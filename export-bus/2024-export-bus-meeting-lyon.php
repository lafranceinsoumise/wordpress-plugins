<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Lyon 06/06/2024
(new WC_Bus_Order_Exporter(69215, "1kczfXHj-rfQNQBZmOw5JT9lC9Q5yUOq6VM6hqJCmo_c"))->export();
