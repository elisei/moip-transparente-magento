<?php
class MOIP_Transparente_Model_Method_Boleto extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'moip_boleto';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/adminhtml_form_boleto';
    protected $_infoBlockType = 'transparente/info_boleto';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = true;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canFetchTransactionInfo = true;

    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info           = $this->getInfoInstance();
        $additionaldata = array();
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
        $info          = $this->getInfoInstance();
        $currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
        $errorMsg      = false;
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
        $info                = $this->getInfoInstance();
        $quote               = $info->getQuote();
        $additionaldata      = unserialize($info->getAdditionalData());
        $json_order          = $this->getApi()->getDados($quote);
        $IdMoip              = $this->getApi()->getOrderIdMoip($json_order);
        $json_payment        = $this->getApi()->getPaymentJsonBoleto($info, $quote);
        $payment             = $this->getApi()->generatePayment($json_payment, $IdMoip);
        $additionaldataAfter = array(
            'token_moip' => $json_order,
            'response_moip' => $payment,
            'order_moip' => (string) $IdMoip
        );
        $additionaldata      = array_merge($additionaldata, $additionaldataAfter);
        $info->setAdditionalData(serialize($additionaldataAfter))->save()->setAdditionalInformation(serialize($additionaldataAfter))->save();
        Mage::log('json ' . $json_payment, null, 'MOIP_PaymentJsonSend.log', true);
        $this->prepare();
        if (isset($payment->errors)) {
            foreach ($payment->errors as $key => $value) {
                $erros = (string) $value->description . " " . $erros;
            }
            $session = Mage::getSingleton('checkout/session');
            $session->setMoipError($erros);
            Mage::log('json' . $json_payment, null, 'MOIP_ErrorPayment.log', true);
            Mage::log('Erro no pagamento moip order' . $payment, null, 'MOIP_ErrorPayment.log', true);
            return Mage::getUrl('transparente/standard/cancel', array(
                '_secure' => true
            ));
        } else {
            return Mage::getUrl('transparente/standard/redirect', array(
                '_secure' => true
            ));
        }
    }
}