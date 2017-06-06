<?php
class MOIP_Transparente_Block_Oneclickbuy_ShippingAddress extends Mage_Checkout_Block_Onepage_Billing
{
    public function getAddressesHtmlSelect($type)
    {

        if ($this->isCustomerLoggedIn()) {
            $options = array();
            
               $address_shipping = $this->getCustomer()->getDefaultBilling();
               if ($address_shipping){
                    $address = Mage::getModel('customer/address')->load($address_shipping);
                    $htmlAddress = $address->format('text');
                }
            

            return $htmlAddress;
        }
        return ;
    }
}
