<?php

/**
 * PriceMotion products grid
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Block_Adminhtml_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid {

	/**
     * Prepare grid columns. Add 'lowest price' and 'price difference' columns
		*
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns() {
        $store = $this->_getStore();
        if(Mage::helper("pricemotion")->getLowestPriceAtt()) {
            $this->addColumnAfter(Mage::helper("pricemotion")->getLowestPriceAtt(),
                array(
                    'header'=> Mage::helper('pricemotion')->__('Lowest Price'),
                    'type'  => 'price',
                    'currency_code' => $store->getBaseCurrency()->getCode(),
                    'index' => Mage::helper("pricemotion")->getLowestPriceAtt(),
                    'width' => '100px'
            ), 'sku');
        }
        
        if(Mage::helper("pricemotion")->getPriceDiffAtt()) {
            $this->addColumnAfter(Mage::helper("pricemotion")->getPriceDiffAtt(),
                array(
                    'header'=> Mage::helper('pricemotion')->__('Price Difference'),
                    'index' => Mage::helper("pricemotion")->getPriceDiffAtt(),
                    'width' => '100px'
            ), 'sku');
        }

        $this->addColumnAfter('enable_pricemotion',
            array(
                'header'=> Mage::helper('pricemotion')->__('Show PriceMotion Prices'),
                'width' => '70px',
                'index' => 'enable_pricemotion',
                'type'  => 'options',
                'options' => Mage::getSingleton('eav/entity_attribute_source_boolean')->getOptionArray(),
            ), 'visibility');

        $this->sortColumnsByOrder();
        return parent::_prepareColumns();
     }
	 
	/**
     * Prepare grid massaction actions. Add 'Update PriceMotion Price Rules' action
	 *
     * @return A4s_Pricemotion_Block_Adminhtml_Catalog_Product_Grid
     */
	protected function _prepareMassaction() {
        parent::_prepareMassaction();
        $this->getMassactionBlock()->addItem('pm_pricerules', array(
		'label' => Mage::helper('pricemotion')->__('Update PriceMotion Price Rules'),
		'url'   => $this->getUrl('pricemotion/adminhtml_action/rules', array('_current'=>true))
		));
        return $this;
    }
}  