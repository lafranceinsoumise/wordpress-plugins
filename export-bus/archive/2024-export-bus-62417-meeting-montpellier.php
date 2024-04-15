<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Montpellier 14/04/2024
(new WC_Bus_Order_Exporter(62417, "1dPlab0UQa1Yntse710hC-rRGyRC8GtDlcu_gp3s4rxg"))->export();
