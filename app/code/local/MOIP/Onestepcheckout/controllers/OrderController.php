<?php

require_once 'Mage/Sales/controllers/OrderController.php';
class MOIP_Onestepcheckout_OrderController extends Mage_Sales_OrderController{

 	public function reorderAction()
    {

    	 if (!$this->_loadValidOrder()) {
	            return;
	        }
	        $order = Mage::registry('current_order');

	        $cart = Mage::getSingleton('checkout/cart');
	        $cartTruncated = false;
	        /* @var $cart Mage_Checkout_Model_Cart */

	        $items = $order->getItemsCollection();
	        foreach ($items as $item) {
	            try {
	                $cart->addOrderItem($item);
	            } catch (Mage_Core_Exception $e){
	                if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
	                    Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
	                }
	                else {
	                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
	                }
	                $this->_redirect('*/*/history');
	            } catch (Exception $e) {
	                Mage::getSingleton('checkout/session')->addException($e,
	                    Mage::helper('checkout')->__('Cannot add the item to shopping cart.')
	                );
	                $this->_redirect('checkout/cart');
	            }
	        }

	        $cart->save();
	        $this->_redirect('checkout/onepage');
    	
    }
}