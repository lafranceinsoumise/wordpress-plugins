<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Toulouse 01/06/2024
(new WC_Bus_Order_Exporter(66496, "1lnMKAnJunq90TRk85enO11CJtOHGP7mrjJhl5i1wQIQ"))->export();
