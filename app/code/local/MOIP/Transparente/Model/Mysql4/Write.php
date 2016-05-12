<?php
class MOIP_Transparente_Model_Mysql4_Write extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('transparente/write', 'entity_id_moip');
    }
}
?>
