<?php

/**
 * PriceRules product tab block
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Block_Adminhtml_Catalog_Product_Pricerules
extends Mage_Adminhtml_Block_Template
implements Mage_Adminhtml_Block_Widget_Tab_Interface {
 
    /**
     * Set the template for the block
     *
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('pricemotion/price_rules.phtml');
    }
    
	/**
	 * Get Pricemotion price rules for the current product
	 * @return A4s_Pricemotion_Model_Rules
	 */
    public function getProductRules() {
        $product_id = Mage::registry('product')->getId();
        return Mage::getModel('pricemotion/rules')->load($product_id, 'product_id');
    }
 
    /**
     * Retrieve the label used for the tab relating to this block
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('PriceMotion Price Rules');
    }
 
    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('PriceMotion Price Rules');
    }
 
    /**
     * Determines whether to display the tab
     * Add logic here to decide whether you want the tab to display
     *
     * @return bool
     */
    public function canShowTab()
    {
        $product = Mage::registry('product');
        if($product->getEnablePricemotion()) return true;
        return false;
    }
 
    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
 
    /**
     * AJAX TAB's
     * If you want to use an AJAX tab, uncomment the following functions
     * Please note that you will need to setup a controller to recieve
     * the tab content request
     *
     */
    /**
     * Retrieve the class name of the tab
     * Return 'ajax' here if you want the tab to be loaded via Ajax
     *
     * return string
     */
#   public function getTabClass()
#   {
#       return 'my-custom-tab';
#   }
 
    /**
     * Determine whether to generate content on load or via AJAX
     * If true, the tab's content won't be loaded until the tab is clicked
     * You will need to setup a controller to handle the tab request
     *
     * @return bool
     */
#   public function getSkipGenerateContent()
#   {
#       return false;
#   }
 
    /**
     * Retrieve the URL used to load the tab content
     * Return the URL here used to load the content by Ajax
     * see self::getSkipGenerateContent & self::getTabClass
     *
     * @return string
     */
#   public function getTabUrl()
#   {
#       return null;
#   }
 
}