<?php

/**
 * PriceMotion Price rules mysql4 collection
 *
 * @package        Pricemotion
 * @copyright    Aim4Solutions s.r.l.
 * @author        Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Model_Mysql4_Rules_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('pricemotion/rules');

    }
}
