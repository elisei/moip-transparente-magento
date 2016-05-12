<?php
class MOIP_Transparente_Block_IndexController_Novaforma extends Mage_Core_Block_Template{
    public function __construct(){
		parent::__construct();
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('MOIP_Transparente_Block_Standard_Novaforma'));
		$this->renderLayout();
	}
	public function mostraCartao()
	{
		
	}
}	
?>
