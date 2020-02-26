<?php


/**
 * PriceMotion mysql installer
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */


$installer = $this;

$installer->startSetup();
$installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY,'pricemotion_price_difference','input','int');
$installer->endSetup();