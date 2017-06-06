<?php
class MOIP_Transparente_Block_Standard_Details extends Mage_Sales_Block_Items_Abstract {

	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('sales/order/info.phtml');
    }

    protected function _prepareLayout()
    {
        if ( $this->getOrder()) {
           
      
                $this->setChild(
                    'payment_info',
                    $this->helper('payment')->getInfoBlock($this->getOrder()->getPayment())
                );
          }
    }
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }

	public function getOrder(){
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        $current_order    =    Mage::getModel('sales/order')->getCollection()
                            ->addFieldToFilter('increment_id', $orderId);

        if($current_order) {
            foreach( $current_order as $order )    {
                $final = $order;
                break;
            }
        }
        return $final;
	}

    public function getIconPgto($_code){

        $icon_method = array(
                                "moip_cc" => '<i class="fa fa-credit-card" aria-hidden="true"></i>',
                                "moip_boleto" => '<i class="fa fa-barcode" aria-hidden="true"></i>',
                                "moip_tef" => '<i class="fa fa-money" aria-hidden="true"></i>',
                                "default" => '<i class="fa fa-shopping-cart" aria-hidden="true"></i>'
                             );
        return (isset($icon_method[$_code])? $icon_method[$_code] : $icon_method['default']);
           
    }
}
?>