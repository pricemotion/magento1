<?php

class A4s_Pricemotion_Adminhtml_ActionController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Action controller rules action
	 */
    public function rulesAction()
    {
        if (!$this->_validateProducts()) {
            return;
        }
        
        $this->_title($this->__('PriceMotion'))
             ->_title($this->__('Mass Update PriceRules'));
        $this->loadLayout()->renderLayout();
    }
    
	/**
	 * Action controller save action
	 */
    public function saveAction()
    {
        if (!$this->_validateProducts()) {
            return;
        }
        $post = $this->getRequest()->getParams();
        $post_data = $post['pricemotion'];
        $productIds  = $this->_getHelper()->getProductIds();
        try {
            foreach ($productIds as $product_id) {
                $cost_margin_status = ($post_data['cost_margin_enabled'] == 'on') ? 1 : 0;
                $only_email = ($post_data['only_email'] == 'on') ? 1 : 0;

                $rule_model = Mage::getModel('pricemotion/rules')->load($product_id, 'product_id');
                $rule_model->setData('product_id', $product_id);
                $rule_model->setData('cost_margin_percent', $post_data['cost_margin']);
                $rule_model->setData('cost_margin_status', $cost_margin_status);
                $rule_model->setData('below_average_percent', $post_data['below_average']);
                $rule_model->setData('in_top_value', $post_data['top']);
                $rule_model->setData('only_email', $only_email);
                $rule_model->setData('status', $post_data['price_rules']);
                $rule_model->save();
            }
            $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were updated', count($this->_getHelper()->getProductIds()))
                    );
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while updating the product(s) price rules.'));
        }
        $this->_redirect('adminhtml/catalog_product', array('_current'=>true));
    }
    
    /**
     * Rertive data manipulation helper
     *
     * @return A4s_Pricemotion_Helper_Catalog_Product_Action
     */
    protected function _getHelper()
    {
        return Mage::helper('pricemotion/catalog_product_action');
    }
    
    /**
     * Validate selection of products for massupdate
     *
     * @return boolean
     */
    protected function _validateProducts()
    {
        $error = false;
        $productIds = $this->_getHelper()->getProductIds();
        if (!is_array($productIds)) {
            $error = $this->__('Please select products for price rules update');
        }

        if ($error) {
            $this->_getSession()->addError($error);
            $this->_redirect(Mage::helper('adminhtml')->getUrl('adminhtml/catalog_product'), array('_current'=>true));
        }

        return !$error;
    }
    
}