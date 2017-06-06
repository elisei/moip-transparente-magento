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
                    'value'=>$address->getId(),
                    'label'=>$address->format('oneline'),
                    'title' => 'Selecione o endereço de envio',
                );
            }

            $addressId = $this->getAddress()->getId();
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
                ->setClass('address-select')
                ->setTitle('Selecione seu endereço')
                ->setExtraParams('')
                ->setValue($addressId)
                ->setOptions($options);

            $select->addOption('', Mage::helper('checkout')->__('Salvar um novo endereço'));

            return $select->getHtml();
        }
        return '';
    }

    public function getAddressesHtmlInput($type)
    {

        if ($this->isCustomerLoggedIn()) {
            $is_default = 0;
            $addressId = $this->getAddress()->getId();
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
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                if($address->getDefaultBilling()){
                    $is_default = "1";
                } else {
                    $is_default = $this->getCustomer()->getPrimaryBillingAddress()->getId();
                }

                $options[] = array(
                    'value'=>$address->getId(),
                    'label'=>$address->format('text'),
                    'id_default' =>  $is_default, 
                );
            }


            $select = $this->getLayout()->createBlock('MOIP_Onestepcheckout_Block_Checkout_Onepage_Radio_InputRadio')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
                ->setTitle('Selecione seu endereço')
                ->setExtraParams('')
                ->setValue($addressId)
                ->setOptions($options);

                $select->addOption('0', Mage::helper('checkout')->__('<div class="h4 address-title a-center">Novo endereço</div>'));

          

            return $select->getHtml();
        }
        return '';
    }

  

}
