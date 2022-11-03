<?php

namespace LFI\WPPlugins\AgirEvents\API;

use Exception;
use WP_REST_Request;
use WP_REST_Response;

function search_groups(WP_REST_Request $request)
{
  $options = get_option('lfi_settings');

  $url = $options['api_server'] . '/api/groupes/recherche/';
  $query = [
    'q' =>  $request->get_param('term'),    
  ];
  $url .= '?' . http_build_query($query);

  $response = wp_cache_get($url, 'lfi-agir-events__search_groups');

  if (!$response) {
    $response = wp_remote_get($url, [
      'headers' => [
        'Content-type' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode($options['api_id'] . ':' . $options['api_key']),
        'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
      ]
    ]);
    wp_cache_set($url, $response, 'lfi-agir-events__search_groups');
  }

  $results = [];

  if (!is_array($response)) {
    return new WP_REST_Response(['error' => 'An error has occurred'], 500);
  }

  if (is_wp_error($response) || $response['response']['code'] !== 200) {
    return new WP_REST_Response(
      [
        'error' => $response['response']['message']
      ],
      $response['response']['code']
    );
  }

  try {
    $json = json_decode($response['body'], true);
    foreach ($json['results'] as $group) {
      $results[] = [
        'id' => $group['id'],
        'text' => $group['name'] . ' - ' . $group['location']['zip'] . ' - ' . $group['id'],
      ];
    }
  } catch (Exception $ex) {
    $results = [];
  }

  return ['results' => $results];
}
