<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Grenoble 30/04/2024
(new WC_Bus_Order_Exporter(64065, "1_BzsLjEcSVrLem60QLndns8fUDvYw1guUlx-pYfGTi0"))->export();
