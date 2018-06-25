<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Customer
 * @copyright  Copyright (c) 2006-2018 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MOIP_Onestepcheckout_Block_Checkout_Customer_Tipopessoa extends Mage_Customer_Block_Widget_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/onestepcheckout/customer/tipopessoa.phtml');
    }

    public function isEnabled()
    {
        return (bool)$this->_getAttribute('tipopessoa')->getIsVisible();
    }

    public function isRequired()
    {
        return (bool)$this->_getAttribute('tipopessoa')->getIsRequired();
    }

    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function getOptionsValues(){
        $attribute = Mage::getModel('eav/config')->getAttribute('customer', 'tipopessoa');
        $allOptions = $attribute->getSource()->getAllOptions(true, true);
        return $allOptions;
    }
}
