<?php

require 'app/Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}
Mage::app('admin')->setUseSessionInUrl(false);

//echo round(9.99, 0, PHP_ROUND_HALF_DOWN);die();

Mage::getModel('pricemotion/observer')->setPrices(1);
Mage::getModel('pricemotion/observer')->updateAttributes(1);