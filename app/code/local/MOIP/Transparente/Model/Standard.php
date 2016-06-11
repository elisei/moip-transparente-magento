<?php
class MOIP_Transparente_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'moip_transparente_standard';
    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'transparente/form_admin';
    protected $_infoBlockType = 'transparente/info_admin';
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_allowCurrencyCode = array('BRL');
    protected $_canFetchTransactionInfo = true;

    public function assignData($data)
    {
        $additionaldata = array();
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info           = $this->getInfoInstance();
       
       
        $quote               = $info->getQuote();
        $json_order          = $this->getApi()->getDados($quote);
        $IdMoip              = $this->getApi()->getOrderIdMoip($json_order);
       	$decode = json_decode($IdMoip, true);
        $link_boleto = $decode['_links']['checkout']['payBoleto']['redirectHref'];
        $link_cc = $decode['_links']['checkout']['payCreditCard']['redirectHref'];
        $info           = $this->getInfoInstance();
        $additionaldata = array(
            'link_boleto' => $link_boleto,
            'link_cc' => $link_cc
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
        $api = Mage::getModel('transparente/admin');
        return $api;
    }
    
}