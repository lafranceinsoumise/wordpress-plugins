<?php
/*
   Plugin Name: LFI Envoi Parlementaires
   Description: Gère l'envoi de mails automatiques aux parlementaires
   Version: 1.1
   Author: Salomé Cheysson, Alexandra Puret
   License: GPL3
 */

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

require_once(dirname(__FILE__) . '/liste-parlementaires.php');
require_once(dirname(__FILE__) . '/lettre.php');

class Plugin
{
  const TABLE_NAME = 'envoi_parlementaires';
  const TEXTDOMAIN = 'lfi-envoi-parlementaires';
  const API_NAMESPACE = 'envoi-parlementaires/v1';

  public function __construct()
  {
    register_activation_hook(__FILE__, [$this, 'install']);

    add_action('init', [$this, 'init']);
    add_action('rest_api_init', [$this, 'register_planned_sending_route']);

    if (class_exists('WP_CLI')) {
      \WP_CLI::add_command(
        "parlementaires-scheduled",
        [$this, "send_scheduled_emails"],
        [
          'shortdesc' => 'Send scheduled parlementaires emails for a given campaign',
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
          'longdesc' =>   '## EXAMPLES' . "\n\n" . 'wp parlementaires-scheduled [campaign] [--dry-run]',
        ]
      );
    }
  }

  public function lettre_parlementaire_shortcode($attrs)
  {
    wp_enqueue_script(
      'envoi-parlementaires',
      plugins_url('/script.js', __FILE__),
      array('jquery'),
      '1.0.1',
      true
    );
    wp_localize_script(
      'envoi-parlementaires',
      'configParlementaires',
      array(
        'endpointURL' => get_rest_url(null, self::API_NAMESPACE . '/envoi'),
      ),
    );

    $liste_parlementaires = Liste_Parlementaires::get_instance();

    $expediteur = $liste_parlementaires->expediteur(stripslashes_deep($_GET));

    if (is_null($expediteur)) {
      return '';
    }

    $parlementaire = $liste_parlementaires->random_parlementaire();

      return generer_mail(
        $parlementaire,
        $expediteur
      );
  }

  public function planifier_envoi($request)
  {
    global $wpdb;

    $params = $request->get_body_params();

    Liste_Parlementaires::get_instance();

    $wpdb->insert(
      $wpdb->prefix . self::TABLE_NAME,
      array(
        'time' => current_time('mysql'),
        'email' => $params['email'],
        'nom' => $params['nom'],
        'campaign' =>
        $params['campaign'],
      )
    );

    return [];
  }


  public function send_scheduled_email($data, $dry_run = False)
  {
    $liste_parlementaires = Liste_Parlementaires::get_instance();
    $expediteur = $liste_parlementaires->expediteur($data);
    $recipients = $liste_parlementaires->all_parlementaires();

    $count = count($recipients);

    if ($count === 0) {
      \WP_CLI::log("\n— No recipients found.");
      return;
    }

    \WP_CLI::log("\n— Sending $count e-mail(s) for $expediteur[email]");

    foreach ($recipients as $recipient) {
      $subject = objet_lettre($recipient, $expediteur);
      $message = implode("\n\n", mail_contenu($recipient, $expediteur));

      if (false === $dry_run) {
        $result = wp_mail(
          $recipient['email'],
          $subject,
          $message,
          "From:" . $expediteur["nom"] . "<" . $expediteur["email"] . ">"
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

    \WP_CLI::log("\nSending scheduled parlementaires email...");

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
        'nom' => [
          'type' => 'string',
          'required' => true,
        ],
        'prenom' => [
            'type' => 'string',
            'required' => true
        ],
        'email' => [
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

  public function install()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . self::TABLE_NAME;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      email tinytext NOT NULL,
      nom tinytext NOT NULL,
      prenom tinytext NOT NULL,
      campaign tinytext DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function parlementaire_aleatoire_shortcode($attr) {
     $liste_parlementaires = Liste_Parlementaires::get_instance();
     return $liste_parlementaires->random_parlementaire();
  }

  public function init()
  {
    add_shortcode('parlementaire_aleatoire', [$this, 'parlementaire_aleatoire_shortcode']);
    add_shortcode('lettre_parlementaire', [$this, 'lettre_parlementaire_shortcode']);
  }
}

new Plugin();
