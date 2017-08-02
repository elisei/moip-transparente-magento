<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Recurring profile view
 */
class MOIP_Transparente_Block_Sales_Recurring_Profile_View extends Mage_Sales_Block_Recurring_Profile_View
{
    public function getMethodMoip()
    {
    	$ref 			   = $this->_profile->getReferenceId();
    	$profile           = Mage::getModel('sales/recurring_profile')->load($ref);
    	$payment_method    = $profile->getMethodCode();
    	return $payment_method;
    }
   
   public function getOrderInfoPayment(){
   		$ref 			   = $this->_profile->getReferenceId();
   		$profile           = Mage::getModel('sales/recurring_profile')->load($ref);
   		
   		$customer_id       = $profile->getCustomerId();
   		$order = Mage::getResourceModel('sales/order_collection')
  	   ->addFieldToFilter('customer_id', $customer_id)
		   ->addRecurringProfilesFilter($profile->getProfileId())
		   ->setOrder('entity_id', 'desc')
		   ->getLastItem();
        
        $load_order 			   = Mage::getModel('sales/order')->load($order->getId());
        $payment 		   = $order->getPayment()->getId();

        $collection = Mage::getModel('sales/order_payment_transaction')
        		->getCollection()
                ->addPaymentIdFilter($payment);

        foreach ($collection as $col)
        {
            $info_transaction = $col->getAdditionalInformation();
        }

        return $info_transaction;
        
      /* return $load_order;*/
   }
}
