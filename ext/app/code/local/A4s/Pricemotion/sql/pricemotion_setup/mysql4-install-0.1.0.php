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
 
$installer->run("
 
-- DROP TABLE IF EXISTS {$this->getTable('pricemotion_rules')};
CREATE TABLE {$this->getTable('pricemotion_rules')} (
  `rule_id` int(10) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) NOT NULL,
  `cost_margin_percent` float(5,2) DEFAULT NULL,
  `cost_margin_status` tinyint(1) NOT NULL COMMENT '0=Disabled; 1=Enabled',
  `below_average_percent` float(5,2) NOT NULL,
  `in_top_value` int(4) NOT NULL,
  `status` int(11) NOT NULL COMMENT '0=Disabled; 1=Below average; 2=In top',
  PRIMARY KEY (`rule_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
 
	");
	
$installer->addAttribute('catalog_product', 'pricemotion_lowest_price', array(
'backend'       => '',
'frontend'      => '',
'label'         => 'Lowest Price',
'input'         => 'price',
'class'         => '',
'global'        => true,
'visible'       => true,
'required'      => false,
'user_defined'  => true,
'searchable'    => false,
'filterable'    => false,
'comparable'    => false,
'apply_to'      => '',
'position'      => 1,
'visible_on_front' => false,
'visible_in_advanced_search' => 1
));

$installer->addAttribute('catalog_product', 'pricemotion_price_difference', array(
'backend'       => '',
'frontend'      => '',
'label'         => 'Price Difference (%)',
'input'         => 'int',
'class'         => '',
'global'        => true,
'visible'       => true,
'required'      => false,
'user_defined'  => true,
'searchable'    => false,
'filterable'    => false,
'comparable'    => false,
'apply_to'      => '',
'position'      => 1,
'visible_on_front' => false,
'visible_in_advanced_search' => 1
));
 
$installer->endSetup();
