<?php

$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE  {$this->getTable('pricemotion_rules')} CHANGE  `status`  `status` INT( 11 ) NOT NULL COMMENT  '0=Disabled; 1=Below average; 2=In top; 3=Lowest price';
");


$installer->endSetup();
