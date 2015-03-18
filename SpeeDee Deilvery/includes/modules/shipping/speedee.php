<?php

/*

  Released under the GNU General Public License
 */

class speedee extends base {

    var $code, $title, $description, $icon, $enabled;

// class constructor
    function __construct() {
        global $order, $db;

        $this->code = 'speedee';
        $this->title = MODULE_SHIPPING_SPEEDEE_TEXT_TITLE;
        $this->description = MODULE_SHIPPING_SPEEDEE_TEXT_DESCRIPTION;
        $this->mark_up = MODULE_SHIPPING_SPEEDEE_MARK_UP;
        $this->sort_order = MODULE_SHIPPING_SPEEDEE_SORT_ORDER;
        $this->icon = DIR_WS_IMAGES . 'icons/speedee_round.jpg';
        $this->tax_class = MODULE_SHIPPING_SPEEDEE_TAX_CLASS;
        $this->enabled = ((MODULE_SHIPPING_SPEEDEE_STATUS == 'True') ? true : false);
            if (IS_ADMIN_FLAG) {
                $this->enabled = TRUE;
                $new_version_details = plugin_version_check_for_updates(1884, MODULE_SHIPPING_SPEEDEE_VERSION);
                if ($new_version_details !== FALSE) {
                    $this->title .= '<span class="alert">' . ' - NOTE: A NEW VERSION OF THIS PLUGIN IS AVAILABLE. <a href="' . $new_version_details['link'] . '" target="_blank">[Details]</a>' . '</span>';
                }
            }
        $speeddee_monthyear = date("Ym");
        if (MODULE_SHIPPING_SPEEDEE_VERSION == 'True' && $speeddee_monthyear > MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_LAST_UPDATE) {
            $update_surcharge = false;
            $usefromDate = date("Ym", strtotime("-2 months"));
            $json_eia = file_get_contents('http://api.eia.gov/series/?api_key=0559570E1FCE8912AAE164F439814034&series_id=PET.EMA_EPD2DXL0_PTG_NUS_DPG.M');
            foreach ($diesel_date = $json_eia['request']['series'][0]['data'] as $data_value) {
                if ($data_value[0] == $usefromDate) {
                    $eia_array = $data_value;
                    $update_surcharge = true;
                }
            }
            if ($update_surcharge == true) {
                if ((float) $eia_array[1] <= 5.47) {
                    $speedee_surcharge_array[] = array('min' => '2.39', 'max' => '2.61', 'surcharge' => '4.00');
                    $speedee_surcharge_array[] = array('min' => '2.61', 'max' => '2.83', 'surcharge' => '4.50');
                    $speedee_surcharge_array[] = array('min' => '2.83', 'max' => '3.05', 'surcharge' => '5.00');
                    $speedee_surcharge_array[] = array('min' => '3.05', 'max' => '3.27', 'surcharge' => '5.50');
                    $speedee_surcharge_array[] = array('min' => '3.27', 'max' => '3.49', 'surcharge' => '6.00');
                    $speedee_surcharge_array[] = array('min' => '3.49', 'max' => '3.71', 'surcharge' => '6.50');
                    $speedee_surcharge_array[] = array('min' => '3.71', 'max' => '3.93', 'surcharge' => '7.00');
                    $speedee_surcharge_array[] = array('min' => '3.93', 'max' => '4.15', 'surcharge' => '7.50');
                    $speedee_surcharge_array[] = array('min' => '4.15', 'max' => '4.37', 'surcharge' => '8.00');
                    $speedee_surcharge_array[] = array('min' => '4.37', 'max' => '4.59', 'surcharge' => '8.50');
                    $speedee_surcharge_array[] = array('min' => '4.59', 'max' => '4.81', 'surcharge' => '9.00');
                    $speedee_surcharge_array[] = array('min' => '4.81', 'max' => '5.03', 'surcharge' => '9.50');
                    $speedee_surcharge_array[] = array('min' => '5.03', 'max' => '5.25', 'surcharge' => '10.00');
                    $speedee_surcharge_array[] = array('min' => '5.05', 'max' => '5.47', 'surcharge' => '10.50');
                    foreach ($speedee_surcharge_array as $surcharge_table_value) {
                        if ((float) $eia_array[1] < (float) $speedee_surcharge_array['max']) {
                            $fuel_surcharge = $speedee_surcharge_array['surcharge'];
                        }
                    }
                } else {
                    $diff_eia_price = (float) $eia_array[1] - 5.47;
                    $times_over_eia_price = ceil($diff_eia_price / 0.22);
                    $fuel_surcharge = $times_over_eia_price + 10.50;
                }
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value='" . $fuel_surcharge . "' WHERE configuration_key='MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE'");
                $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value='" . $speeddee_monthyear . "' WHERE configuration_key='MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_LAST_UPDATE'");
            }
        }

// class methods
        function quote($method = '') {
            global $order, $shipping_weight, $shipping_num_boxes, $total_weight, $boxcount, $handling_cp, $cart;
            $speedee_shipping_weight = $shipping_weight;
            $srcFSA = substr(SHIPPING_ORIGIN_ZIP, 0, 5);
            $desFSA = substr($order->delivery['postcode'], 0, 5);


            $request = join('&', array('source_zip=' . $srcFSA,
                'dest_zip=' . $desFSA,
                'weight=' . intval($speedee_shipping_weight),
                'account=' . MODULE_SHIPPING_SPEEDEE_ACCOUNT));




            $body = file_get_contents('http://www.speedeedelivery.com/cgi-bin/interface.cgi?' . $request);
            $return_array = explode(",", $body);
            if ($return_array[0] != '') {
                $base_shipping_cost = $return_array[0];
                $MarkUp = MODULE_SHIPPING_SPEEDEE_MARK_UP;
                $FuelSurcharge_base = MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE;
                $FuelSurcharge = 1 + $FuelSurcharge_base / 100;
                $ShippingCost = $base_shipping_cost * $FuelSurcharge + $MarkUp;
                $this->quotes = array('id' => $this->code,
                    'module' => MODULE_SHIPPING_SPEEDEE_TEXT_TITLE,
                    'methods' => array(array('id' => $this->code,
                            'title' => MODULE_SHIPPING_SPEEDEE_TEXT_WAY,
                            'cost' => $ShippingCost)));
            } else {
                $this->quotes = array('module' => $this->title,
                    'error' => 'No Delivery to your area');
            }

            if ($this->tax_class > 0) {
                $this->quotes['tax'] = zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
            }

            if (zen_not_null($this->icon))
                $this->quotes['icon'] = zen_image($this->icon, $this->title, null, null, 'align="middle"');

            return $this->quotes;
        }

        function check() {
            global $db;
            if (!isset($this->_check)) {
                $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_SHIPPING_SPEEDEE_STATUS'");
                $this->_check = $check_query->RecordCount();
            }
            return $this->_check;
        }

        function install() {
            global $db;
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable SPEEDEE Shipping', 'MODULE_SHIPPING_SPEEDEE_STATUS', 'True', 'Do you want to offer SPEEDEE rate shipping?', '1', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_SPEEDEE_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '6', '2', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mark Up', 'MODULE_SHIPPING_SPEEDEE_MARK_UP', '1', 'Use the following mark-up on the shipping list fees.', '6', '3', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_SPEEDEE_SORT_ORDER', '0', 'Sort order of display.', '6', '4', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fuel Surcharge Rate', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE', '16.8', 'KEEP UP TO DATE: Fuel Surcharge Rate Enter as a percentage without the % sign (eg. 16.8).', '6', '5', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Fuel Surcharge Rate - Auromatic Update', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_AUTO_UPDATE', 'True', 'Auto Update Fuel Surcharge', '6', '6', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fuel Surcharge Rate - Last Update', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_LAST_UPDATE', '201411', 'Last Month fuel surcharge was updated', '6', '7', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Account Number', 'MODULE_SHIPPING_SPEEDEE_ACCOUNT', '1011', 'This is your Spee-Dee Deilvery Account Number', '6', '8', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Version', 'MODULE_SHIPPING_SPEEDEE_VERSION', '1.1.1', 'SpeeDee Delivery Module Version', '6', '99', now())");
        }

        function remove() {
            global $db;
            $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }

        function keys() {
            return array('MODULE_SHIPPING_SPEEDEE_STATUS', 'MODULE_SHIPPING_SPEEDEE_TAX_CLASS', 'MODULE_SHIPPING_SPEEDEE_MARK_UP', 'MODULE_SHIPPING_SPEEDEE_SORT_ORDER', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE', 'MODULE_SHIPPING_SPEEDEE_ACCOUNT', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_LAST_UPDATE', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE_AUTO_UPDATE', 'MODULE_SHIPPING_SPEEDEE_VERSION');
        }

    }

}

/**
 * this is ONLY here to offer compatibility with ZC versions prior to v1.5.2
 */
    if (!function_exists('plugin_version_check_for_updates')) {
    function plugin_version_check_for_updates($fileid = 0, $version_string_to_check = '') {
        if ($fileid == 0){
            return FALSE;
        }
        $new_version_available = FALSE;
        $lookup_index = 0;
        $url = 'https://www.zen-cart.com/downloads.php?do=versioncheck' . '&id=' . (int) $fileid;
        $data = json_decode(file_get_contents($url), true);
        if (!$data || !is_array($data)) return false;
        // compare versions
        if (version_compare($data[$lookup_index]['latest_plugin_version'], $version_string_to_check) > 0) {
            $new_version_available = TRUE;
        }
        // check whether present ZC version is compatible with the latest available plugin version
        if (!in_array('v' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, $data[$lookup_index]['zcversions'])) {
            $new_version_available = FALSE;
        }
        if ($version_string_to_check == true) {
            return $data[$lookup_index];
        } else {
            return FALSE;
        }
    }
}
