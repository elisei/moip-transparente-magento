<?php
class MOIP_Onestepcheckout_Block_Checkout_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing
{

    public function getCountries()
    {
        return Mage::getResourceModel('directory/country_collection')->loadByStore();
    }
    
    public function getAddressesHtmlSelect($type)
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
                );
            }

            $addressId = $this->getAddress()->getCustomerAddressId();
            if (empty($addressId)) {
                if ($type=='billing') {
                    $address = $this->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $this->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
                }
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setTitle(Mage::helper('checkout')->__('Endereço de cobrança'))
                ->setClass('address-select form-control')
                ->setExtraParams('onchange="EditAddress(this.value,\''.$type.'\')"')
                ->setValue($addressId)
                ->setOptions($options);

            $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
    }

    public function getAddressesHtmlInput($type)
    {

       if ($this->isCustomerLoggedIn()) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('text'),
                    'params' => 'onchange="EditAddress(this.value,\''.$type.'\')"' 
                );
            }

            $addressId = $this->getAddress()->getCustomerAddressId();
            if (empty($addressId)) {
                if ($type=='billing') {
                    $address = $this->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $this->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
                }
            }

            $select = $this->getLayout()->createBlock('onestepcheckout/checkout_onepage_radio_inputRadio')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setTitle(Mage::helper('checkout')->__('Endereço de cobrança'))
                ->setClass('address-select form-control')
                ->setExtraParams('onchange="EditAddress(this.value,\''.$type.'\')"')
                ->setValue($addressId)
                ->setOptions($options);
            return $select->getHtml();
        }
        return '';
    }

  

}
