<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Amiens 13/05/2024
(new WC_Bus_Order_Exporter(66237, "1Caq7qXl4WH8GlB6-glBgi6fSN1sGozkRyZhu_Yrg3Ws"))->export();
