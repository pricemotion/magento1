<?php

/**
 * PriceMotion product tab block
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Block_Adminhtml_Catalog_Product_Tab
extends Mage_Adminhtml_Block_Widget_Form
implements Mage_Adminhtml_Block_Widget_Tab_Interface {
 
    /**
     * Set the template for the block
     *
     */
    public function _construct()
    {
        parent::_construct();
    }
	
	/**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    
    protected function _prepareForm() {
    	$form = new Varien_Data_Form();
    	
    	$fieldset = $form->addFieldset('prices', array(
            'legend' => Mage::helper('pricemotion')->__('Price Information')
        ));
        
        $fieldset->addType('error','A4s_Pricemotion_Block_Adminhtml_Form_Element_Errormsg');
        $fieldset->addType('prices','A4s_Pricemotion_Block_Adminhtml_Form_Element_Prices');
        
        $pricemotion_info = Mage::helper('pricemotion')->getPricemotion(Mage::registry('product'));
        
        if(!$pricemotion_info['success']) {
        	$fieldset->addField('error', 'error', array(
	        	'label' 	=> Mage::helper('pricemotion')->__('Error'),
	            'title' 	=> Mage::helper('pricemotion')->__('Error'),
	        	'message'	=> $pricemotion_info['message']
			));
        } else {
        	$fieldset->addField('highest_price', 'text', array(
	            'name' => 'highest_price',
	            'label' => Mage::helper('pricemotion')->__('Highest Price'),
	            'title' => Mage::helper('pricemotion')->__('Highest Price'),
	            'disabled'  => true
	        ));
	        
	        $fieldset->addField('average_price', 'text', array(
	            'name' => 'average_price',
	            'label' => Mage::helper('pricemotion')->__('Average Price'),
	            'title' => Mage::helper('pricemotion')->__('Average Price'),
	            'disabled'  => true
	        ));
	        
	        $fieldset->addField('lowest_price', 'text', array(
	            'name' => 'lowest_price',
	            'label' => Mage::helper('pricemotion')->__('Lowest Price'),
	            'title' => Mage::helper('pricemotion')->__('Lowest Price'),
	            'disabled'  => true
	        ));
	        
	        $fieldset->addField('pricemotion_name', 'text', array(
	            'name' => 'pricemotion_name',
	            'label' => Mage::helper('pricemotion')->__('Pricemotion name'),
	            'title' => Mage::helper('pricemotion')->__('Pricemotion name'),
	            'disabled'  => true
	        ));
	        
	        $fieldset->addField('pricemotion_ean', 'text', array(
	            'name' => 'pricemotion_ean',
	            'label' => Mage::helper('pricemotion')->__('Pricemotion EAN'),
	            'title' => Mage::helper('pricemotion')->__('Pricemotion EAN'),
	            'disabled'  => true
	        ));
	        
	        $fieldset->addField('pricemotion_prices', 'prices', array(
	            'name' => 'pricemotion_prices',
	            'label' => Mage::helper('pricemotion')->__('Prices'),
	            'title' => Mage::helper('pricemotion')->__('Prices'),
	        	'tablehead'	=> array(Mage::helper('pricemotion')->__('Vendor'),Mage::helper('pricemotion')->__('Price'))
	        ));
	        
	        $form_data['highest_price'] = $pricemotion_info['info']['highest'];
	        $form_data['average_price'] = $pricemotion_info['info']['average'];
	        $form_data['lowest_price'] = $pricemotion_info['info']['lowest'];
	        $form_data['pricemotion_name'] = trim($pricemotion_info['info']['name']);
	        $form_data['pricemotion_ean'] = trim($pricemotion_info['info']['ean']);
	        $form_data['pricemotion_prices'] = $pricemotion_info['prices'];
	        
	        $form->setValues($form_data);
        }
        
        $this->setForm($form);
        
    	return parent::_prepareForm();
    	
    }
 
    /**
     * Retrieve the label used for the tab relating to this block
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('PriceMotion Prices');
    }
 
    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('PriceMotion Prices');
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