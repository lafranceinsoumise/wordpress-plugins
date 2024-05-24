<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Bordeaux 18/05/2024
(new WC_Bus_Order_Exporter(66917, "1gxaPSndHjvE1gJuljsKH9dxRkzFuM8vZdfQqwnishXk"))->export();
