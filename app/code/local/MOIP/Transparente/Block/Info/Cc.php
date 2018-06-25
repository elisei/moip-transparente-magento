<?php
class MOIP_Transparente_Block_Info_Cc extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/cc.phtml');
    }

    public function getBrand(){
        $data = $this->getMoipData();
       return $data['moip_card_brand'];
    }

    public function getInstallment(){
        $data = $this->getMoipData();
       return $data['moip_card_installmentCount'];
    }

    public function getFullname(){
        $data = $this->getMoipData();
       return $data['moip_card_fullname'];
    }


    public function getFirst6(){
        $data = $this->getMoipData();
       return $data['moip_card_first6'];
    }

    public function getLast4(){
        $data = $this->getMoipData();
       return $data['moip_card_last4'];
    }
    public function viewInMoip(){
        $data = $this->getMoipData();
        if($data['ambiente'] == "teste"){
            $url = MOIP_Transparente_Model_Api::ACCOUNT_TEST."orders/".$data['moip_order_id'];
        } else {
            $url = MOIP_Transparente_Model_Api::ACCOUNT_PROD."orders/".$data['moip_order_id'];
        }
        return $url;
    }
    protected function getMoipData(){
        $additional = $this->getInfo()->getAdditionalData();
        return unserialize($additional);
    }
}
