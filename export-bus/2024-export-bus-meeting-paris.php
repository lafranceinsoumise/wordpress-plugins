<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Paris 25/05/2024
(new WC_Bus_Order_Exporter(66286, "1EKa6mZ8zUYHk3355RS_DwA5ruf-IHKWunmzQVRmA888"))->export();
