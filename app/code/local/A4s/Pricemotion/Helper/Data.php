<?php 

/**
 * PriceMotion helper
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Helper_Data extends Mage_Core_Helper_Abstract {
	
	/**
	 * Url path used to get the ean from the name. The base url will be fetch from config
	 */
	const NAME_TO_EAN_URL = "name_to_ean.php";
	
	/**
	 * Url path used to get the product info based on the ean.  The base url will be fetch from config
	 */
	const EAN_URL = "&ean=";
    
    /**
     * Url path for the name.  The base url will be fetch from config
     */
    const NAME_URL = "&name=";
	
	/**
	 * String to add the serial to the url
	 */
	const SERIAL_URL = "?serial=";
	
	/**
	 * Shortcut to get the NAME_TO_EAN_URL constant
	 * @return string
	 */
    public static function getNameToEanUrl() {
        return self::NAME_TO_EAN_URL;
    }
	
	/**
	 * Shortcut to get the EAN_URL constant
	 * @return string
	 */
    public static function getEanUrl() {
        return self::EAN_URL;
    }
	
	/**
	 * Shortcut to get the SERIAL_URL constant
	 * @return string
	 */
    public static function getSerialUrl() {
        return self::SERIAL_URL;
    }
    
	/**
	 * Get the service url
	 * @return string
	 */
	public function getServiceUrl() {
        return Mage::getStoreConfig('pricemotion_options/default/url');
    }
	
	/**
	 * Get the messages url
	 * @return string
	 */
	public function getMessagesUrl() {
        return Mage::getStoreConfig('pricemotion_options/default/messages_url');
    }
	
    /**
     * Get the attributes in which the EAN is saved
     * @return string
     */
	public function getEanAtt() {
        return Mage::getStoreConfig('pricemotion_options/default/ean_att');
    }
	
	
    /**
     * Get the attributes in which the name is saved
     * @return string
     */
	public function getNameAtt() {
        return Mage::getStoreConfig('pricemotion_options/default/name_att');
    }
	
    /**
     * Get the attributes in which the price cost is saved
     * @return string
     */
	public function getCostPriceAtt() {
        return Mage::getStoreConfig('pricemotion_options/default/cost_price_att');
    }
	
	/**
     * Get the attributes in which the lowest price is saved
     * @return string
     */
    public function getLowestPriceAtt() {
        return Mage::getStoreConfig('pricemotion_options/default/lowestprice_att');
    }
    
    /**
     * Get the attributes in which the price difference is saved
     * @return string
     */
    public function getPriceDiffAtt() {
        return Mage::getStoreConfig('pricemotion_options/default/pricediff_att');
    }
	
	/**
     * Check if prices should be rounded or not.
     * @return bool
     */
    public function isRoundedPrice() {
        return Mage::getStoreConfig('pricemotion_options/default/rounded_prices');
    }
    
    /**
     * Get the Pricemotion serial
     * @return string
     */
	public function getSerial() {
        return Mage::getStoreConfig('pricemotion_options/default/serial');
    }
	
    /**
     * Get emails enabled
     * @return string
     */
	public function getEmailsEnabled() {
        return Mage::getStoreConfig('pricemotion_options/emails/enabled');
    }
	
    /**
     * Get send email to
     * @return string
     */
	public function getEmailTo() {
        return Mage::getStoreConfig('pricemotion_options/emails/recipient_email');
    }
    /**
     * Get special price config
     * @return boolean
     */
	public function getSpecialPrice() {
        return Mage::getStoreConfig('pricemotion_options/default/special_price');
    }
    
    /**
     * Get the attribute in which the company names that are to be escaped is saved
     * @return string
     */
    public function getEscNames() {
        return Mage::getStoreConfig('pricemotion_options/default/escape_names');
    }
    
    /**
     * Get pricemotion information
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getPricemotion($product, $with_currency_symbol = true, $with_seller = true, $display_log = false) {
    	$ean_att = $this->getEanAtt();
    	$name_att = $this->getNameAtt();
    	$service_url = $this->getServiceUrl();
    	
    	$return = array('success' => false);
    	
    	if($ean_att && ($ean = $product->getData($ean_att)) && strlen($ean) > 8) {
		if($display_log) {
			//echo " |Request URL: " . $this->getServiceUrl() . self::SERIAL_URL . $this->getSerial() . self::EAN_URL . urlencode($ean) . "| ";
		}
    		$xml_request = $this->getXml($ean);
    	} elseif($name_att && ($pname = $product->getData($name_att)) && strlen($ean) > 8) {
		if($display_log) {
                        //echo " |Request URL: " . $this->getServiceUrl() . self::NAME_TO_EAN_URL . self::SERIAL_URL . $this->getSerial() . self::NAME_URL . urlencode($pname) . "| ";
                }
    		$xml_request = $this->getXml(null, $pname);
    	} else {
			$xml_request = array();
			$xml_request['success'] = false;
			$xml_request['message'] = $this->__('Service not available') . ' (Error: E003)';
		}
    	if(!$xml_request['success']) {
    		$return['message'] = $xml_request['message'];
    	} else {
    		
    		if($xml_request['xml']->info->name && $xml_request['xml']->info->ean) {
    			$return['success'] = true;
    			$return['info'] = array('name' => (string)$xml_request['xml']->info->name, 'ean' => (string)$xml_request['xml']->info->ean);
    			$return['prices'] = array();
    			$highest = 0;
    			$lowest = 0;
    			$average = 0;
    			foreach ($xml_request['xml']->prices->bezorg->item as $price_item) {
    				$price = str_replace(",", ".", (string)$price_item->price);
    				if($price > $highest) {
    					$highest = $price;
    				}
    				if($price < $lowest || $lowest == 0) {
    					$lowest = $price;
    				}
    				$average += $price;
    				if($with_currency_symbol) {
    				    $return['prices'][] = array(
    				    			'seller' => (string)$price_item->seller, 
    				    			'price' => Mage::helper('core')->currency($price, true, false),
    				    			'cleanPrice' => number_format($price, 2, ".", "")
    				    		);
    				} elseif($with_seller) {
    				    $return['prices'][] = array(
    				    			'seller' => (string)$price_item->seller, 
    				    			'price' => number_format($price, 2, ".", ""),
    				    			'cleanPrice'=> number_format($price, 2, ".", "")
    				    		);
    				} else {
                                    if($this->getEscNames()) {
                                        $escs_array = array() + explode(',',$this->getEscNames());
                                        if(in_array($price_item->seller, $escs_array)){
                                            continue;
                                        }                         
                                    }          
    				    $return['prices'][] = number_format($price, 2, ".", "");
    				}
    			}
    			
    			if($with_currency_symbol || $with_seller) {
					usort($return['prices'], array('self', 'cmpByPrice'));
				} else {
					sort($return['prices']);
				}
    			
    			$average = $average / count($xml_request['xml']->prices->bezorg->item);
                if($with_currency_symbol) {
                    $return['info']['average'] = Mage::helper('core')->currency($average, true, false);
                    $return['info']['lowest'] = Mage::helper('core')->currency($lowest, true, false);
                    $return['info']['highest'] = Mage::helper('core')->currency($highest, true, false);
                } else {
                    $return['info']['average'] = number_format($average, 2, ".", "");
                    $return['info']['lowest'] = number_format($lowest, 2, ".", "");
                    $return['info']['highest'] = number_format($highest, 2, ".", "");
                }
    			
    			
    		} else {
    			$return['message'] = $this->__('Service not available') . ' (Error: E004)';
    		}
    		
    	}
    	return $return;
    }
    
    
    /**
     * Compare array by price
     * @param array $a
     * @param array $b
     * @return int
     */
    public function cmpByPrice($a, $b) {
		/*  
		if(mb_substr($a['price'], 0, 1, 'UTF-8') == Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol()) {
			$price_a = mb_substr($a['price'], 1, 20, 'UTF-8');
		} else {
			$price_a = $a['price'];
		}
		if(mb_substr($b['price'], 0, 1, 'UTF-8') == Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol()) {
			$price_b = mb_substr($b['price'], 1, 20, 'UTF-8');
		} else {
			$price_b = $b['price'];
		}
		*/
    		$price_a=$a['cleanPrice'];
    		$price_b=$b['cleanPrice'];
		$diff = $price_a - $price_b;
		if($diff < 0) {
			return -1;
		} elseif($diff > 0) {
			return 1;
		}
		return 0;
	}
    
	/**
     * Get pricemotion xml
     * @param string $ean
     * @param string $name
     * @return array
     */
    public function getXml($ean = null, $name = null) {
    	
    	$return = array("success" => false);

    	if($ean !== null) {
    		
    		$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->getServiceUrl() . self::SERIAL_URL . $this->getSerial() . self::EAN_URL . urlencode($ean));
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//Timeout after 5 sec to prevent slow loading
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$response = curl_exec($ch);
			
			curl_close($ch);
			
			if($response === false) {
				$return['message'] = $this->__('Service not available') . ' (Error: E001a)';
			} else {
				$response_xml = simplexml_load_string(str_replace('&', '&amp;', $response));
				
	    		if($response_xml === false) {
					$return['message'] = $this->__('Service not available') . ' (Error: E002) Response: ' . $response;
				} elseif($response_xml->getName() == 'error') {
					$return['message'] = $this->__('Service not available') . ": " . (string)$response_xml . ' (Error: E111)';
				} else {
					$return['success'] = true;
					$return['xml'] = $response_xml;
				}
			}
    		
    	} elseif($name !== null) {
    		$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->getServiceUrl() . self::NAME_TO_EAN_URL . self::SERIAL_URL . $this->getSerial() . self::NAME_URL . urlencode($name));
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//Timeout after 5 sec to prevent slow loading
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			$response = curl_exec($ch);
			curl_close($ch);
			
			$name_to_ean_xml = simplexml_load_string($response);
			
    		if($response === false) {
				//print($this->getServiceUrl() . self::NAME_TO_EAN_URL . self::SERIAL_URL . $this->getSerial() . self::NAME_URL . urlencode($name));
				$return['message'] = $this->__('Service not available') . ' (Error: E001b)';
				
			} elseif($name_to_ean_xml === false) {
				$return['message'] = $this->__('Service not available') . ' (Error: E002) Response: ' . $response;
			} elseif($name_to_ean_xml->getName() == 'error') {
				$return['message'] = $this->__('Service not available') . ": " . (string)$name_to_ean_xml . ' (Error: E111)';
			} else {
				$ean_array = (string)$name_to_ean_xml->info->ean;
                
                $ean_array = explode(",", $ean_array);
                $ean = $ean_array[0];
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->getServiceUrl() . self::SERIAL_URL . $this->getSerial() . self::EAN_URL . urlencode($ean));
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				//Timeout after 5 sec to prevent slow loading
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				$response = curl_exec($ch);
				curl_close($ch);
				
				$response_xml = simplexml_load_string($response);
				
				if($response === false) {
					$return['message'] = $this->__('Service not available') . ' (Error: E001c)';
				} elseif($response_xml === false) {
					$return['message'] = $this->__('Service not available') . ' (Error: E002) Response: ' . $response;
				} elseif($response_xml->getName() == 'error') {
					$return['message'] = $this->__('Service not available') . ": " . (string)$response_xml . ' (Error: E111)';
				} else {
					$return['success'] = true;
					$return['xml'] = $response_xml;
				}
			}
    	} else {
    		$return['message'] = $this->__('Service not available') . ' (Error: E003)';
    	}
    	
    	return $return;
    	
    }
	
}