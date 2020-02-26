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
ALTER TABLE {$this->getTable('pricemotion_rules')} ADD `only_email` BOOLEAN NOT NULL DEFAULT FALSE AFTER `in_top_value` ;
");

 
$installer->endSetup();
