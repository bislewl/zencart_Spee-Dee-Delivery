<?php
/*
 
  Released under the GNU General Public License
*/

  class speedee {
    var $code, $title, $description, $icon, $enabled;

// class constructor
    function speedee() {
      global $order;

      $this->code = 'speedee';
      $this->title = MODULE_SHIPPING_SPEEDEE_TEXT_TITLE;
      $this->description = MODULE_SHIPPING_SPEEDEE_TEXT_DESCRIPTION;
      $this->mark_up = MODULE_SHIPPING_SPEEDEE_MARK_UP;
      $this->sort_order = MODULE_SHIPPING_SPEEDEE_SORT_ORDER;
      $this->icon = DIR_WS_IMAGES . 'icons/speedee_round.jpg';
      $this->tax_class = MODULE_SHIPPING_SPEEDEE_TAX_CLASS;
      $this->enabled = ((MODULE_SHIPPING_SPEEDEE_STATUS == 'True') ? true : false);

    }

// class methods
    function quote($method = '') {
      global $order, $shipping_weight,$shipping_num_boxes, $total_weight, $boxcount, $handling_cp, $cart;

      $srcFSA = substr(SHIPPING_ORIGIN_ZIP, 0, 5);
      $desFSA = substr($order->delivery['postcode'], 0, 5);


      $request = join('&', array('source_zip=' . $srcFSA,
                                 'dest_zip=' . $desFSA,
                                 'weight=' . intval($shipping_weight),
                                 'account=' . MODULE_SHIPPING_SPEEDEE_ACCOUNT));



		
        $body = file_get_contents('http://www.speedeedelivery.com/cgi-bin/interface.cgi?' . $request);
        $return_array = explode(",", $body);
         if ($return_array[0] != '') {
        $base_shipping_cost = $return_array[0];
        $MarkUp = MODULE_SHIPPING_SPEEDEE_MARK_UP;
	$FuelSurcharge_base = MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE;
	$FuelSurcharge = 1 + $FuelSurcharge_base / 100 ;
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
        $this->quotes['tax'] =  zen_get_tax_rate($this->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
      }

      if (zen_not_null($this->icon)) $this->quotes['icon'] = zen_image($this->icon, $this->title, null, null, 'align="middle"');

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
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable SPEEDEE Shipping', 'MODULE_SHIPPING_SPEEDEE_STATUS', 'True', 'Do you want to offer SPEEDEE rate shipping?', '1', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_SHIPPING_SPEEDEE_TAX_CLASS', '0', 'Use the following tax class on the shipping fee.', '4', '0', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Mark Up', 'MODULE_SHIPPING_SPEEDEE_MARK_UP', '1', 'Use the following mark-up on the shipping list fees.', '7', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_SHIPPING_SPEEDEE_SORT_ORDER', '0', 'Sort order of display.', '9', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Fuel Surcharge Rate', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE', '16.8', 'KEEP UP TO DATE: Fuel Surcharge Rate Enter as a percentage without the % sign (eg. 16.8).', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Account Number', 'MODULE_SHIPPING_SPEEDEE_ACCOUNT', '1011', 'This is your Spee-Dee Deilvery Account Number', '2', '0', now())");
    }

    function remove() {
	  global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_SHIPPING_SPEEDEE_STATUS', 'MODULE_SHIPPING_SPEEDEE_TAX_CLASS', 'MODULE_SHIPPING_SPEEDEE_MARK_UP', 'MODULE_SHIPPING_SPEEDEE_SORT_ORDER', 'MODULE_SHIPPING_SPEEDEE_FUEL_SURCHARGE', 'MODULE_SHIPPING_SPEEDEE_ACCOUNT');
    }
  }
?>
