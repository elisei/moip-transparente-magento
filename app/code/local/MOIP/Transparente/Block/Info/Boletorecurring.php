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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Payment
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Base payment iformation block
 */
class MOIP_Transparente_Block_Info_Boletorecurring extends Mage_Payment_Block_Info
{
    /**
     * Payment rendered specific information
     *
     * @var Varien_Object
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('MOIP/transparente/info/boletorecurring.phtml');
    }

    protected function _prepareInfo()
    {
        $order = $this->getInfo()->getOrder();
        $info_transaction = [];
        if ($order) {
            $order = $this->getInfo()->getOrder();

            $order = Mage::getModel('sales/order')->load((int)$order->getId());
            $payment = $order->getPayment()->getId();
            $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
                ->addPaymentIdFilter($payment);

            foreach ($collection as $col) {
                $info_transaction = $col->getAdditionalInformation();
            }

            return  $info_transaction;
        }
    }
}
