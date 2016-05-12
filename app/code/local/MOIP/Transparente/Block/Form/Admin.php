<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MOIP_Transparente_Block_Form_Admin extends Mage_Payment_Block_Form {

	protected function _construct() {
		$this->setTemplate('MOIP/transparente/form/admin.phtml');
		parent::_construct();
	}





}
