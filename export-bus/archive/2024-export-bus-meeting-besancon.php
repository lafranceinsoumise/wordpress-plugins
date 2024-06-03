<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Besançon 27/05/2024
(new WC_Bus_Order_Exporter(66466, "12d4-1fi0ek0e18qqbJkiMeIc__jSC1P69xNpVbq4NOA"))->export();
