<?php

class MOIP_Transparente_Block_Oneclickbuy_PaymentMethod extends Mage_Payment_Block_Form_Container
{
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Check payment method model
     *
     * @param Mage_Payment_Model_Method_Abstract|null
     * @return bool
     */
    protected function _canUseMethod($method)
    {
        return $method && $method->canUseCheckout() && parent::_canUseMethod($method);
    }

    /**
     * Retrieve code of current payment method
     *
     * @return mixed
     */
    public function getSelectedMethodCode()
    {
        if ($method = $this->getQuote()->getPayment()->getMethod()) {
            return $method;
        }
        return false;
    }

    /**
     * Payment method form html getter
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getPaymentMethodFormHtml(Mage_Payment_Model_Method_Abstract $method)
    {
         return $this->getChildHtml('payment.method.' . $method->getCode());
    }

    /**
     * Return method title for payment selection page
     *
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getMethodTitle(Mage_Payment_Model_Method_Abstract $method)
    {
        $form = $this->getChild('payment.method.' . $method->getCode());
        if ($form && $form->hasMethodTitle()) {
            return $form->getMethodTitle();
        }
        return $method->getTitle();
    }

    /**
     * Payment method additional label part getter
     * @param Mage_Payment_Model_Method_Abstract $method
     */
    public function getMethodLabelAfterHtml(Mage_Payment_Model_Method_Abstract $method)
    {
        if ($form = $this->getChild('payment.method.' . $method->getCode())) {
            return $form->getMethodLabelAfterHtml();
        }
    }
    public function getCofre() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $data_array = array();
            $customerData = Mage::getSingleton( 'customer/session' )->getCustomer();
            $resource = Mage::getSingleton('core/resource');

            $readConnection = $resource->getConnection('core_read');
            $table = Mage::getConfig()->getTablePrefix().'moip_transparentev2';

            $query = 'SELECT * FROM ' . $table .' WHERE customer_id='.$customerData->getID().' AND moip_card_id IS NOT NULL';
            $results = $readConnection->fetchAll($query);

            if($results){
                return json_encode($results, true);
            } else {
                return 'false';
            }

        } else {
            return 'false';
        }

    }

    public function getChildParcelas(){
        return  $this->getLayout()->getBlock('moip.oneclickbuy.parcelas')->toHtml();
    }

}
