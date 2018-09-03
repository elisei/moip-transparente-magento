<?php

class MOIP_Transparente_Block_Info_Boleto extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/boleto.phtml');
    }

    public function getMethodInstance()
    {
        if (!$this->hasMethodInstance()) {
            if ($this->getMethod()) {
                $instance = Mage::helper('payment')->getMethodInstance($this->getMethod());
                if ($instance) {
                    $instance->setInfoInstance($this);
                    $this->setMethodInstance($instance);

                    return $instance;
                }
            }
            Mage::throwException(Mage::helper('payment')->__('The requested Payment Method is not available.'));
        }

        return $this->_getData('method_instance');
    }
}
