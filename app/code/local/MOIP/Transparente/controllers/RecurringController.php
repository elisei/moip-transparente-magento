<?php
class MOIP_Transparente_RecurringController extends Mage_Core_Controller_Front_Action
{
    
    public function getApiMoip()
    {
        $api = Mage::getSingleton('transparente/recurringapi');
        return $api;
        
    }

    
    //$profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_PENDING)->save();

   
    
    public function NewTransactionAction()
    {
   
        $api       = $this->getApiMoip();
        $chave1 = Mage::getStoreConfig('payment/moip_transparente_standard/validador_retorno');
       
        $data = $this->getRequest()->getParams();
        $json_moip = $this->getRequest()->getRawBody();
        
        $decode = json_decode($json_moip, false);
       
              
        $api->generateLog($json_moip, 'MOIP_Webhooks.log'); 

        if($data['validacao'] == $chave1){
            $order_event        = $decode->event;
            $code_id            = $decode->resource->subscription_code;
            $data_for_payment   = json_decode($json_moip, true);

            if($order_event == 'payment.created'){
                
                $order_exist        = $this->consultOrdersExist($code_id);
                $order_init         = $order_exist->getFirstItem();
                $orderId            = $order_init->getEntityId();
                $order_load         = Mage::getModel('sales/order')->load($orderId);
                $order_status       = $order_load->getState();
                if($order_status === Mage_Sales_Model_Order::STATE_NEW && count($order_exist) === 1){
                    $order_trans_status  = $decode->resource->status->description;
                    if($order_trans_status == "Cancelado"){
                        $this->cancelaPagamento($order_load);
                        $this->setProfileState($code_id, "Cancelado"); 
                    } elseif ($order_trans_status == "Autorizado") {
                        $this->autorizaPagamento($order_load);
                         $this->setProfileState($code_id, "Autorizado");
                    }
                    
                    return $this;
                } else {
                    
                    $new_order      = $this->createNewOrderRecurring($code_id, $order_load, $data_for_payment["resource"]);
                    $order_trans_status  = $decode->resource->status->description;
                    echo $order_trans_status;
                    $this->cancelaPagamento($new_order);
                    echo $new_order->getIncrementId();
                    echo " ".$new_order->getState();
                }
            }

            elseif($order_event == 'payment.status_updated'){



                
                $code_id             = $decode->resource->subscription_code;
                $order_trans_status  = $decode->resource->status->description;
                $id                  = $decode->resource->id;
                if($order_trans_status == "Autorizado"){
                    $order_exist    = $this->consultOrdersExist($code_id);

                    $order_init     = $order_exist->getFirstItem();
                    $orderId        = $order_init->getEntityId();
                    $order_load     = Mage::getModel('sales/order')->load($orderId);
                    $order_status   = $order_load->getState();
                   
                    if($order_status === Mage_Sales_Model_Order::STATE_NEW && count($order_exist) === 1){
                        $this->autorizaPagamento($order_load);
                        $this->setProfileState($code_id, "Autorizado");
                       
                    } else {
                      $order_transaction = $this->consultTransactionId($id);
                      if($order_transaction['order_id']){
                        $order    = Mage::getModel('sales/order')->load($order_transaction['order_id']);
                        $this->autorizaPagamento($order);
                        $this->setProfileState($code_id, "Autorizado");
                      }
                      
                    }
                  
                } elseif($order_trans_status == "Cancelado") {
                    $order_exist    = $this->consultOrdersExist($code_id);

                    $order_init     = $order_exist->getFirstItem();
                    $orderId        = $order_init->getEntityId();
                    $order_load     = Mage::getModel('sales/order')->load($orderId);
                    $order_status   = $order_load->getState();
                   
                    if($order_status === Mage_Sales_Model_Order::STATE_NEW && count($order_exist) === 1){
                        $this->cancelaPagamento($order_load);
                        $this->setProfileState($code_id, "Cancelado");
                       
                    } else {
                      $order_transaction = $this->consultTransactionId($id);
                      if($order_transaction['order_id']){
                        $order    = Mage::getModel('sales/order')->load($order_transaction['order_id']);
                        $this->cancelaPagamento($order);
                        $this->setProfileState($code_id, "Cancelado");
                      }
                      
                    }


                } else {
                   echo "Não lerei essa info";
                   return; 
                }
                
                
            } else {
                echo "Não lerei essa info";
               return;
            } 

        }
    }

    public function consultOrdersExist($code_id){
        $profile            = Mage::getModel('sales/recurring_profile')->load($code_id);
        $customer_id        = $profile->getCustomerId();
        $order              = Mage::getResourceModel('sales/order_collection')
                                ->addFieldToFilter('customer_id', $customer_id)
                                ->addRecurringProfilesFilter($profile->getProfileId())
                                ->setOrder('entity_id', 'asc');
        return $order; 
    }

    public function consultTransactionId($id){
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table = (string) Mage::getConfig()->getTablePrefix().'sales_payment_transaction';

        $query = "SELECT order_id FROM ". $table ." WHERE txn_id ={$id}";
        $results = $readConnection->fetchAll($query);
        return $results[0];
    }

    public function createNewOrderRecurring($code_id, $order, $data_for_payment){
     
        $content_parent     = !1;
        $profile            = Mage::getModel('sales/recurring_profile')->load($code_id);
        $customer_id        = $profile->getCustomerId();
        $customer           = Mage::getModel('customer/customer')->load($customer_id);
        $transaction        = Mage::getModel('core/resource_transaction');
        $storeId            = $customer->getStoreId();
        $reservedOrderId    = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
        $billing            = $customer->getDefaultBilling();
        $address            = Mage::getModel('customer/address')->load($billing);
        
        
        

        $new_order = Mage::getModel('sales/order')
               // ->setIncrementId($order->getIncrementId().'-1')
               
                ->setIncrementId($reservedOrderId)
                ->setStoreId($storeId)
                ->setQuoteId(0)
                ->setGlobalCurrencyCode('BRL')
                ->setBaseCurrencyCode('BRL')
                ->setStoreCurrencyCode('BRL')
                ->setOrderCurrencyCode('BRL');

        $new_order->setCustomerEmail($customer->getEmail())
                ->setCustomerFirstname($customer->getFirstname())
                ->setCustomerLastname($customer->getLastname())
                ->setCustomerTaxvat($customer->getTaxvat())
                ->setCustomerGroupId($customer->getGroupId())
                ->setCustomerIsGuest(0)
                ->setCustomer($customer);
   
        $billingAddress = Mage::getModel('sales/order_address')
                            ->setStoreId($storeId)
                            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
                            ->setCustomerId($customer->getId())
                            ->setCustomerAddressId($customer->getDefaultBilling())
                            ->setPrefix($address->getPrefix())
                            ->setFirstname($address->getFirstname())
                            ->setMiddlename($address->getMiddlename())
                            ->setLastname($address->getLastname())
                            ->setSuffix($address->getSuffix())
                            ->setCompany($address->getCompany())
                            ->setStreet($address->getStreet())
                            ->setCity($address->getCity())
                            ->setCountryId("BR")
                            ->setRegion($address->getRegion())
                            ->setRegionId($address->getRegionId())
                            ->setPostcode($address->getPostcode())
                            ->setTelephone($address->getTelephone())
                            ->setFax($address->getFax())
                            ->setVatId($customer->getTaxvat());
        $new_order->setBillingAddress($billingAddress);

        $shipping        = $customer->getDefaultShippingAddress();
        $shippingAddress = Mage::getModel('sales/order_address')
                            ->setStoreId($storeId)
                            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                            ->setCustomerId($customer->getId())
                            ->setCustomerAddressId($customer->getDefaultShipping())
                            ->setPrefix($shipping->getPrefix())
                            ->setFirstname($shipping->getFirstname())
                            ->setMiddlename($shipping->getMiddlename())
                            ->setLastname($shipping->getLastname())
                            ->setCustomerTaxvat($customer->getTaxvat())
                            ->setSuffix($shipping->getSuffix())
                            ->setCompany($shipping->getCompany())
                            ->setStreet($shipping->getStreet())
                            ->setCity($shipping->getCity())
                            ->setCountryId("BR")
                            ->setRegion($shipping->getRegion())
                            ->setRegionId($shipping->getRegionId())
                            ->setPostcode($shipping->getPostcode())
                            ->setTelephone($shipping->getTelephone())
                            ->setFax($shipping->getFax())
                            ->setVatId($customer->getTaxvat());

        $new_order->setShippingAddress($shippingAddress);

        $new_order->setShippingMethod($order->getShippingMethod())
              ->setShippingDescription($order->getShippingDescription());


        
        $orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)->setCustomerPaymentId(0)->setMethod($order->getPayment()->getMethod());
        $new_order->setPayment($orderPayment);
       

        $transaction->save();
        if(!$this->isBundle($order)){
            $products    = $this->setInfoAddItemProduct($order);
            $new_order->addItem($products);
        }
        $new_order->setSubtotal($order->getSubtotal())
            ->setBaseSubtotal($order->getBaseSubtotal())
            ->setGrandTotal($order->getGrandTotal())
            ->setBaseShippingAmount($order->getBaseShippingAmount())
            ->setShippingAmount($order->getShippingAmount())
            ->setBaseGrandTotal($order->getBaseGrandTotal())
            ->setBaseDiscountAmount($order->getBaseDiscountAmount())
            ->setDiscountAmount($order->getDiscountAmount())->save();

        $new_order_id = $new_order->getId();
       
        if($this->isBundle($order)){
            $products    = $this->setInfoAddItemProduct($order, $new_order_id);
            $new_order->addItem($products)->save();
        }
        $profile->addOrderRelation($new_order->getId());
        
        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderId($new_order->getId());
        $transaction->setTxnId($data_for_payment["id"]);
        $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $transaction->setPaymentId($new_order->getPayment()->getId());
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $data_for_payment
        );
       
        $transaction->setOrderPaymentObject($new_order->getPayment());
        $transaction->setIsClosed(1);
        $transaction->save();
        $new_order->setState(Mage_Sales_Model_Order::STATE_NEW)->save();
       return $new_order;
    }

    
    public function isBundle($order){
        foreach($order->getAllItems() as $item) {
            $load_parent         = Mage::getModel('sales/order_item')->load($item->getId());
            $parent_product_type = $load_parent->getProductType();
            if ($parent_product_type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                return 1;
            }
        }
        return !1;
    }

    public function setInfoAddItemProduct($order, $new_order_id = null){
        $parent_id_order = "";
        foreach($order->getAllItems() as $item) {
            
                $load_parent         = Mage::getModel('sales/order_item')->load($item->getId());
                $parent_product_type = $load_parent->getProductType();
                
                $orderItem = Mage::getModel('sales/order_item')
                            
                            ->setIsNominal($item->getIsNominal())
                            ->setStoreId($item->getStoreId())
                            ->setQuoteItemId($item->getQuoteItemId())
                            ->setParentItemId($parent_id_order)
                            ->setSku($item->getSku()) 
                            ->setProductType($item->getProductType())
                            ->setProductId($item->getProductId())
                            ->setQtyBackordered($item->getQtyBackordered())
                            ->setTotalQtyOrdered($item->getTotalQtyOrdered())
                            ->setQtyOrdered($item->getQtyOrdered())
                            ->setName($item->getName())
                            ->setPrice($item->getPrice())
                            ->setBasePrice($item->getBasePrice())
                            ->setOriginalPrice($item->getOriginalPrice())
                            ->setBaseOriginalPrice($item->getBaseOriginalPrice())
                            ->setRowWeight($item->getRowWeight())
                            ->setPriceInclTax($item->getPriceInclTax())
                            ->setBasePriceInclTax($item->getBasePriceInclTax())
                            ->setTaxAmount($item->getTaxAmount())
                            ->setBaseTaxAmount($item->getBaseTaxAmount())
                            ->setTaxPercent($item->getTaxPercent())
                            ->setDiscountAmount($item->getDiscountAmount())
                            ->setBaseDiscountAmount($item->getBaseDiscountAmount())
                            ->setDiscountPercent($item->getDiscountPercent())
                            ->setRowTotal($item->getRowTotal())
                            ->setBaseRowTotal($item->getBaseRowTotal());

                if($new_order_id){
                    $orderItem->setOrderId($new_order_id);
                    $orderItem->save();
                } 
               
                if ($parent_product_type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $orderItem->setProductOptions($item->getProductOptions())->save();
                    $parent_id_order = $orderItem->getItemId();
                }
                
                
        }
      
        return $orderItem;
    }
    
    public function setProfileState($id_profile, $state){

        if($state === "Cancelado"){
            $state_profile      = Mage_Sales_Model_Recurring_Profile::STATE_PENDING;
        } elseif($state === "Autorizado"){
            $state_profile      = Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE;
        }

        $profile                = Mage::getModel('sales/recurring_profile')->load($id_profile);
        $profile->setState($state_profile)->save();
        return $this;
    }
    
    
    public function set404(){
        $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
        $this->getResponse()->setHeader('Status','404 File not found');

        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('defaultNoRoute');
        }
    }

    public function autorizaPagamento($order){
        if($order->canUnhold()) {
            $order->unhold()->save();
        } 
        $invoice = $order->prepareInvoice();
       
        $invoice->register()->capture();
        
        Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
        $invoice->sendEmail();
        $invoice->setEmailSent(true);
        $invoice->save();
        try {
            return $order;
        } catch (Exception $exception) {
            return $this->set404();
        }
     }

    public function cancelaPagamento($order){
        if($order->canUnhold()) {
            $order->unhold()->save();
        } 
        $order->cancel()->save();
        
        try {
            return $order;
        } catch (Exception $exception) {
            return $this->set404();
        }
        
    }
}   