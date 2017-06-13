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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Checkout default helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
 
class MOIP_Onestepcheckout_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_GUEST_CHECKOUT           = 'checkout/options/guest_checkout';

    protected $_agreements = null;

    public function valueForValidate($value, $key){

        $validatevaluesMin = array(
                                    'postcode'  => '8',
                                    'street_1'  => '-',
                                    'street_2'  => '-',
                                    'street_3'  => '0',
                                    'street_4'  => '1',
                                    'telephone' => '10',
                                    
                                    //'region_id' => '1'
                                );
        $validatevaluesMax = array(
                                    'postcode'  => '9',
                                    'street_1'  => '45',
                                    'street_2'  => '5',
                                    'street_3'  => '30',
                                    'street_4'  => '60',
                                    'telephone' => '14',
                                    
                                    //'region_id' => '99999'
                                );
        if($key != 'street_3'){
            if(strlen($value) > $validatevaluesMax[$key]){
                return strlen($value)." está maior que".$validatevaluesMax[$key];
            } elseif(strlen($value) <= $validatevaluesMin[$key]){
                return 1;
            } else {
                return !1;
            }    
        } else {
            return !1;
        }
        

    }
    private function _getRegionId($sigla){ 
        $region = Mage::getModel('directory/region')->loadByCode($sigla, 'BR'); 
        return $region->getRegionId();
        
    }

    private function getValidaCPF($cpf = null)
    {
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $count = strlen($cpf);
            if (empty($cpf)) {
                return !1;
            } elseif ($count != 11) {
                return !1;
            }

             elseif ($cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') {
                return !1;
            } else {
                for ($t = 9; $t < 11; $t++) {
                    for ($d = 0, $c = 0; $c < $t; $c++) {
                        $d += $cpf{$c} * (($t + 1) - $c);
                    }
                    $d = ((10 * $d) % 11) % 10;
                    if ($cpf{$c} != $d) {
                        return !1;
                    }
                }
                return 1;
            }
    }

    public function validate($billing_id = null, $shipping_id = null, $customer = null){
        $valido = 1;
        if($billing_id){
            $billing = Mage::getModel('customer/address')->load($billing_id);

            $data['postcode']     = $billing->getPostcode();
            $data['street_1']     = $billing->getStreet(1);
            $data['street_2']     = $billing->getStreet(2);
            $data['street_3']     = $billing->getStreet(3);
            $data['street_4']     = $billing->getStreet(4);
            $data['telephone']    = $billing->getTelephone();
            $label_campo = array(
                                        'postcode'  => 'Cep',
                                        'street_1'  => 'Rua, Avendida, Travessa (Logradouro)',
                                        'street_2'  => 'Número de sua residência.',
                                        'street_3'  => 'Complemento de sua residência.',
                                        'street_4'  => 'Bairro',
                                        'telephone' => 'Telefone',
                                    );
            
            $estado = $this->_getRegionId($billing->getRegionCode());
            if(!$billing->getRegionId()){

                if(!$billing->getRegion()){
                    $valido = !1;
                    Mage::getSingleton('core/session')->addError('O campo do endereço, Estado (UF), está inválido.'); 
                }
                
            }
            
            foreach ($data as $key => $value) {
                $valid = $this->valueForValidate($value, $key);
                if($valid){
                    $valido = !1;
                    Mage::getSingleton('core/session')->addError('O campo '.$label_campo[$key].' está inválido.'); 
                }
            }
        }
        if($customer){
           $valid = $this->getValidaCPF($customer->getTaxvat());
           if(!$valid){
                    $valido = !1;
                    Mage::getSingleton('core/session')->addError('O campo CPF está inválido.'); 
            }
        }
        
        
        return $valido;

    }
    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Retrieve checkout quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function formatPrice($price)
    {
        return $this->getQuote()->getStore()->formatPrice($price);
    }

    public function convertPrice($price, $format=true)
    {
        return $this->getQuote()->getStore()->convertPrice($price, $format);
    }

    public function getRequiredAgreementIds()
    {
        if (is_null($this->_agreements)) {
            if (!Mage::getStoreConfigFlag('checkout/options/enable_agreements')) {
                $this->_agreements = array();
            } else {
                $this->_agreements = Mage::getModel('checkout/agreement')->getCollection()
                    ->addStoreFilter(Mage::app()->getStore()->getId())
                    ->addFieldToFilter('is_active', 1)
                    ->getAllIds();
            }
        }
        return $this->_agreements;
    }

    /**
     * Get onepage checkout availability
     *
     * @return bool
     */
    public function canOnepageCheckout()
    {
        return (bool)Mage::getStoreConfig('checkout/options/onepage_checkout_enabled');
    }

    /**
     * Get sales item (quote item, order item etc) price including tax based on row total and tax amount
     *
     * @param   Varien_Object $item
     * @return  float
     */
    public function getPriceInclTax($item)
    {
        if ($item->getPriceInclTax()) {
            return $item->getPriceInclTax();
        }
        $qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
        $price = (floatval($qty)) ? ($item->getRowTotal() + $item->getTaxAmount())/$qty : 0;
        return Mage::app()->getStore()->roundPrice($price);
    }

    /**
     * Get sales item (quote item, order item etc) row total price including tax
     *
     * @param   Varien_Object $item
     * @return  float
     */
    public function getSubtotalInclTax($item)
    {
        if ($item->getRowTotalInclTax()) {
            return $item->getRowTotalInclTax();
        }
        $tax = $item->getTaxAmount();
        return $item->getRowTotal() + $tax;
    }

    public function getBasePriceInclTax($item)
    {
        $qty = ($item->getQty() ? $item->getQty() : ($item->getQtyOrdered() ? $item->getQtyOrdered() : 1));
        $price = (floatval($qty)) ? ($item->getBaseRowTotal() + $item->getBaseTaxAmount())/$qty : 0;
        return Mage::app()->getStore()->roundPrice($price);
    }

    public function getBaseSubtotalInclTax($item)
    {
        $tax = ($item->getBaseTaxBeforeDiscount() ? $item->getBaseTaxBeforeDiscount() : $item->getBaseTaxAmount());
        return $item->getBaseRowTotal()+$tax;
    }

    /**
     * Send email id payment was failed
     *
     * @param Mage_Sales_Model_Quote $checkout
     * @param string $message
     * @param string $checkoutType
     * @return Mage_Checkout_Helper_Data
     */
    public function sendPaymentFailedEmail($checkout, $message, $checkoutType = 'onepage')
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = Mage::getStoreConfig('checkout/payment_failed/template', $checkout->getStoreId());

        $copyTo = $this->_getEmails('checkout/payment_failed/copy_to', $checkout->getStoreId());
        $copyMethod = Mage::getStoreConfig('checkout/payment_failed/copy_method', $checkout->getStoreId());
        if ($copyTo && $copyMethod == 'bcc') {
            $mailTemplate->addBcc($copyTo);
        }

        $_reciever = Mage::getStoreConfig('checkout/payment_failed/reciever', $checkout->getStoreId());
        $sendTo = array(
            array(
                'email' => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/email', $checkout->getStoreId()),
                'name'  => Mage::getStoreConfig('trans_email/ident_'.$_reciever.'/name', $checkout->getStoreId())
            )
        );

        if ($copyTo && $copyMethod == 'copy') {
            foreach ($copyTo as $email) {
                $sendTo[] = array(
                    'email' => $email,
                    'name'  => null
                );
            }
        }
        $shippingMethod = '';
        if ($shippingInfo = $checkout->getShippingAddress()->getShippingMethod()) {
            $data = explode('_', $shippingInfo);
            $shippingMethod = $data[0];
        }

        $paymentMethod = '';
        if ($paymentInfo = $checkout->getPayment()) {
            $paymentMethod = $paymentInfo->getMethod();
        }

        $items = '';
        foreach ($checkout->getItemsCollection() as $_item) {
            /* @var $_item Mage_Sales_Model_Quote_Item */
            $items .= $_item->getProduct()->getName() . '  x '. $_item->getQty() . '  '
                    . $checkout->getStoreCurrencyCode() . ' ' . $_item->getProduct()->getFinalPrice($_item->getQty()) . "\n";
        }
        $total = $checkout->getStoreCurrencyCode() . ' ' . $checkout->getGrandTotal();

        foreach ($sendTo as $recipient) {
            $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$checkout->getStoreId()))
                ->sendTransactional(
                    $template,
                    Mage::getStoreConfig('checkout/payment_failed/identity', $checkout->getStoreId()),
                    $recipient['email'],
                    $recipient['name'],
                    array(
                        'reason' => $message,
                        'checkoutType' => $checkoutType,
                        'dateAndTime' => Mage::app()->getLocale()->date(),
                        'customer' => $checkout->getCustomerFirstname() . ' ' . $checkout->getCustomerLastname(),
                        'customerEmail' => $checkout->getCustomerEmail(),
                        'billingAddress' => $checkout->getBillingAddress(),
                        'shippingAddress' => $checkout->getShippingAddress(),
                        'shippingMethod' => Mage::getStoreConfig('carriers/'.$shippingMethod.'/title'),
                        'paymentMethod' => Mage::getStoreConfig('payment/'.$paymentMethod.'/title'),
                        'items' => nl2br($items),
                        'total' => $total
                    )
                );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

    protected function _getEmails($configPath, $storeId)
    {
        $data = Mage::getStoreConfig($configPath, $storeId);
        if (!empty($data)) {
            return explode(',', $data);
        }
        return false;
    }

    /**
     * Check if multishipping checkout is available.
     * There should be a valid quote in checkout session. If not, only the config value will be returned.
     *
     * @return bool
     */
    public function isMultishippingCheckoutAvailable()
    {
        $quote = $this->getQuote();
        $isMultiShipping = (bool)(int)Mage::getStoreConfig('shipping/option/checkout_multiple');
        if ((!$quote) || !$quote->hasItems()) {
            return $isMultiShipping;
        }
        $maximunQty = (int)Mage::getStoreConfig('shipping/option/checkout_multiple_maximum_qty');
        return $isMultiShipping
            && !$quote->hasItemsWithDecimalQty()
            && $quote->validateMinimumAmount(true)
            && (($quote->getItemsSummaryQty() - $quote->getItemVirtualQty()) > 0)
            && ($quote->getItemsSummaryQty() <= $maximunQty)
            && !$quote->hasNominalItems()
        ;
    }

    /**
     * Check is allowed Guest Checkout
     * Use config settings and observer
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|Mage_Core_Model_Store $store
     * @return bool
     */
    public function isAllowedGuestCheckout(Mage_Sales_Model_Quote $quote, $store = null)
    {
        if ($store === null) {
            $store = $quote->getStoreId();
        }
        $guestCheckout = Mage::getStoreConfigFlag(self::XML_PATH_GUEST_CHECKOUT, $store);

        if ($guestCheckout == true) {
            $result = new Varien_Object();
            $result->setIsAllowed($guestCheckout);
            Mage::dispatchEvent('checkout_allow_guest', array(
                'quote'  => $quote,
                'store'  => $store,
                'result' => $result
            ));

            $guestCheckout = $result->getIsAllowed();
        }

        return $guestCheckout;
    }
	public function onlyProductDownloadable(){		
		$itemProduct=$this->getQuote()->getAllVisibleItems();
		//echo sizeof($itemProduct);exit;
		//$istrue;
		foreach($itemProduct as $item){
				if($item->getProduct()->getTypeId()!='downloadable' AND $item->getProduct()->getTypeId()!='virtual')
					return false;
		}
		return true;
	}
	public function haveProductDownloadable(){		
		$itemProduct=$this->getQuote()->getAllVisibleItems();
		foreach($itemProduct as $item){
				if($item->getProduct()->getTypeId()=='downloadable')
					return true;
		}
		
		return false;		
	}	
	public function issubscribed(){
		$issubscribe= Mage::getModel('newsletter/subscriber')->loadByCustomer(Mage::getSingleton('customer/session')->getCustomer())->isSubscribed();
		if(!Mage::getSingleton('customer/session')->isLoggedIn() or !$issubscribe){
			return true;  
		}
		else{
			return false;
		}
	}
	
}
