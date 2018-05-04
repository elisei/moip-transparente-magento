<?php 
class MOIP_Transparente_Model_Mysql4_Transparente_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('transparente/transparente');
    }
}
