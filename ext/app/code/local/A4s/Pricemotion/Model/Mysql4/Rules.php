<?php

/**
 * PriceMotion Price rules mysql4
 *
 * @package        Pricemotion
 * @copyright    Aim4Solutions s.r.l.
 * @author        Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Model_Mysql4_Rules extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('pricemotion/rules', 'rule_id');
    }
}