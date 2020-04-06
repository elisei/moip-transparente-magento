<?php

class MOIP_Transparente_Block_Info_Fluxx extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/fluxx.phtml');
    }

    public function getMoipLineCode()
    {
        $data = $this->getMoipData();
        return $data['line_code'];
    }

    public function getMoipLinkPrint()
    {
        $data = $this->getMoipData();
        return $data['print_href'];
    }

    public function getExpirationDate()
    {
        $data = $this->getMoipData();
        return $data['expiration_date'];
    }
    
    public function viewInMoip()
    {
        $data = $this->getMoipData();
        if ($data['ambiente'] == "teste") {
            $url = MOIP_Transparente_Model_Api::ACCOUNT_TEST."orders/".$data['moip_order_id'];
        } else {
            $url = MOIP_Transparente_Model_Api::ACCOUNT_PROD."orders/".$data['moip_order_id'];
        }
        return $url;
    }

    public function viewInFluxx()
    {
        $data = $this->getMoipData();
        if ($data['ambiente'] == "teste") {
            $url = MOIP_Transparente_Model_Fluxx_Api::ACCOUNT_TEST."?ref_id=".$data['moip_fluxx_id'];
        } else {
            $url = MOIP_Transparente_Model_Fluxx_Api::ACCOUNT_PROD."?ref_id=".$data['moip_fluxx_id'];
        }
        return $url;
    }

    protected function getMoipData()
    {
        $additional = $this->getInfo()->getAdditionalData();
        return unserialize($additional);
    }
}
