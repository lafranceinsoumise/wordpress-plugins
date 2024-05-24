<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Marseille 23/05/2024
(new WC_Bus_Order_Exporter(66246, "1RB6BeOLCoK9xieWITNVJeir_7WkPvWnN3VBJ-E8aGrY"))->export();
