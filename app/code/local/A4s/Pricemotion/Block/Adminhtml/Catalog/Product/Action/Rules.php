<?php

/**
 * Mass PriceRules edit
 *
 * @package     Pricemotion
 * @copyright   Aim4Solutions s.r.l.
 * @author      Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
 
class A4s_Pricemotion_Block_Adminhtml_Catalog_Product_Action_Rules extends Mage_Adminhtml_Block_Widget_Form_Container {
    
    public function __construct() {
        parent::__construct();
        $this->_removeButton('reset');
        $this->_updateButton('back', 'onclick', "setLocation('" . Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product') . "')");
    }
    
	/**
	 * Retrive header text
	 * 
	 * @return  string
	 */
    public function getHeaderText() {
        return Mage::helper('pricemotion')->__('Mass Edit PriceRules');
    }

    /**
     * Retrieve Tag Save URL
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current'=>true));
    }
}
