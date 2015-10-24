<?php

/**
 * PriceMotion Rules model
 *
 * @package        Pricemotion
 * @copyright    Aim4Solutions s.r.l.
 * @author        Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Model_Rules extends Mage_Core_Model_Abstract
{

    const RULE_DISABLED = 0;
    const RULE_BELOW_AVERAGE = 1;
    const RULE_IN_TOP = 2;
    const LOWEST_PRICE = 3;
	const RULE_MATCH_TOP = 4;

    public function _construct()
    {
        parent::_construct();
        $this->_init('pricemotion/rules');
    }
}