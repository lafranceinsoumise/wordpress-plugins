<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

// Bus meeting Strasbourg 23/04/2024
(new WC_Bus_Order_Exporter(62492, "1kF6ScCBd2ltW5nfkJjCI0uhe2MZ1MnAC0O7nCAnNLp0"))->export();
