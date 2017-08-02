<?php
class MOIP_Transparente_Model_Method_BoletoRecurring extends Mage_Payment_Model_Method_Abstract implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    
    protected $_code = 'moip_boletorecurring';
    protected $_formBlockType = 'transparente/form_boleto';
    protected $_infoBlockType = 'transparente/info_boletorecurring';
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canCreateBillingAgreement = true;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canReviewPayment = true;
    
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        return $this;
    }
    public function getPayment()
    {
        return $this->getQuote()->getPayment();
    }
    public function getSession()
    {
        return Mage::getSingleton('transparente/session');
    }
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
   
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        return $this;
    }
    public function prepare()
    {
        $info           = $this->getInfoInstance();
        $additionaldata = unserialize($info->getAdditionalData());
        
    }

    public function validate()
    {
        
       
        parent::validate();
        $info           = $this->getInfoInstance();
        $currency_code  = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg       = false;
        $additionaldata = unserialize($info->getAdditionalData());
        
       
        
        
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
   
    
    public function getApiMoip()
    {
        $api = Mage::getSingleton('transparente/recurringapi');
        return $api;
        
    }
   
    public function canUseCheckout()
    {
        $cart = Mage::getModel('checkout/cart')->getQuote();
        foreach ($cart->getAllItems() as $item) {
            if (!$item->getProduct()->getIsRecurring())
                return false;
        }
        return true;
    }
    
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
       
        
        return $this;
    }
    
    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, Mage_Payment_Model_Info $payment)
    {
        
        $api                 = $this->getApiMoip();
        $response_moip       = $api->searchCustomersPlans($profile, $payment);
        $decode_moip         = json_decode($response_moip, true);
        $moip_code           = $decode_moip["code"];
       
         
        
        if ($moip_code) {
            $profile->setReferenceId($moip_code);
            $payment->setSkipTransactionCreation(true);
            
            if ((float) $profile->getInitAmount()) {
                $productItemInfo = new Varien_Object;
                $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_INITIAL);
                $productItemInfo->setPrice($profile->getInitAmount()-$profile->getShippingAmount());
                
                $order    = $profile->createOrder($productItemInfo);
                
                $payment = $order->getPayment();
                $payment->setTransactionId($moip_code)->setIsTransactionClosed(1);
               
                $profile->addOrderRelation($order->getId());
                
                
                $transaction = Mage::getModel('sales/order_payment_transaction');
                $transaction->setTxnId($moip_code);
                $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                $transaction->setPaymentId($payment->getId());
                $transaction->setOrderId($order->getId());
                $transaction->setOrderPaymentObject($payment);
                $transaction->setIsClosed(1);
                $transaction->save();
                $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
            }
            
            $this->chargeRecurringProfile($profile, $response_moip);
            
            return $this;
            
        } else {
            
            if (!$profile->getInitMayFail()) {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED);
                $profile->save();
            }
            
            Mage::throwException($response['msg']);
            
        }
    }
    
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
       
        
        return $this;
    }
    
    public function canGetRecurringProfileDetails()
    {
       
        
        return true;
    }
    
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
       
        
        return $this;
    }
    
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        switch ($profile->getNewState()) {
            case Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE:
                {
                    $profile_id = $profile->getId();
                    $moip = $this->getApiMoip()->activateSubscription($profile_id);
                    $action = 'start';
                    break;
                }
            case Mage_Sales_Model_Recurring_Profile::STATE_CANCELED:
            {

                $profile_id = $profile->getId();
                $moip = $this->getApiMoip()->cancelSubscription($profile_id);
                $action = 'cancel';
                break;
            }
                
            case Mage_Sales_Model_Recurring_Profile::STATE_EXPIRED:
                    $action = 'cancel';
                break;
            case Mage_Sales_Model_Recurring_Profile::STATE_SUSPENDED:
                {
                    $profile_id = $profile->getId();
                    $moip = $this->getApiMoip()->suspendSubscription($profile_id);
                    $action = 'stop';
                    break;
                }
            default:
                return $this;
        }
    }
    
    
    public function chargeRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, $response_moip){
            $decode_moip = json_decode($response_moip, true);
            $moip_code = $decode_moip["code"];
           
            $productItemInfo = new Varien_Object;
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
            $productItemInfo->setPrice( $profile->getTaxAmount() + $profile->getBillingAmount() );

            $order = $profile->createOrder($productItemInfo);
            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
            $payment = $order->getPayment();
            $payment->setTransactionId($moip_code)->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                serialize($moip_code)
            )->setIsTransactionClosed(1);
            
            $profile->addOrderRelation($order->getId());
           

            $transaction= Mage::getModel('sales/order_payment_transaction');
            $transaction->setTxnId($moip_code);
            $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            $transaction->setPaymentId($payment->getId());
            $transaction->setAdditionalInformation(
                Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                $decode_moip
            );
            $transaction->setOrderId($order->getId());
            $transaction->setOrderPaymentObject($payment);
            $transaction->setIsClosed(1);

            $transaction->save();
           
            $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
            
            // change updated_at to one cycle ahead
            $this->_setUpdateDateToNextPeriod($profile->getId());
            
            return true;
            
       
    }
    
    
    protected function _setUpdateDateToNextPeriod($profile_id)
    {
        
        
        $_resource = Mage::getSingleton('core/resource');
        $sql       = '
            UPDATE ' . $_resource->getTableName('sales_recurring_profile') . '
            SET updated_at = CASE period_unit
                WHEN "day"          THEN DATE_ADD(updated_at, INTERVAL period_frequency DAY)
                WHEN "week"         THEN DATE_ADD(updated_at, INTERVAL (period_frequency*7) DAY)
                WHEN "semi_month"   THEN DATE_ADD(updated_at, INTERVAL (period_frequency*14) DAY)
                WHEN "month"        THEN DATE_ADD(updated_at, INTERVAL period_frequency MONTH)
                WHEN "year"         THEN DATE_ADD(updated_at, INTERVAL period_frequency YEAR)
            END
            WHERE profile_id = :pid';
        
        $connection   = $_resource->getConnection('core_write');
        $pdoStatement = $connection->prepare($sql);
        $pdoStatement->bindValue(':pid', $profile_id);
        return $pdoStatement->execute();
    }
    
    protected function _sendRequest($action, array $params = array())
    {
        
        return array(
            'result' => 'SUCCESS',
            'msg' => 'Success',
            'action' => $action,
            'token' => 'token-' . uniqid()
        );
    }
    
}