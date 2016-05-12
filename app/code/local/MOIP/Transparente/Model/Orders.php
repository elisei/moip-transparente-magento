<?php
class MOIP_Transparente_Model_Orders extends Mage_Payment_Model_Method_Abstract {
	public function cancel(){
		$date = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d")-Mage::getStoreConfig('payment/moip_transparente_standard/vcmentoboleto'), date("Y")));
		$orders = Mage::getModel('sales/order')->getCollection();
		$orders->addFieldToFilter('status', 'boleto_impresso')
			->addFieldToFilter('created_at', array('to' => $date));
		$comment = "Pedido cancelado por boleto não pago.";
		foreach($orders as $order){
			$state = Mage_Sales_Model_Order::STATE_CANCELED;
			$status = 'canceled';
			$order->cancel();
			$order->sendOrderUpdateEmail(true, $comment);
			$order->setState($state, $status, $comment, $notified = true, $includeComment = true);
			$order->save();
			Mage::dispatchEvent('transparente_order_canceled', array("order" => $order));
		}
	}
}
?>
