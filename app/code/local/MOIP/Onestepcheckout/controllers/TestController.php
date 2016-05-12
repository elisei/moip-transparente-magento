<?php
class MOIP_Onestepcheckout_TestController extends Mage_Core_Controller_Front_Action
{
		public function indexAction()
		{
			echo Mage::getUrl('onestepcheckout/index/updatebillingform');
		}
}
