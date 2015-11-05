<?php

/**
 * PriceMotion Observer
 *
 * @package        Pricemotion
 * @copyright    Aim4Solutions s.r.l.
 * @author        Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Model_Observer
{

    const XML_PATH_EMAIL_TEMPLATE = 'pricemotion_options/emails/template';

    public static $display_log = false;
    public static $coll_count = 1;
    public static $email_report = false;

    /**
     * Get pricemotion messages and push them to adminhtml notification
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function sendMessages(Mage_Cron_Model_Schedule $schedule)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, Mage::helper('pricemotion')->getMessagesUrl() . Mage::helper('pricemotion')->getSerialUrl() . Mage::helper('pricemotion')->getSerial());
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //Timeout after 25 sec to prevent slow loading
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        $response = curl_exec($ch);
        curl_close($ch);
        $messages_xml = simplexml_load_string($response);
        if ($response !== false && $messages_xml !== false) {
            $feedData = array();
            foreach ($messages_xml->message as $message) {
                $feedData[] = array(
                    "title" => "PriceMotion - " . $message->title,
                    "description" => $message->safe_body,
                    "severity" => 4,
                    "date_added" => date("Y-m-d H:i:s")
                );
            }
            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }
        }

    }

    /**
     * Function that is called when catalog_product_collection_load_before is triggered
     * It is used to add new attributes to the product collection
     * @param Varien_Event_Observer $observer
     */
    public function addToProductCollection(Varien_Event_Observer $observer)
    {
        $collection = $observer->getCollection();
        if (Mage::helper("pricemotion")->getLowestPriceAtt()) {
            $collection->addAttributeToSelect(Mage::helper("pricemotion")->getLowestPriceAtt());
        }
        if (Mage::helper("pricemotion")->getPriceDiffAtt()) {
            $collection->addAttributeToSelect(Mage::helper("pricemotion")->getPriceDiffAtt());
        }

        $collection->addAttributeToSelect('enable_pricemotion');

    }

    /**
     *
     * Callback for the product collection iterator
     * @param array $args
     *
     */
    public static function updateAttributesCallback($args)
    {
        self::$coll_count = self::$coll_count + 1;
        $product = $args;
        $log_file = "pricemotion_log_update_attributes_callback" . date("Y-m-d-h") . ".log";
        Mage::log('Loaded product ' . $product->getId(), null, $log_file);
        if ($product->getStatus() != "1" || $product->getEnablePricemotion() != "1") {
            Mage::log('SKIPPED: product disabled', null, $log_file);
        } else {
            $pricemotion = Mage::helper('pricemotion')->getPricemotion($product, false, false, true);
            $lowestprice_att = Mage::helper("pricemotion")->getLowestPriceAtt();
            $difference_att = Mage::helper("pricemotion")->getPriceDiffAtt();
            echo "Starting productnr : " . self::$coll_count . " : " . "Product: " . $product->getSku() . " : ";
            self::$email_report .= "Starting productnr : " . self::$coll_count . " : " . "Product: " . $product->getSku() . " : ";
            if ($pricemotion['success']) {
                $lowest_price = $pricemotion['info']['lowest'];
                if ($lowest_price) {
                    $product->setData($lowestprice_att, $lowest_price);
                    if ($product->getSpecialPrice() && $product->getSpecialPrice() > 0) {
                        $price = $product->getSpecialPrice();
                    } else {
                        $price = $product->getPrice();
                    }
                    $difference = number_format((($price - $lowest_price) * 100) / $lowest_price, 2, ".", "");
                    Mage::log("Price: {$price}; Lowest price: {$lowest_price}; Difference: {$difference}", null, $log_file);
                    //if(self::$display_log) {
                    echo " Price: {$price}; Lowest price: {$lowest_price}; Difference: {$difference}\r\n";
                    self::$email_report .= " Price: {$price}; Lowest price: {$lowest_price}; Difference: {$difference}\r\n";
                    //}
                    $product->setData($difference_att, $difference);
                    $product->save();
                } else {
                    Mage::log('No lowest price', null, $log_file);
                }
            } else {
                Mage::log('Pricemotion error', null, $log_file);
                //if(self::$display_log) {
                echo "Pricemotion error: " . str_replace(array("\r\r\n", "\r", "\r\n", "\t"), ' ', strip_tags($pricemotion['message'])) . "\r\n";
                self::$email_report .= "Pricemotion error: " . str_replace(array("\r\r\n", "\r", "\r\n", "\t"), ' ', strip_tags($pricemotion['message'])) . "\r\n";
                //}
                Mage::log($pricemotion, null, $log_file);
            }
        }

    }

    /**
     * Update product attributes (lowestprice and price_difference)
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function updateAttributes($schedule = false, $display_log = false, $start = false, $end = false, $sku = false)
    {
        if ($display_log) {
            self::$display_log = true;
        }
        $log_file = "pricemotion_log_update_attributes" . date("Y-m-d-h") . ".log";
        Mage::log('Update attributes cron started', null, $log_file);
        $lowestprice_att = Mage::helper("pricemotion")->getLowestPriceAtt();
        $difference_att = Mage::helper("pricemotion")->getPriceDiffAtt();
        $name_att = Mage::helper("pricemotion")->getNameAtt();

        if ($lowestprice_att && $difference_att) {

            echo "Started Loading collection\r\n";
            self::$email_report = "Started Loading collection\r\n";
            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*');
            if ($sku !== false) {
                $collection->addAttributeToFilter('sku', array('eq' => $sku));
            }
            if ($start !== false && $end !== false) {
                $collection->getSelect()->limit($start, $end);
            }
            $collection->walk("A4s_Pricemotion_Model_Observer::updateAttributesCallback");
            echo "Collection walking ended\r\n";
            self::$email_report .= "Collection walking ended\r\n";
        } else {
            Mage::log('Attributes not set', null, $log_file);
        }
        Mage::log("Update attributes cron ended\r\n", null, $log_file);

    }

    /**
     * Function that is called when catalog_product_save_after is triggered
     * @param Varien_Event_Observer $observer
     */
    public function saveRulesData(Varien_Event_Observer $observer)
    {

        if ($post = Mage::app()->getRequest()->getPost()) {
            $product = Mage::registry('product');
            if (isset($product) && $product && isset($post['pricemotion'])) {
                if ($post['pricemotion']['price_rules'] != 0) {

                    $post_data = $post['pricemotion'];
                    $cost_margin_status = ($post_data['cost_margin_enabled'] == 'on') ? 1 : 0;
                    $only_email = ($post_data['only_email'] == 'on') ? 1 : 0;

                    $rule_model = Mage::getModel('pricemotion/rules')->load($product->getId(), 'product_id');
                    $rule_model->setData('product_id', $product->getId());
                    $rule_model->setData('cost_margin_percent', $post_data['cost_margin']);
                    $rule_model->setData('cost_margin_status', $cost_margin_status);
                    $rule_model->setData('below_average_percent', $post_data['below_average']);
                    $rule_model->setData('in_top_value', $post_data['top']);
                    $rule_model->setData('only_email', $only_email);
                    $rule_model->setData('status', $post_data['price_rules']);
                    $rule_model->save();

                    $resource = Mage::getSingleton('core/resource');
                    $conn = $resource->getConnection('core_write');
                    $stmt = $conn->prepare("
                        DELETE FROM `" . $resource->getTableName('pricemotion/rules') . "`
                        WHERE
                            product_id = :product_id
                            AND rule_id <> :rule_id
                    ");
                    $stmt->execute(array(
                        'product_id' => $product->getId(),
                        'rule_id'    => $rule_model->getId(),
                    ));
                } elseif ($post['pricemotion']['price_rules'] == 0) {
                    $rule_model = Mage::getModel('pricemotion/rules')->load($product->getId(), 'product_id');
                    $rule_model->delete();
                }
            }
        }

    }

    /**
     * Get pricemotion price rules and adjust the prices
     * @param Mage_Cron_Model_Schedule $schedule
     */
    public function setPrices($schedule)
    {
        error_reporting(E_ALL);
        ini_set("display_errors", "On");
        $log_file = "pricemotion_log_setprices" . date("Y-m-d-h") . ".log";
        echo 'Cron started\r\n';
        Mage::log('Cron started', null, $log_file);

        $cron_email = "";
        $collection = Mage::getModel('pricemotion/rules')->getCollection();
        $rules_loaded = count($collection);
        echo 'Collection loaded: ' . count($collection) . ' rules\r\n';
        Mage::log('Collection loaded: ' . count($collection) . ' rules', null, $log_file);
        $special_price = Mage::helper("pricemotion")->getSpecialPrice();
        $active_rules = count($collection);
        $report_table_items = array();

        if (count($collection)) {
            $cron_email .= "<table border='1'>
			<thead>
			    <tr>
			      <th>Product ID</th>
			      <th>" . Mage::helper('pricemotion')->__("Product name") . "</th>
			      <th>" . Mage::helper('pricemotion')->__("Rule type") . "</th>
			      <th>" . Mage::helper('pricemotion')->__("Old price") . "</th>
			      <th>" . Mage::helper('pricemotion')->__("New price") . "</th>
			    </tr>
			  </thead><tbody>";
        }

        foreach ($collection as $rule) {

            if ($rule->getStatus() == A4s_Pricemotion_Model_Rules::RULE_DISABLED) {
                echo 'Rule ' . $rule->getId() . ' disabled\r\n';
                Mage::log('Rule ' . $rule->getId() . ' disabled', null, $log_file);
                $active_rules--;
                continue;
            }

            $product = Mage::getModel('catalog/product')->load($rule->getProductId());

            if(!$product->getEnablePricemotion()) {
                echo 'Product with rule id ' . $rule->getId() . ' is disabled\r\n';
                $active_rules--;
                continue;
            }

            echo 'Loading pricemotion data for product ' . $rule->getProductId() . '\r\n';
            Mage::log('Loading pricemotion data for product ' . $rule->getProductId(), null, $log_file);
            $pricemotion = Mage::helper('pricemotion')->getPricemotion($product, false, false);

            if ($pricemotion['success']) {
                echo 'Pricemotion data loaded\r\n';
                Mage::log('Pricemotion data loaded', null, $log_file);
                //$cron_email .= "Pricemotion data loaded for product id:  ". $product->getId() ."; name: ". $product->getName() ."\r\n";
                if ($special_price && $product->getSpecialPrice() && $product->getSpecialPrice() > 0) {
                    $product_price = $product->getSpecialPrice();
                } else {
                    $product_price = $product->getPrice();
                }
                $min_price = 0;
                echo 'Checking margin on cost\r\n';
                Mage::log('Checking margin on cost', null, $log_file);
                if ($rule->getCostMarginStatus()) {
                    $cost_price_att = Mage::helper("pricemotion")->getCostPriceAtt();
                    $cost_price = $product->getData($cost_price_att);
                    if (!$cost_price) {
                        echo 'Rule skipped: no cost price\r\n';
                        Mage::log('Rule skipped: no cost price', null, $log_file);
                        $report_table_items[] = array(
                            "id" => $product->getId(),
                            "name" => $product->getName(),
                            "error" => Mage::helper('pricemotion')->__('Cost price not set')
                        );
                        continue;
                    }
                    $cost_margin = $rule->getCostMarginPercent();
                    $min_price = $cost_price + ($cost_price * ($cost_margin / 100));
                    $min_price = number_format($min_price, 4, '.', '');
                }

                //Appling price rules
                switch ($rule->getStatus()) {

                    case A4s_Pricemotion_Model_Rules::LOWEST_PRICE:
                        echo 'Rule lowest price\r\n';
                        Mage::log('Rule lowest price', null, $log_file);
                        sort($pricemotion['prices']);
                        if (count($pricemotion['prices'])) {
                            $new_price = $pricemotion['prices'][0];
                            if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                $new_price = round($new_price);
                            }

                            $new_price = number_format($new_price, 4, '.', '');
                            echo 'New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price . '\r\n';
                            Mage::log('New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price, null, $log_file);

                            if ($new_price > $min_price) {
                                echo 'New price ' . $new_price . ' set\r\n';
                                Mage::log('New price ' . $new_price . ' set', null, $log_file);
                                if ($new_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
									<td>" . $product->getId() . "</td>
									<td>" . $product->getName() . "</td>
									<td>" . Mage::helper('pricemotion')->__("lowest price") . "</td>
									<td>" . $product_price . "</td>
									<td>" . $new_price . "</td>
									</tr>";
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "lowest price",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($new_price);
                                        } else {
                                            $product->setPrice($new_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "lowest price",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }

                            } elseif ($min_price > 0) {
                                echo 'Min price set\r\n';
                                Mage::log('Min price set', null, $log_file);
                                //$cron_email .= "Min price set\r\n";
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $min_price = round($min_price);
                                }
                                if ($min_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
                            		<td>" . $product->getId() . "</td>
                            		<td>" . $product->getName() . "</td>
                            		<td>" . Mage::helper('pricemotion')->__("lowest price") . "</td>
                            		<td>" . $product_price . "</td>
                            		<td>" . $min_price . "</td>
                            		</tr>";

                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "lowest price",
                                        "price" => $product_price,
                                        "new_price" => $min_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($min_price);
                                        } else {
                                            $product->setPrice($min_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "lowest price",
                                        "price" => $product_price,
                                        "new_price" => $min_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }
                            } else {
                                echo 'No price set (1)\r\n';
                                Mage::log('No price set (1)', null, $log_file);
                                $report_table_items[] = array(
                                    "id" => $product->getId(),
                                    "name" => $product->getName(),
                                    "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                );
                                //$cron_email .= "No price was set\r\n";
                            }
                        } else {
                            echo 'No price set (2)\r\n';
                            Mage::log('No price set (2)', null, $log_file);
                            $report_table_items[] = array(
                                "id" => $product->getId(),
                                "name" => $product->getName(),
                                "error" => Mage::helper('pricemotion')->__('Pricemotion data not available')
                            );
                            //$cron_email .= "No price was set\r\n";
                        }
                        break;

                    case A4s_Pricemotion_Model_Rules::RULE_BELOW_AVERAGE:
                        echo 'Rule below average\r\n';
                        Mage::log('Rule below average', null, $log_file);
                        //$cron_email .= "Rule 'below avegare'\r\n";
                        if ($pricemotion['info']['average']) {
                            $below_average_percent = $rule->getBelowAveragePercent();
                            $new_price = $pricemotion['info']['average'] - ($pricemotion['info']['average'] * ($below_average_percent / 100));
                            $new_price = number_format($new_price, 4, '.', '');
                            echo 'New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price . '\r\n';
                            Mage::log('New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price, null, $log_file);
                            //$cron_email .= 'New price: ' . $new_price . '; Min price: ' . $min_price . "\r\n";
                            if ($new_price > $min_price) {
                                echo 'New price set\r\n';
                                Mage::log('New price set', null, $log_file);
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $new_price = round($new_price);
                                }
                                //$cron_email .= "New price set\r\n";
                                if ($new_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
									<td>" . $product->getId() . "</td>
									<td>" . $product->getName() . "</td>
									<td>" . Mage::helper('pricemotion')->__("below average") . "</td>
									<td>" . $product_price . "</td>
									<td>" . $new_price . "</td>
									</tr>";
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "onder de gemiddelde prijs",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($new_price);
                                        } else {
                                            $product->setPrice($new_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "onder de gemiddelde prijs",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }
                            } elseif ($min_price > 0) {
                                echo 'Min price set\r\n';
                                Mage::log('Min price set', null, $log_file);
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $min_price = round($min_price);
                                }
                                //$cron_email .= "Min price set\r\n";
                                if ($min_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
                            		<td>" . $product->getId() . "</td>
                            		<td>" . $product->getName() . "</td>
                            		<td>" . Mage::helper('pricemotion')->__("below average") . "</td>
                            		<td>" . $product_price . "</td>
                            		<td>" . $min_price . "</td>
                            		</tr>";
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "onder de gemiddelde prijs",
                                        "price" => $product_price,
                                        "new_price" => $min_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($min_price);
                                        } else {
                                            $product->setPrice($min_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "onder de gemiddelde prijs",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }
                            } else {
                                echo 'No price set (1)\r\n';
                                Mage::log('No price set (1)', null, $log_file);
                                $report_table_items[] = array(
                                    "id" => $product->getId(),
                                    "name" => $product->getName(),
                                    "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                );
                                //$cron_email .= "No price was set\r\n";
                            }

                        } else {
                            echo 'No price set (2)\r\n';
                            Mage::log('No price set (2)', null, $log_file);
                            $report_table_items[] = array(
                                "id" => $product->getId(),
                                "name" => $product->getName(),
                                "error" => Mage::helper('pricemotion')->__('Pricemotion data not available')
                            );
                            //$cron_email .= "No price was set\r\n";
                        }
                        break;
                    case A4s_Pricemotion_Model_Rules::RULE_IN_TOP:
					case A4s_Pricemotion_Model_Rules::RULE_MATCH_TOP:
						if ($rule->getStatus()==A4s_Pricemotion_Model_Rules::RULE_IN_TOP) {
							echo 'Rule in top\r\n';
							Mage::log('Rule in top', null, $log_file);
						} else {
							echo 'Rule match top\r\n';
							Mage::log('Rule match top', null, $log_file);
						}
                        //$cron_email .= "Rule 'in top'\r\n";
                        sort($pricemotion['prices']);
                        if (count($pricemotion['prices'])) {
                            $array_position = $rule->getInTopValue() - 1;
                            if (isset($pricemotion['prices'][$array_position])) {
                                $top_price = $pricemotion['prices'][$array_position];
								if ($rule->getStatus()==A4s_Pricemotion_Model_Rules::RULE_IN_TOP) {
									//We go below number x
									$new_price = $top_price - 0.01;
								} else { 
									//We match the price
									$new_price = $top_price; 
								}
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $new_price = floor($new_price);
                                }
                            } else {
								//Top is not big enough, make it 1 cent more expensive
                                $top_price = end($pricemotion['prices']);
                                $new_price = $top_price + 0.01;
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $new_price = ceil($new_price);
                                }
                            }
                            //$product_price=str_replace(',', '', $product_price);
                            $new_price = number_format($new_price, 4, '.', '');
                            echo 'New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price . '\r\n';
                            Mage::log('New price: ' . $new_price . '; Min price: ' . $min_price . '; Old price: ' . $product_price, null, $log_file);
                            //$cron_email .= 'New price: ' . $new_price . '; Min price: ' . $min_price . "\r\n";
                            if ($new_price > $min_price) {
                                echo 'New price ' . $new_price . ' set\r\n';
                                Mage::log('New price ' . $new_price . ' set', null, $log_file);
                                if ($new_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
									<td>" . $product->getId() . "</td>
									<td>" . $product->getName() . "</td>
									<td>" . Mage::helper('pricemotion')->__("in top") . "</td>
									<td>" . $product_price . "</td>
									<td>" . $new_price . "</td>
									</tr>";
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "top",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($new_price);
                                        } else {
                                            $product->setPrice($new_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "top",
                                        "price" => $product_price,
                                        "new_price" => $new_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }

                            } elseif ($min_price > 0) {
                                echo 'Min price set\r\n';
                                Mage::log('Min price set', null, $log_file);
                                //$cron_email .= "Min price set\r\n";
                                if (Mage::helper('pricemotion')->isRoundedPrice()) {
                                    $min_price = round($min_price);
                                }
                                if ($min_price != $product_price) {
                                    $change_price = true;
                                    $status = Mage::helper('pricemotion')->__("Prijs gewijzigd");
                                    if ($rule->getOnlyEmail()) {
                                        $change_price = false;
                                        $status = Mage::helper('pricemotion')->__("Notificatie");
                                    }
                                    $cron_email .= "<tr>
                            		<td>" . $product->getId() . "</td>
                            		<td>" . $product->getName() . "</td>
                            		<td>" . Mage::helper('pricemotion')->__("in top") . "</td>
                            		<td>" . $product_price . "</td>
                            		<td>" . $min_price . "</td>
                            		</tr>";

                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "top",
                                        "price" => $product_price,
                                        "new_price" => $min_price,
                                        "min_price" => $min_price,
                                        "status" => $status
                                    );
                                    if ($change_price) {
                                        if ($special_price) {
                                            $product->setSpecialPrice($min_price);
                                        } else {
                                            $product->setPrice($min_price);
                                        }
                                        $product->save();
                                    }
                                } else {
                                    $report_table_items[] = array(
                                        "id" => $product->getId(),
                                        "name" => $product->getName(),
                                        "type" => "top",
                                        "price" => $product_price,
                                        "new_price" => $min_price,
                                        "min_price" => $min_price,
                                        "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                    );
                                }
                            } else {
                                echo 'No price set (1)\r\n';
                                Mage::log('No price set (1)', null, $log_file);
                                $report_table_items[] = array(
                                    "id" => $product->getId(),
                                    "name" => $product->getName(),
                                    "error" => Mage::helper('pricemotion')->__('Geen wijziging')
                                );
                                //$cron_email .= "No price was set\r\n";
                            }
                        } else {
                            echo 'No price set (2)\r\n';
                            Mage::log('No price set (2)', null, $log_file);
                            $report_table_items[] = array(
                                "id" => $product->getId(),
                                "name" => $product->getName(),
                                "error" => Mage::helper('pricemotion')->__('Pricemotion data not available')
                            );
                            //$cron_email .= "No price was set\r\n";
                        }
                        break;
                    default:
                        break;
                }

            } else {
                echo 'Pricemotion data not loaded\r\n';
                Mage::log('Pricemotion data not loaded', null, $log_file);
                //$cron_email .= "Pricemotion data could not be loaded for product ". $rule->getProductId() ."\r\n";
                /*$cron_email .= "<tr>
                <td>".$rule->getProductId()."</td>
                <td colspan='4'>". Mage::helper('pricemotion')->__("Pricemotion data could not be loaded") ."</td>
                </tr>";*/
                $report_table_items[] = array(
                    "id" => $product->getId(),
                    "name" => $product->getName(),
                    "error" => Mage::helper('pricemotion')->__('')
                );
            }
            //$cron_email .= "\r\n";
        }

        $cron_email = Mage::helper('pricemotion')->__("Cron Started") . "<br/>" . Mage::helper('pricemotion')->__("Loaded %s rules", $active_rules) . "<br/>" . $cron_email;

        echo "Cron ended\r\n";
        Mage::log("Cron ended\r\n", null, $log_file);
        //$cron_email .= "Cron ended\r\n";
        $cron_email .= "</tbody></table>";

        if (Mage::helper("pricemotion")->getEmailsEnabled() && Mage::helper("pricemotion")->getEmailTo() && $active_rules > 0) {
            $email = Mage::helper("pricemotion")->getEmailTo();
            $sender = array('name' => Mage::getStoreConfig("trans_email/ident_general/name"),
                'email' => Mage::getStoreConfig("trans_email/ident_general/email"));

            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);

            $storeId = Mage::app()->getStore()->getId();

            try {
                Mage::getModel('core/email_template')
                    ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId),
                        $sender,
                        $email,
                        "",
                        array(
                            "date" => date("Y-m-d H:i:s"),
                            "rules_loaded" => $active_rules,
                            "items" => $report_table_items
                        )
                    );

                $translate->setTranslateInline(true);
                echo "Mail Sent!";
            } catch (Exception $e) {
                echo "Mail error: ";
                echo $e->getMessage();
            }

        }

    }

}

