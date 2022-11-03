<?php

function search_groups(WP_REST_Request $request)
{
  $options = get_option('lfi_settings');

  $url = $options['api_server'] . '/api/recherche/';

  $query = [
    'q' =>  $request->get_param('term'),
    'type' => 'groups',
    'filters[groupInactive]' => 1
  ];

  $response = wp_remote_get($url . '?' . http_build_query($query), [
    'headers' => [
      'Content-type' => 'application/json',
      'Authorization' => 'Basic ' . base64_encode($options['api_id'] . ':' . $options['api_key']),
      'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
    ]
  ]);

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
    foreach ($json['groups'] as $group) {
      $results[] = [
        'id' => $group['name'] . ' - ' . $group['location']['zip'] . ' - ' . $group['id'],
        'text' => $group['name'] . ' - ' . $group['location']['zip'] . ' - ' . $group['id'],
      ];
    }
  } catch (Exception $ex) {
    $results = [];
  }

  return ['results' => $results];
}
