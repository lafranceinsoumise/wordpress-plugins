<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/vendor/google-api-php-client/vendor/autoload.php');

class WC_Bus_Order_Exporter
{
    const CATEGORY_BUS_ID = "198";
    const META_PREFIX = "meta__";
    const GOOGLE_CLIENT_AUTH_FILE_PATH = '/gce.json';

    protected int $PARENT_PRODUCT_ID;
    protected string $GOOGLE_SPREADSHEET_ID;
    protected string $GOOGLE_SPREADSHEET_RANGE;

    public function __construct(
        int $PARENT_PRODUCT_ID,
        string $GOOGLE_SPREADSHEET_ID,
        string $GOOGLE_SPREADSHEET_RANGE = "_export"
    ) {
        $this->PARENT_PRODUCT_ID = $PARENT_PRODUCT_ID;
        $this->GOOGLE_SPREADSHEET_ID = $GOOGLE_SPREADSHEET_ID;
        $this->GOOGLE_SPREADSHEET_RANGE = $GOOGLE_SPREADSHEET_RANGE;
    }

    private function log($message)
    {
        if (is_string($message)) {
            \WP_CLI::log($message);
        } else {
            \WP_CLI::log(json_encode($message, JSON_PRETTY_PRINT));
        }
    }

    private function get_sanitized_id_list($ids)
    {
        return implode(',', array_map('absint', is_string($ids) ? explode(',', $ids) : $ids));
    }

    private function get_item_formatted_meta_data($item, $hide_prefix = '_', $include_all = false)
    {

        $item_meta = new \WC_Order_Item_Meta($item);
        $item_meta = $item_meta->get_formatted($hide_prefix);

        return $item_meta;
    }

    private function order_item_is_bus($order_item)
    {
        return in_array(self::CATEGORY_BUS_ID, wp_get_post_terms(
            $order_item->get_product_id(),
            'product_cat',
            array('fields' => 'ids')
        ));
    }

    private function format_date($date)
    {
        return is_callable([$date, 'date']) ? $date->date('Y-m-d H:i:s') : strval($date);
    }

    private function format_phone($phone)
    {
        $phone = strval($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        return in_array($phone[0], ["0", "+"]) ? $phone : "0" . $phone;
    }

    private function get_order_notes($order)
    {

        $callback = ['WC_Comments', 'exclude_order_comments'];
        $args = [
            'post_id' => $order->get_id(),
            'approve' => 'approve',
            'type'    => 'order_note',
        ];
        remove_filter('comments_clauses', $callback);
        $notes = get_comments($args);
        add_filter('comments_clauses', $callback);
        $order_notes = [];
        foreach ($notes as $note) {
            $order_notes[] = str_replace(["\r", "\n"], ' ', $note->comment_content);
        }

        return $order_notes;
    }

    private function get_bus_orders()
    {
        global $wpdb;

        $product_id = $this->PARENT_PRODUCT_ID;
        $bus_order_id_list = $wpdb->get_col(
            "
            SELECT DISTINCT woi.order_id
            FROM {$wpdb->prefix}woocommerce_order_itemmeta woim,
                {$wpdb->prefix}woocommerce_order_items woi,
                {$wpdb->prefix}posts p
            WHERE woi.order_item_id = woim.order_item_id
            AND woi.order_id = p.ID
            AND woim.meta_key IN ( '_product_id', '_variation_id' )
            AND woim.meta_value = {$product_id}
            ORDER BY woi.order_item_id DESC;"
        );

        if (count($bus_order_id_list) === 0) {
            return [];
        }

        $args = array(
            'post__in' => $bus_order_id_list,
            'status' => "any",
            'type' => 'shop_order',
            'limit' => -1,
            'orderby' => 'date',
        );
        return wc_get_orders($args);
    }

    private function get_attribute_stock($order_item)
    {
        $product = $order_item->get_product();
        $attributes = mewz_wcas_get_product_attributes($product);
        $stocks = mewz_wcas_match_product_stock($product, $attributes);
        foreach ($stocks as $stock) {
            $s = mewz_wcas_get_stock($stock["stock_id"]);

            if (in_array($this->PARENT_PRODUCT_ID, $s->products())) {
                return explode(" - ", $s->title())[1];
            }
        }
        return "";
    }

    private function format_order_item($order, $item, $context)
    {
        $data = array();

        # Order and item main data
        $data["id"] = $order->get_id($context);
        $data["date"] = $this->format_date($order->get_date_created($context));
        $data["status"] = $order->get_status($context);
        $data["item_name"] = $item->get_name($context);
        $data["quantity"] = $item->get_quantity($context) && is_numeric($item->get_quantity($context)) ? (float) $item->get_quantity($context) : NULL;

        $data["attribute_stock"] = $this->get_attribute_stock($item);
        $meta_formatted = $this->get_item_formatted_meta_data($item, '_');
        foreach ($meta_formatted as $meta_key => $formatted_meta) {
            $data[self::META_PREFIX . $formatted_meta['key']] = wp_strip_all_tags(str_replace('"', '&quot;', $formatted_meta['value']));
        }
        # Customer
        $data["first_name"] = ucwords(strtolower($order->get_billing_first_name($context)));
        $data["last_name"] = ucwords(strtolower($order->get_billing_last_name($context)));
        $data["email"] = strtolower($order->get_billing_email($context));
        $data["phone"] = $this->format_phone($order->get_billing_phone($context));
        # Payment
        $data["total_price"] = (float) $item->get_total($context);
        if ($data["total_price"] > 0) {
            $data["unit_price"] = is_null($data["quantity"]) ? 0 : (float) $item->get_total($context) / $data["quantity"];
            $data["payment_method"] = $order->get_payment_method($context);
            $data["payment_method_title"] = $order->get_payment_method_title($context);
        }
        # Extra data
        $data["date_completed"] = $this->format_date($order->get_date_completed($context));
        $data["date_paid"] = $this->format_date($order->get_date_paid($context));
        $data["customer_note"] = $order->get_customer_note($context);
        // $data['order_notes'] = implode('\r\n', $this->get_order_notes($order));

        return $data;
    }


    private function format_orders($orders, $datetime = "", $context = 'view')
    {
        $formatted_order_items = [];
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if (false === $this->order_item_is_bus($item)) {
                    continue;
                }
                $data = $this->format_order_item($order, $item, $context);
                $data["export_date"] = $datetime;
            }
            array_push($formatted_order_items, $data);
        }
        return $formatted_order_items;
    }

    private function get_google_sheets_client_service()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig(dirname(__FILE__) . self::GOOGLE_CLIENT_AUTH_FILE_PATH);
        $service = new \Google_Service_Sheets($client);

        return $service;
    }

    private function export_to_google_sheet($orders)
    {
        $service = $this->get_google_sheets_client_service();
        $cols = array_keys($orders[0]);
        $rows = [$cols];
        foreach ($orders as $order) {
            $row = [];
            foreach ($cols as $key) {
                array_push($row, $order[$key] ?? "");
            }
            array_push($rows, $row);
        }
        $rows = new \Google_Service_Sheets_ValueRange(array(
            'values' => $rows
        ));
        $params = array(
            "valueInputOption" => "USER_ENTERED"
        );
        $this->log($params);
        $result = $service->spreadsheets_values->update($this->GOOGLE_SPREADSHEET_ID, $this->GOOGLE_SPREADSHEET_RANGE, $rows, $params);

        return $result;
    }

    public function export()
    {
        $now = (new DateTimeImmutable())->format(DateTime::ATOM);
        $this->log("\n======================================================================");
        $this->log("[$now] :: WC_Bus_Order_Exporter execution start");
        $orders = $this->get_bus_orders();
        $orders = $this->format_orders($orders, $now);
        $count = count($orders);
        $this->log($count . " orders found ...");
        if ($count > 0) {
            $result = $this->export_to_google_sheet($orders);
            $this->log("... and succesfully exported to google sheets !");
            $this->log($result);
        }
        $this->log("======================================================================");
    }
}
