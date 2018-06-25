<?php
class MOIP_Transparente_Block_Info_Tef extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/tef.phtml');
    }

    public function getMoipTransfHref(){
        $data = $this->getMoipData();
       return $data['moip_transf_href'];
    }

    public function getNameBank(){
        $data = $this->getMoipData();
        return $data['moip_transf_bankName'];
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
