<?php
/*
   Plugin Name: LFI Envoi Sénateurs
   Description: Gère l'envoi de mails automatiques aux sénateurs
   Version: 2.0
   Author: Salomé Cheysson, Giuseppe De Ponte
   License: GPL3
 */

namespace LFI\WPPlugins\EnvoiSenateurs;

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

require_once(dirname(__FILE__) . '/liste-senateurs.php');
require_once(dirname(__FILE__) . '/lettre.php');

class Plugin
{
  const TABLE_NAME = 'envoi_senateurs';
  const TEXTDOMAIN = 'lfi-envoi-senateurs';
  const API_NAMESPACE = 'envoi-senateurs/v1';

  public function __construct()
  {
    register_activation_hook(__FILE__, [$this, 'install']);

    add_action('init', [$this, 'init']);
    add_action('rest_api_init', [$this, 'register_planned_sending_route']);
    add_action('elementor_pro/forms/new_record', [$this, 'save_new_blacklist_record'], 10, 2);

    if (class_exists('WP_CLI')) {
      \WP_CLI::add_command(
        "senateurs-scheduled",
        [$this, "send_scheduled_emails"],
        [
          'shortdesc' => 'Send scheduled senateurs emails for a given campaign',
          'synopsis' => array(
            array(
              'type'        => 'positional',
              'name'        => 'campaign',
              'description' => 'The unique identifier for the campaign to sent',
              'optional'    => false,
              'repeating'   => false,
            ),
            array(
              'type'        => 'flag',
              'name'        => 'dry-run',
              'description' => 'Whether or not to actually send the emails and update the DB',
              'optional'    => true,
              'default'     => 'false',
            ),
          ),
          'longdesc' =>   '## EXAMPLES' . "\n\n" . 'wp senateurs-scheduled [campaign] [--dry-run]',
        ]
      );
    }
  }

  public function lettre_senateurs_shortcode($attrs)
  {
    wp_enqueue_script(
      'envoi-senateurs',
      plugins_url('/script.js', __FILE__),
      array('jquery'),
      '1.0.1',
      true
    );

    wp_localize_script(
      'envoi-senateurs',
      'configSenateurs',
      array(
        'endpointURL' => get_rest_url(null, self::API_NAMESPACE . '/envoi'),
      ),
    );

    $liste_senateurs = Liste_Senateurs::get_instance();

    $expediteur = $liste_senateurs->expediteur(stripslashes_deep($_GET));
    $departement = $_GET['departement'] ?? null;

    if (is_null($expediteur) || is_null($liste_senateurs->departement($departement))) {
      return '';
    }

    $senateur = $liste_senateurs->random_senateur($departement);
    $twitters =
      $liste_senateurs->departement_twitters($departement);
    $facebooks = $liste_senateurs->departement_facebooks($departement);

    $result = generer_interpellation(
      $senateur,
      $twitters,
      $facebooks,
      $expediteur
    );

    return $result;
  }

  public function planifier_envoi($request)
  {
    global $wpdb;

    $params = $request->get_body_params();

    if (is_null(Liste_Senateurs::get_instance()->senateur(
      $params['departement'],
      null,
      $params['senateur']
    ))) {
      return new \WP_Error(
        'rest_invalid_param',
        sprintf(esc_html__('%1$s n\'est pas un département', self::TEXTDOMAIN), $departement, 'string'),
        array('status' => 400),
      );
    }

    $wpdb->insert(
      $wpdb->prefix . self::TABLE_NAME,
      array(
        'time' => current_time('mysql'),
        'departement' => $params['departement'],
        'senateur' => $params['senateur'],
        'email' => $params['email'],
        'nom' => $params['nom'],
        'prenom' => $params['prenom'],
        'profession' => $params['profession'],
        'civilite' => $params['civilite'],
        'campaign' =>
        $params['campaign'],
      )
    );

    return [];
  }


  public function send_scheduled_email($data, $dry_run = False)
  {
    $liste_senateurs = Liste_Senateurs::get_instance();
    $expediteur = $liste_senateurs->expediteur($data);
    $recipients = $liste_senateurs->departement_senateurs($data['departement']);

    $count = count($recipients);

    if ($count === 0) {
      \WP_CLI::log("\n— No recipients found for departement $data[departement].");
      return;
    }

    \WP_CLI::log("\n— Sending $count e-mail(s) for $expediteur[email] ($data[departement])");

    foreach ($recipients as $recipient) {
      $recipient = $liste_senateurs->senateur($data['departement'], $recipient);
      $subject = objet_lettre($recipient, $expediteur);
      $message = implode("\n\n", texte_lettre($recipient, $expediteur));

      if (false === $dry_run) {
        $result = wp_mail(
          $recipient['email'],
          $subject,
          $message,
          "From:" . $expediteur["nom_complet"] . "<" . $expediteur["email"] . ">"
        );
      }

      if ($dry_run || true === $result) {
        \WP_CLI::log("   ✅ $recipient[email]");
      } else {
        \WP_CLI::log("   ❌ $recipient[email]");
      }
    }
  }

  public function send_scheduled_emails($args, $assoc_args)
  {
    global $wpdb;

    if (false === class_exists('WP_CLI')) {
      return;
    }

    $campaign = $args[0];
    $dry_run = isset($assoc_args["dry-run"]) ? $assoc_args["dry-run"] : false;
    $datetime = date(\DateTime::ATOM);

    \WP_CLI::log("\nSending scheduled senateurs email...");

    if ($dry_run) {
      \WP_CLI::log("[ DRY-MODE - $datetime ]");
    } else {
      \WP_CLI::log("[ $datetime ]");
    }


    $db_table = $wpdb->prefix . self::TABLE_NAME;
    $rows = $wpdb->get_results(
      "
      SELECT * FROM $db_table
      WHERE campaign = '$campaign'
      AND email NOT IN (
        SELECT email FROM $db_table
        WHERE campaign LIKE '$campaign::sent::%'
      );
      ",
      "OBJECT_K"
    );

    if (empty($rows)) {
      \WP_CLI::log("\nNo email requests found !\n");
      return;
    }

    $count = count($rows);
    \WP_CLI::log("\n$count email request(s) found.");

    $handled_senders = [];

    foreach ($rows as $row) {
      # Avoid sending multiple messages for the same sender email address
      if (in_array($row->email, $handled_senders)) {
        continue;
      }

      # Send the emails
      $this->send_scheduled_email(
        (array)$row,
        $dry_run
      );

      # Add sender to handled senders to avoid duplicates
      array_push($handled_senders, $row->email);

      # Update the DB row to mark the email as sent
      if (false === $dry_run) {
        $wpdb->update(
          $db_table,
          [
            "campaign" => "$row->campaign::sent::$datetime"
          ],
          [
            "id" => $row->id
          ]
        );
      }
    }

    \WP_CLI::log("\nAll email request(s) have been handled !\n");
  }

  public function register_planned_sending_route()
  {
    register_rest_route(self::API_NAMESPACE, '/envoi', array(
      'methods' => 'POST',
      'callback' => [$this, 'planifier_envoi'],
      'permission_callback' => function () {
        return true;
      },
      'args' => [
        'departement' => [
          'required' => true,
        ],
        'senateur' => [
          'required' => true,
        ],
        'nom' => [
          'type' => 'string',
          'required' => true,
        ],
        'prenom' => [
          'type' => 'string',
          'required' => true,
        ],
        'email' => [
          'type' => 'string',
          'required' => true,
        ],
        'civilite' => [
          'type' => 'string',
          'required' => true,
        ],
        'campaign' => [
          'type' => 'string',
          'required' => true,
        ],
      ]
    ));
  }


  public function save_new_blacklist_record($record, $handler)
  {
    $form_name = $record->get_form_settings('form_name');
    if ('blacklist_senateurs' !== $form_name) {
      return;
    }
    $raw_fields = $record->get('fields');
    $fields = [];
    foreach ($raw_fields as $id => $field) {
      $fields[$id] = $field['value'];
    }
    $email = trim(strtolower($fields["email"]));

    $blacklist = file_exists(dirname(__FILE__) . '/blacklist.json') ? wp_json_file_decode(
      dirname(__FILE__) . '/blacklist.json'
    ) : [];

    if (in_array($email, $blacklist)) {
      return $blacklist;
    }

    $senateurs_config = wp_json_file_decode(
      dirname(__FILE__) . '/senateurs.json',
      ['associative' => true]
    );

    $match = false;
    foreach ($senateurs_config as $k => $v) {
      foreach ($v["sen"] as $senateur) {
        if ($senateur["e"] === $email) {
          $match = $senateur["e"];
          break 2;
        }
      }
    }

    if (false === $match) {
      return $blacklist;
    }

    array_push($blacklist, $match);
    $responseData = wp_json_encode($blacklist);
    file_put_contents(dirname(__FILE__) . '/blacklist.json', $responseData);

    return $blacklist;
  }

  public function install()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . self::TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      departement tinytext NOT NULL,
      senateur tinyint(2) NOT NULL,
      email tinytext NOT NULL,
      nom tinytext NOT NULL,
      prenom tinytext NOT NULL,
      profession tinytext NOT NULL,
      civilite tinytext DEFAULT '' NOT NULL,
      campaign tinytext DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function init()
  {
    add_shortcode('lettre_senateurs', [$this, 'lettre_senateurs_shortcode']);
  }
}

new Plugin();
