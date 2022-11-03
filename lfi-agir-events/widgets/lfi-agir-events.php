<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

class LFIAgirEvents_Elementor_List_Widget extends \Elementor\Widget_Base
{
  public function get_name()
  {
    return 'lfi-agir-events';
  }

  public function get_title()
  {
    return esc_html__('Événements Action populaire', 'lfi-agir-events__title');
  }

  public function get_icon()
  {
    return 'eicon-map-pin';
  }

  public function get_keywords()
  {
    return ['événement',  'évènement', 'evenement', 'event', 'action', 'populaire'];
  }

  public function get_custom_help_url()
  {
    return 'https://github.com/lafranceinsoumise/wordpress-plugins/tree/main/lfi-agir-events';
  }

  public function get_categories()
  {
    return ['general', 'actionpopulaire'];
  }

  public function get_style_depends()
  {
    wp_register_style(
      'lfi-agir-events',
      plugins_url('assets/css/lfi-agir-events.min.css', __FILE__),
      []
    );

    return ['lfi-agir-events'];
  }

  protected function register_controls()
  {
    $this->start_controls_section(
      'content_section',
      [
        'label' => esc_html__('Liste des événements', 'lfi-agir-events__content_section'),
        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'organizer_group',
      [
        'label' => esc_html__('Id du groupe', 'lfi-agir-events__organizer_group'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => esc_html__('aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group'),
        'title' => esc_html__('Ex. aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group'),
        'description' => esc_html__('Retrouvez l\'identifiant du groupe organisateur des événéments dans l\'URL de la page publique du groupe sur Action populaire. Ex. https://actionpopulaire.fr/groupes/aec78081-3b87-40d5-b097-e8374eef4a89/ → aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group'),
        'default' => '',
      ]
    );

    /*
    // TODO: Elementor Select2 control may be used, if a solution is found to automatically add and select the initial value
    // (currently the selected value is not displayed on page load)
    $this->add_control(
      'organizer_group_id',
      [
        'label' => esc_html__('Id du groupe', 'lfi-agir-events__organizer_group_id'),
        'type' => \Elementor\Controls_Manager::SELECT2,
        'placeholder' => esc_html__('aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group_id'),
        'title' => esc_html__('Ex. aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group_id'),
        'description' => esc_html__('Retrouvez l\'identifiant du groupe organisateur des événéments dans l\'URL de la page publique du groupe sur Action populaire. Ex. https://actionpopulaire.fr/groupes/aec78081-3b87-40d5-b097-e8374eef4a89/ → aec78081-3b87-40d5-b097-e8374eef4a89', 'lfi-agir-events__organizer_group_id'),
        'default' => '',
        'options' => [
          '' => esc_html__('', 'lfi-agir-events__organizer_group_id')
        ],
        'lockedOptions' => [''],
        'select2options' => [
          'name' => 'organizer_group_id',
          'placeholder' => 'Chercher un groupe',
          'minimumInputLength' => 3,
          'ajax' => [
            'url' => get_rest_url(null, 'lfi-agir-events/groups'),
            'dataType' => 'json',
            'delay' => 250,
            'cache' => true
          ],
        ]
      ]
    );
    */

    $this->add_control(
      'event_type',
      [
        'label' => esc_html__('Type d\'événements', 'lfi-agir-events__event_type'),
        'type' => \Elementor\Controls_Manager::SELECT,
        'default' => '',
        'options' => [
          '' => esc_html__('Tous', 'lfi-agir-events__event_type'),
          'a-venir/' => esc_html__('À  venir', 'lfi-agir-events__event_type'),
          'passes/' => esc_html__('Passés', 'lfi-agir-events__event_type'),
        ],
      ]
    );

    $this->add_control(
      'event_items',
      [
        'label' => esc_html__('Nombre d\'événements à afficher', 'lfi-agir-events__event_items'),
        'type' => \Elementor\Controls_Manager::NUMBER,
        'min' => 1,
        'max' => 10,
        'step' => 1,
        'default' => 3,
      ]
    );

    $this->end_controls_section();

    $this->start_controls_section(
      'style_content_section',
      [
        'label' => esc_html__('List Style', 'elementor-list-widget'),
        'tab' => \Elementor\Controls_Manager::TAB_STYLE,
      ]
    );

    $this->add_group_control(
      \Elementor\Group_Control_Typography::get_type(),
      [
        'name' => 'icon_typography',
        'selector' => '{{WRAPPER}} .lfi-agir-events, {{WRAPPER}} .lfi-agir-events > a',
      ]
    );

    $this->end_controls_section();
  }

  protected function get_event_link_attrs($event)
  {
    $options = get_option('lfi_settings');
    $this->add_render_attribute(
      "event_link_{$event['id']}",
      [
        'href' => "{$options['api_server']}/evenements/{$event["id"]}/",
        'title' => "Page de l'événement : {$event['name']}",
        'rel' => "noopener noreferrer",
        'target' => "blank",
      ]
    );

    return $this->get_render_attribute_string("event_link_{$event['id']}");;
  }

  protected function get_group_events()
  {
    $events = [];
    $options = get_option('lfi_settings');
    $settings = $this->get_settings_for_display();
    $group_id = $settings['organizer_group'];

    if (!$group_id) {
      return $events;
    }

    $event_type = $settings['event_type'];
    $url = "{$options['api_server']}/api/groupes/{$group_id}/evenements/{$event_type}";
    $response = wp_remote_get($url, [
      'headers' => [
        'Content-type' => 'application/json',
        'X-Wordpress-Client' => $_SERVER['REMOTE_ADDR']
      ]
    ]);

    if (!is_array($response)) {
      return [
        'group_id' => $group_id,
        'url' => $url,
        'error' => 'An error has occurred',
        'code' => 500,
      ];
    }

    if (is_wp_error($response) || $response['response']['code'] !== 200) {
      return [
        'group_id' => $group_id,
        'url' => $url,
        'error' => $response['response']['message'],
        'code' => $response['response']['code'],
      ];
    }

    try {
      $events = json_decode($response['body'], true);
      $events = array_key_exists("results", $events) ? $events["results"] : $events;
    } catch (Exception $ex) {
      $events = [];
    }

    if ($events && count($events) > 0) {
      $event_items = $settings['event_items'];
      usort($events, fn ($a, $b) => $b['endTime'] <=> $a['endTime']);
      $events = array_slice($events, 0, $event_items);
    }

    return $events;
  }

  protected function content_template()
  {
  }

  protected function render()
  {
    $events = $this->get_group_events();
    if (array_key_exists("error", $events)) {
?>
      <div class="lfi-agir-events lfi-agir-events--error">
        <h5>&#9888; Une erreur est survenue&nbsp;:</h5>
        <pre><?php echo json_encode($events, JSON_PRETTY_PRINT) ?></pre>
      </div>
    <?php
      return;
    }
    ?>
    <ul class="lfi-agir-events lfi-agir-events--list">
      <?php
      foreach ($events as $event) {
      ?>
        <li class="lfi-agir-events__item">
          <a <?php echo $this->get_event_link_attrs($event) ?>>
            <strong><?php echo wp_date(__('j F Y à g:i'), strtotime($event['startTime'])); ?></strong>
            <span><?php echo $event['name']; ?></span>
          </a>
        </li>
      <?php
      }
      ?>
    </ul>
<?php
  }
}
