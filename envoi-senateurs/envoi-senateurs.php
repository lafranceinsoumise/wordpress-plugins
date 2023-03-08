<?php
/*
   Plugin Name: LFI Envoi Sénateurs
   Description: Gère l'envoi de mails automatiques aux sénateurs
   Version: 1.0
   Author: Salomé Cheysson
   License: GPL3
 */

namespace LFI\WPPlugins\EnvoiSenateurs;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

require_once(dirname(__FILE__) . '/liste-senateurs.php');
require_once(dirname(__FILE__) . '/lettre.php');


function expediteur($args) {
  if ( !array_key_exists( 'email', $args )
    || !array_key_exists( 'nom', $args )
    || !array_key_exists( 'prenom', $args )
    || !array_key_exists( 'profession', $args )
    || !array_key_exists( 'civilite', $args ) )
  {
    return null;
  }

  $nom_complet = mb_convert_case( $args['prenom'], MB_CASE_TITLE )
               . ' ' . mb_convert_case( $args['nom'], MB_CASE_UPPER);

  return [
    'email' => $args['email'],
    'nom' => $args['nom'],
    'prenom' => $args['prenom'],
    'profession' => $args['profession'],
    'civilite' => $args['civilite'],
    'nom_complet' => $nom_complet,
  ];
}

class Plugin
{
  const TABLE_NAME = 'envoi_senateurs';
  const TEXTDOMAIN = 'lfi-envoi-senateurs';
  const API_NAMESPACE = 'envoi-senateurs/v1';

  public function __construct()
  {
    register_activation_hook( __FILE__, [$this, 'install'] );

    add_action( 'init', [$this, 'init'] );
    add_action( 'rest_api_init', [$this, 'register_route'] );
  }

  public function lettre_senateurs_shortcode($attrs) {
    wp_enqueue_script(
      'envoi-senateurs',
      plugins_url( '/script.js', __FILE__ ),
      array( 'jquery' ),
      '1.0.0',
      true
    );

    wp_localize_script(
      'envoi-senateurs',
      'configSenateurs',
      array(
        'endpointURL' => get_rest_url( null, self::API_NAMESPACE . '/envoi'),
      ),
    );

    $liste_senateurs = Liste_Senateurs::get_instance();

    $expediteur = expediteur($_GET);
    $departement = $_GET['departement'] ?? null;

    if ( is_null( $expediteur ) || is_null( $liste_senateurs->departement( $departement ) ) ) {
      return '';
    }

    $senateur = $liste_senateurs->random_senateur( $departement );

    $result = generer_lettre_html(
      $senateur, $expediteur
    );

    return $result;
  }


  public function planifier_envoi( $request ) {
    global $wpdb;

    $params = $request->get_body_params();

    if ( is_null(Liste_Senateurs::get_instance()->senateur (
      $params['departement'],
      $params['senateur'] ) ) )
    {
      return new WP_Error(
        'rest_invalid_param',
        sprintf( esc_html__( '%1$s n\'est pas un département', self::TEXTDOMAIN ), $departement, 'string' ),
        array ( 'status' => 400 ),
      );
    }

    $wpdb->insert(
      $wpdb->prefix . self::TABLE_NAME,
      array(
        'time' => current_time( 'mysql' ),
        'departement' => $params['departement'],
        'senateur' => $params['senateur'],
        'email' => $params['email'],
        'nom' => $params['nom'],
        'prenom' => $params['prenom'],
        'profession' => $params['profession'],
      )
    );

    return [];
  }

  public function register_route()
  {
    register_rest_route( self::API_NAMESPACE, '/envoi', array(
      'methods' => 'POST',
      'callback' => [$this, 'planifier_envoi'],
      'permission_callback' => function () { return true; },
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
      ]
    ) );
  }

  public function install() {
    global $wpdb;

    $table_name = $wpdb->prefix . self::TABLE_NAME;
    $charset_collate= $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      departement tinytext NOT NULL,
      senateur tinyint(2) NOT NULL,
      email tinytext NOT NULL,
      nom tinytext NOT NULL,
      prenom tinytext NOT NULL,
      profession tinytext NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }

  public function init() {
    add_shortcode( 'lettre_senateurs', [$this, 'lettre_senateurs_shortcode'] );
  }
}

new Plugin();
