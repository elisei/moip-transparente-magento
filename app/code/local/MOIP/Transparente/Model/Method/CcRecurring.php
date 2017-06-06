<?php
class MOIP_Transparente_Model_Method_CcRecurring extends Mage_Payment_Model_Method_Abstract implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{
    
    protected $_code = 'moip_ccrecurring';
    protected $_formBlockType = 'transparente/form_recurring';
    
    protected $_isGateway = false;
    protected $_canOrder = false;
    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canFetchTransactionInfo = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canReviewPayment = true;
    
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
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info           = $this->getInfoInstance();
        $dataNascimento = $data->getMoipCcrecurringOwnerExpDay() . "/" . $data->getMoipCcrecurringOwnerExpMonth() . "/" . $data->getMoipCcrecurringOwnerExpYear();
        
        $additionaldata = array(
            'hash_moip' => $data->getMoipCcrecurringNumber()
        );
        $info->setCcType($data->getMoipCcrecurringType())->setCcLast4(substr($data->getMoipCcrecurringNumber(), -4))->setCcNumber($data->getMoipCcrecurringNumber())->setCcExpMonth($data->getMoipCcrecurringExpMonth())->setCcExpYear($data->getMoipCcrecurringExpYear())->setCcCid($data->getMoipCcrecurringCid())->setCcOwner($data->getMoipCcrecurringOwner())->setAdditionalData(serialize($additionaldata))->save()->setAdditionalInformation(serialize($additionaldata))->save();
        return $this;
    }
    public function prepareSave()
    {
       
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }
        $info->setCcCidEnc($info->encrypt($info->getRecurringCid()));
        $info->setCcNumber(null)->setRecurringCid(null);
        return $this;
    }
    public function prepare()
    {
        
        $info           = $this->getInfoInstance();
        $additionaldata = unserialize($info->getAdditionalData());
        $session        = Mage::getSingleton('checkout/session');
        $session->setMoipData($additionaldata);
    }
    public function validate()
    {
        
       
        parent::validate();
        $info           = $this->getInfoInstance();
        $currency_code  = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg       = false;
        $additionaldata = unserialize($info->getAdditionalData());
        
        $ccNumber = $info->getRecurringNumber();
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);
        $ccType = '';
        
        
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    public function validateCcNum($ccNumber)
    {
        $cardNumber = strrev($ccNumber);
        $numSum     = 0;
        for ($i = 0; $i < strlen($cardNumber); $i++) {
            $currentNum = substr($cardNumber, $i, 1);
            if ($i % 2 == 1) {
                $currentNum *= 2;
            }
            if ($currentNum > 9) {
                $firstNum   = $currentNum % 10;
                $secondNum  = ($currentNum - $firstNum) / 10;
                $currentNum = $firstNum + $secondNum;
            }
            $numSum += $currentNum;
        }
        return ($numSum % 10 == 0);
    }
    public function getVerificationRegEx()
    {
        $verificationExpList = array(
            'VI' => '/^[0-9]{3}$/',
            'MC' => '/^[0-9]{3}$/',
            'AE' => '/^[0-9]{4}$/',
            'DC' => '/^[0-9]{3}$/',
            'EO' => '/^[0-9]{3}$/',
            'HI' => '/^[0-9]{4}$/'
        );
        return $verificationExpList;
    }
    public function getValidaCPF($cpf = null)
    {
        if (empty($cpf)) {
            return false;
        } else if ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') {
            return false;
        } else {
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($cpf{$c} != $d) {
                    return false;
                }
            }
            return true;
        }
    }
    protected function _validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (!$expYear || !$expMonth || ($date->compareYear($expYear) == 1) || ($date->compareYear($expYear) == 0 && ($date->compareMonth($expMonth) == 1))) {
            return false;
        }
        return true;
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
        
        $api       = $this->getApiMoip();
        $response2 = $api->setCustomersPlans($profile, $payment);
        $response2 = json_decode($response2, false);
        $moip_code = $response2->code;
        $session = Mage::getSingleton('checkout/session');
        $session->setMoipBoletoLink(null);
        if ($moip_code) {
            $profile->setReferenceId($moip_code);
            $payment->setSkipTransactionCreation(true);
            
            if ((float) $profile->getInitAmount()) {
                $productItemInfo = new Varien_Object;
                $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_INITIAL);
                $productItemInfo->setPrice($profile->getInitAmount());
                
                $order    = $profile->createOrder($productItemInfo);
                
                $payment = $order->getPayment();
                $payment->setTransactionId($moip_code)->setIsTransactionClosed(1);
                $order->save();
                $profile->addOrderRelation($order->getId());
                $order->save();
                $order->sendNewOrderEmail();
                $payment->save();
                
                $transaction = Mage::getModel('sales/order_payment_transaction');
                $transaction->setTxnId($trans_id);
                $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                $transaction->setPaymentId($payment->getId());
                $transaction->setOrderId($order->getId());
                $transaction->setOrderPaymentObject($payment);
                $transaction->setIsClosed(1);
                $transaction->save();
                $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
            }
            
            $this->chargeRecurringProfile($profile, $moip_code);
            
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
    
    
    public function chargeRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, $trans_id){
    
        
       
            $productItemInfo = new Varien_Object;
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
            $productItemInfo->setPrice( $profile->getTaxAmount() + $profile->getBillingAmount() );

            $order = $profile->createOrder($productItemInfo);
            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();

            $payment = $order->getPayment();
            $payment->setTransactionId($trans_id)->setIsTransactionClosed(1);
            $order->save();
            $profile->addOrderRelation($order->getId());
            $order->sendNewOrderEmail();
            $payment->save();

            $transaction= Mage::getModel('sales/order_payment_transaction');
            $transaction->setTxnId($trans_id);
            $transaction->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
            $transaction->setPaymentId($payment->getId());
            $transaction->setOrderId($order->getId());
            $transaction->setOrderPaymentObject($payment);
            $transaction->setIsClosed( 1 );

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