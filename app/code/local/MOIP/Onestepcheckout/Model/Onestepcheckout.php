<?php
class MOIP_Onestepcheckout_Model_Onestepcheckout extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('onestepcheckout/onestepcheckout');
    }
}
