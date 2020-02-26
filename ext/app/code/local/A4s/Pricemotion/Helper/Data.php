<?php

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
    private function getEscNames() {
        return Mage::getStoreConfig('pricemotion_options/default/escape_names');
    }

    /**
     * Get pricemotion information
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getPricemotion($product, $with_currency_symbol = true, $with_seller = true, $display_log = false) {
    	$ean_att = $this->getEanAtt();
    	$service_url = $this->getServiceUrl();

    	$return = array('success' => false);

        if (!$ean_att) {
            return $this->error($this->__("No EAN attribute is configured"));
        }

        $ean = $product->getData($ean_att);
        if (!$ean) {
            return $this->error($this->__("An EAN is required to retrieve prices"));
        }

        $ean = trim($ean);
        if (!preg_match('/^0*[0-9]{8,14}$/', $ean)) {
            return $this->error($this->__("The EAN is invalid (it should consist of 8 to 14 digits)"));
        }

        $xml_request = $this->getXml($ean);

    	if(!$xml_request['success']) {
    	    return $this->error($xml_request['message']);
    	}

        if(!$xml_request['xml']->info->name || !$xml_request['xml']->info->ean) {
            return $this->__('Service not available') . ' (Error: E004)';
        }

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
            if($this->getEscNames()) {
                $escs_array = explode(',', $this->getEscNames());
                $escs_array = array_map([$this, 'normalizeSeller'], $escs_array);
                if(in_array($this->normalizeSeller($price_item->seller), $escs_array)){
                    continue;
                }
            }
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
    private function getXml($ean) {
        if (!$this->getSerial()) {
            return $this->error($this->__('Please configure your Pricemotion API key (serial)'));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getServiceUrl() . self::SERIAL_URL . $this->getSerial() . self::EAN_URL . urlencode($ean));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        if($response === false) {
            return $this->error($this->__('Service not available') . ' (Error: E001a)');
        }

        $response_xml = simplexml_load_string($response);

        if($response_xml === false) {
            return $this->error($this->__('Service not available') . ' (Error: E002) Response: ' . $response);
        }

        if($response_xml->getName() == 'error') {
            return $this->error($this->__('Service not available') . ": " . (string)$response_xml . ' (Error: E111)');
        }

        return [
            'success' => true,
            'xml' => $response_xml,
        ];
    }

    private function error($message) {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    private function normalizeSeller($seller) {
        $seller = strtolower($seller);
        $seller = preg_replace('/\s*/', '', $seller);
        return $seller;
    }

}