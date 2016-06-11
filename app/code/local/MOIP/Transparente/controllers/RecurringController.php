<?php
class MOIP_Transparente_RecurringController extends Mage_Core_Controller_Front_Action
{
    
    public function getApiMoip()
    {
        $api = Mage::getSingleton('transparente/recurringapi');
        return $api;
        
    }

    public function NewTranscitionAction()
    {


               
        $api       = $this->getApiMoip();
        $chave1 = Mage::getStoreConfig('payment/moip_transparente_standard/validador_retorno');
       
        $data = $this->getRequest()->getParams();
        $json_moip = $this->getRequest()->getRawBody();
        $decode = json_decode($json_moip, false);
       
              
        $api->generateLog($json_moip, 'MOIP_Webhooks.log'); 

        if($data['validacao'] == $chave1){
            $order_event = $decode->event;

            if($order_event == 'payment.status_updated'){



                $api->generateLog($json_moip, 'MOIP_Webhooks.log');
                $order_trans = $decode->resource->subscription_code;
                $order_trans_status = $decode->resource->status->description;
                if($order_trans_status == "Autorizado"){
                    $new_order_data = $this->consultRecurring($order_trans);
                    $order_create   = $this->CreateOrder($new_order_data);

                    echo "criada order: ".$order_create->getId();
                    echo "status: ".$order_trans_status;

                    echo  $this->setStatesRecurring($order_create, $order_trans_status);
                    
                    $order_create->sendNewOrderEmail();    
                } else {
                   echo "Não lerei essa info";
                   return; 
                }
                
                
            } elseif($order_event == 'payment.created'){
                echo "Não lerei essa info";
               return; 
                
                
            } 

        }
        
        
    }
    public function consultRecurring($code_id){
        $profile           = Mage::getModel('sales/recurring_profile')->load($code_id);
        $order             = $profile->getOrderInfo();
        $order_id          = $order['entity_id'];
        $customer          = $profile->getCustomerId();
        $shipping_amount   = $profile->getShippingAmount();
        $payment_method    = $profile->getMethodCode();
        $shipping          = $profile->getShippingAddressInfo();
        $dicount           = $profile->getDiscountAmount();
        $base_dicount      = $profile->getBaseDiscountAmount();
        $shipping_method   = $shipping['shipping_method'];
        $shipping_description   = $shipping['shipping_description'];
        $products          = $profile->getOrderItemInfo();        
        $products_order = array(
            $products['product_id'] => array(
                'qty' => (int) $products['qty']
            )
        );
 
        $order_init = array( 
                            'profile' => $profile,
                            'order_info' => $order,
                            'order_id' => $order_id,
                            'customer_id' => $customer,
                            'order_discount' => $dicount,
                            'order_base_discount' => $base_dicount,
                            'products' => $products_order,
                            'shipping_amount' => $shipping_amount,
                            'payment'   => $payment_method,
                            'shipping' => $shipping_method,
                            'shipping_description' => $shipping_description
                        );
  
        return $order_init;
    }

    public function CreateOrder($order_init = array(), $link = null)
    {
        $products_init             = $order_init['products'];
        $payment_init              = $order_init['payment'];
        $shipping_init             = $order_init['shipping'];
        $shipping_description_init = $order_init['shipping_description'];
        $shipping_price_init       = $order_init['shipping_amount'];
        $profile                   = $order_init['profile'];
        $customer_id               = $order_init['customer_id'];
        $dicount                   = $order_init['order_discount'];
        $base_dicount              = $order_init['order_base_discount'];
        
        $customer = Mage::getModel('customer/customer')->load($customer_id);

        $transaction     = Mage::getModel('core/resource_transaction');
        $storeId         = $customer->getStoreId();
        $reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);

        $order = Mage::getModel('sales/order')
                ->setIncrementId($reservedOrderId)
                ->setStoreId($storeId)
                ->setQuoteId(0)
                ->setGlobal_currency_code('BRL')
                ->setBase_currency_code('BRL')
                ->setStore_currency_code('BRL')
                ->setOrder_currency_code('BRL');

        $order->setCustomer_email($customer->getEmail())
                ->setCustomerFirstname($customer->getFirstname())
                ->setCustomerLastname($customer->getLastname())
                ->setCustomerTaxvat($customer->getTaxvat())
                ->setCustomerGroupId($customer->getGroupId())
                ->setCustomer_is_guest(0)
                ->setCustomer($customer);

        $billing        = $customer->getDefaultBilling();
        $address        = Mage::getModel('customer/address')->load($billing);
        $billingAddress = Mage::getModel('sales/order_address')->setStoreId($storeId)->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
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
                            ->setCountryId($address->getCountryId())
                            ->setRegion($address->getRegion())
                            ->setRegionId($address->getRegionId())
                            ->setPostcode($address->getPostcode())
                            ->setTelephone($address->getTelephone())
                            ->setFax($address->getFax())
                            ->setVatId($customer->getTaxvat());
        $order->setBillingAddress($billingAddress);

        $shipping        = $customer->getDefaultShippingAddress();
        $shippingAddress = Mage::getModel('sales/order_address')->setStoreId($storeId)->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
                            ->setCustomerId($customer->getId())
                            ->setCustomerAddressId($customer->getDefaultShipping())
                            ->setCustomerAddressId($shipping->getId())
                            ->setPrefix($shipping->getPrefix())
                            ->setFirstname($shipping->getFirstname())
                            ->setMiddlename($shipping->getMiddlename())
                            ->setLastname($shipping->getLastname())
                            ->setCustomerTaxvat($customer->getTaxvat())
                            ->setSuffix($shipping->getSuffix())
                            ->setCompany($shipping->getCompany())
                            ->setStreet($shipping->getStreet())
                            ->setCity($shipping->getCity())
                            ->setCountryId($shipping->getCountryId())
                            ->setRegion($shipping->getRegion())
                            ->setRegionId($shipping->getRegionId())
                            ->setPostcode($shipping->getPostcode())
                            ->setTelephone($shipping->getTelephone())
                            ->setFax($shipping->getFax())
                            ->setVatId($customer->getTaxvat());

        $order->setShippingAddress($shippingAddress);
        $order->setShippingMethod($shipping_init)->setShippingDescription($shipping_description_init);


        $orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)->setCustomerPaymentId(0)->setMethod($payment_init)->setPo_number(' – ');
        $order->setPayment($orderPayment);
        if($link)
        $orderPayment->setAdditionalInformation('link_boleto', $link);

        $subTotal = 0;
        $products = $products_init;

        foreach ($products as $productId => $product) {
            $_product  = Mage::getModel('catalog/product')->load($productId);
            $rowTotal  = $_product->getPrice() * $product['qty'];
            $orderItem = Mage::getModel('sales/order_item')->setStoreId($storeId)->setQuoteItemId(0)->setQuoteParentItemId(NULL)->setProductId($productId)->setProductType($_product->getTypeId())->setQtyBackordered(NULL)->setTotalQtyOrdered($product['rqty'])->setQtyOrdered($product['qty'])->setName($_product->getName())->setSku($_product->getSku())->setPrice($_product->getPrice())->setBasePrice($_product->getPrice())->setOriginalPrice($_product->getPrice())->setRowTotal($rowTotal)->setBaseRowTotal($rowTotal);
            
            $subTotal += $rowTotal;
            $order->addItem($orderItem);
        }
        $subTotal = $subTotal + $shipping_price_init;
        $order->setSubtotal($subTotal)
            ->setBaseSubtotal($subTotal)
            ->setGrandTotal($subTotal)
            ->setBaseShippingAmount($shipping_price_init)
            ->setShippingAmount($shipping_price_init)
            ->setBaseGrandTotal($subTotal)
            ->setBaseDiscountAmount($base_discount)
            ->setDiscountAmount($discount);

        $transaction->addObject($order);
        $transaction->addCommitCallback(array(
            $order,
            'place'
        ));

        $transaction->save();


        $profile->addOrderRelation($order->getId());

        return $order;

    }
    
    
   
    
    public function setStatesRecurring($order, $status_moip)
    {
        $order_status = $order->getStatus();
        $onhold       = Mage::getSingleton('transparente/standard')->getConfigData('order_status_holded_trial');
        $paid         = Mage::getSingleton('transparente/standard')->getConfigData('order_status_processing');
        
        if ($order->getId()) {
            try {

                
                    
                    $state   = Mage_Sales_Model_Order::STATE_PROCESSING;
                    $status  = "processing";
                    $comment = "Pagamento Autorizado";
                    $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
                    $order->save();
                    $order->sendOrderUpdateEmail(true, $comment);
                    $invoice = $order->prepareInvoice();
                    Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
                    $invoice->sendEmail();
                    $invoice->setEmailSent(true);
                    $invoice->save();
               
                    echo "Processada";
            }
            catch (Exception $order) {
                
                Mage::throwException(Mage::helper('core')->__('Order não encontrada'));
            }
            
        } else {
            Mage::throwException(Mage::helper('core')->__('Order não encontrada'));
        }
    }
    
    
    public function autorizaPagamento($order, $paid)
    {
        $state   = Mage_Sales_Model_Order::STATE_PROCESSING;
        $status  = $paid;
        $comment = "Pagamento Autorizado";
        $invoice = $order->prepareInvoice();
        if ($this->getStandard()->canCapture()) {
            $invoice->register()->capture();
        }
        Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
        $invoice->sendEmail();
        $invoice->setEmailSent(true);
        $invoice->save();
        $update = $this->updateInOrder($order, $state, $status, $comment);
        return $update;
    }
    
    public function iniciaPagamento($order, $onhold)
    {
        $state   = Mage_Sales_Model_Order::STATE_HOLDED;
        $status  = $onhold;
        $comment = "Pagamento Iniciado";
        $update  = $this->updateInOrder($order, $state, $status, $comment);
        return $update;
    }

     public function iniciaPagamentoEspecial($order, $onhold)
    {
        $state   = Mage_Sales_Model_Order::STATE_NEW;
        $status = 'pending';
        $comment = "Order de solicitação de recorrência.";
        $update  = $this->updateInOrder($order, $state, $status, $comment);
        return $update;
    }
    
    public function cancelaPagamento($order)
    {
        $state   = Mage_Sales_Model_Order::STATE_CANCELED;
        $comment = "Pagamento Não Autorizado";
        $status  = 'canceled';
        $order->cancel();
        $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
        $order->save();
        $update = $this->updateInOrder($order, $state, $status, $comment);
        return $update;
    }
    
    public function updateInOrder($order, $state, $status, $comment){
        $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
        $order->save();
        $order->sendOrderUpdateEmail(true, $comment);
        return true;
     }
    
}   