<?php

/**
 * PriceMotion System config source model to get product attributes
 *
 * @package        Pricemotion
 * @copyright    Aim4Solutions s.r.l.
 * @author        Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Model_System_Config_Source_Product_Attributes
{

    /**
     * Returns a list of attributes as an array for select options
     *
     * @access public
     * @return array
     */
    public function toOptionArray()
    {

        $product = Mage::getModel('catalog/product');
        $attributes = $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($product->getResource()->getTypeId());

        $attributes_array = array();
        $attributes_array[] = array("value" => '', 'label' => '--');
        foreach ($attributes as $attr) {
            $attributes_array[] = array("value" => $attr['attribute_code'], 'label' => $attr['frontend_label']);
        }
        return $attributes_array;

    }

}

?>