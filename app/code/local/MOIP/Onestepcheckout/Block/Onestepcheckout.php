<?php
class MOIP_Onestepcheckout_Block_Onestepcheckout extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getOnestepcheckout()     
     { 
        if (!$this->hasData('onestepcheckout')) {
            $this->setData('onestepcheckout', Mage::registry('onestepcheckout'));
        }
        return $this->getData('onestepcheckout');
        
    }
}
