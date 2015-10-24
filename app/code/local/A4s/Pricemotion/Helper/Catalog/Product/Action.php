<?php
/**
 * Pricemotion Catalog Product Action Helper
 *
 * @package     Pricemotion
 * @copyright   Aim4Solutions s.r.l.
 * @author      Sebastian Pruteanu <sebastian@aim4solutions.com>
 */
class A4s_Pricemotion_Helper_Catalog_Product_Action extends Mage_Core_Helper_Data
{
    /**
     * Selected products for mass-update
     *
     * @var Mage_Catalog_Model_Entity_Product_Collection
     */
    protected $_products;


    /**
     * Return product collection with selected product filter
     * Product collection didn't load
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function getProducts()
    {
        if (is_null($this->_products)) {
            $productsIds = $this->getProductIds();

            if (!is_array($productsIds)) {
                $productsIds = array(0);
            }

            $this->_products = Mage::getResourceModel('catalog/product_collection')
                ->addIdFilter($productsIds);
        }

        return $this->_products;
    }

    /**
     * Return array of selected product ids from post or session
     *
     * @return array|null
     */
    public function getProductIds()
    {
        $session = Mage::getSingleton('adminhtml/session');

        if ($this->_getRequest()->isPost() && $this->_getRequest()->getActionName() == 'rules') {
            $session->setProductIds($this->_getRequest()->getParam('product', null));
        }

        return $session->getProductIds();
    }

}
