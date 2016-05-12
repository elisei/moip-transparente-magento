<?php 

class Moip_Transparente_Model_Source_Holded extends Mage_Adminhtml_Model_System_Config_Source_Order_Status
{
	protected $_stateStatuses = Mage_Sales_Model_Order::STATE_HOLDED;
}