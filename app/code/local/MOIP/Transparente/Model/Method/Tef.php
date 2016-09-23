<?php
class MOIP_Transparente_Model_Method_Tef extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'moip_tef';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/form_tef';
    protected $_infoBlockType = 'transparente/info_tef';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canFetchTransactionInfo = true;
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info           = $this->getInfoInstance();
        $additionaldata = array(
            'banknumber_moip' => $data->getMoipTefBanknumber()
        );
        $info->setAdditionalData(serialize($additionaldata))->save()->setAdditionalInformation(serialize($additionaldata))->save();
        return $this;
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
        if ($errorMsg === false) {
            if (!in_array($currency_code, $this->_allowCurrencyCode)) {
                Mage::throwException(Mage::helper('transparente')->__('O Moip Não pode Transacionar pedidos feitos em  (' . $currency_code . ') verifique as configurações de Moeda do seu magento.'));
            }
        }
        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }
    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }
    public function getOrderPlaceRedirectUrl()
    {
        ignore_user_abort(true);
        $api                 = $this->getApi();
        $info                = $this->getInfoInstance();
        $quote               = $info->getQuote();
        $additionaldata      = unserialize($info->getAdditionalData());
        $json_order          = $this->getApi()->getDados($quote);
        $IdMoip              = $this->getApi()->getOrderIdMoip($json_order);
        $json_payment        = $this->getApi()->getPaymentJsonTef($info, $quote);
        $payment             = $this->getApi()->generatePayment($json_payment, $IdMoip);
        $additionaldataAfter = array(
            'token_moip' => $json_order,
            'response_moip' => $payment,
            'order_moip' => (string) $IdMoip
        );
        $additionaldata      = array_merge($additionaldata, $additionaldataAfter);
        $info->setAdditionalData(serialize($additionaldata))->save();
        $info->setAdditionalInformation(serialize($additionaldata))->save();
       
        $this->prepare();
        if (isset($payment->errors)) {
            foreach ($payment->errors as $key => $value) {
                $erros = (string) $value->description . " " . $erros;
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setMoipError($erros);
                $api->generateLog($json_payment, 'MOIP_ErrorPayment.log');
                $api->generateLog($payment, 'MOIP_ErrorPayment.log');
            return Mage::getUrl('transparente/standard/cancel', array(
                '_secure' => true
            ));
        } else {
            Mage::dispatchEvent('sales_order_place_moip_after',$additionaldataAfter);
            return Mage::getUrl('checkout/onepage/success', array(
                '_secure' => true
            ));
        }
    }
}