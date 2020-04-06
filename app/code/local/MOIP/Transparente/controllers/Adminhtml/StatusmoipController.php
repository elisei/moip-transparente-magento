<?php
class MOIP_Transparente_Adminhtml_StatusmoipController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        $aclResource = 'sales/order/actions/setstate';
        return Mage::getSingleton('admin/session')->isAllowed($aclResource);
    }

    public function setstateAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
       
        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
            

            if (strlen($order->getExtOrderId()) < 14) {
               
                $method = $order->getPayment()->getMethodInstance()->getCode();
                if ($method == "moip_cc" || $method == "moip_boleto" || $method == "moip_tef") {
                    $this->_getSession()->addError($this->__('O pedido %s não foi encontrado na conta moip, requer consulta manual e caso não localizado cancelar manualmente e solicitar recompra.', $order->getIncrementId()));
                }
               
            } else {
                 
                if ($method == "moip_cc" || $method == "moip_boleto" || $method == "moip_tef") {
                    $this->getStatusByORDNew($order);
                }
            }
        }
        $this->_redirect('adminhtml/sales_order/index/');
    }

    public function getStatusByORDNew($order)
    {
            $api            = $this->getApi();
            $moip_ord       = $order->getExtOrderId();
            $state          = $order->getState();
            $order_id       = $order->getIncrementId();
            $order_state    = $order->getState();

            if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
                $url = "https://sandbox.moip.com.br/v2/orders/{$moip_ord}";
                $oauth = trim(Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev'));
                $header = "Authorization: OAuth {$oauth}";
            } else {
                $url = "https://api.moip.com.br/v2/orders/{$moip_ord}";
                $oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod') ;
                $header = "Authorization: OAuth {$oauth}";
            }

            $result = array();
            $ch     = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    $header
                ));
            curl_setopt($ch, CURLOPT_USERAGENT, 'MoipMagento/2.0.0');
            $responseBody = curl_exec($ch);
            $info_curl = curl_getinfo($ch);
            curl_close($ch);
            $response_decode = json_decode($responseBody, true);
            $api->generateLog($order_id. " state moip " .$response_decode['status'], 'MOIP_StateAll.log');

            if($response_decode['status'] == "PAID"){
                 $payment = $order->getPayment();
                 $payment->getMethodInstance()->authorize($payment,  $order->getGrandTotal());
                  $this->_getSession()->addSuccess($this->__('O pedido %s está pago na conta moip.', $order->getIncrementId()));


                 $api->generateLog($order_id. " state apos ação moip " .$order->getState(), 'MOIP_StateAll.log');


            } elseif($response_decode['status'] == "NOT_PAID"){
                 if ($order->canCancel()) {
                    $details_cancel = "Indefinido";
                    $order->cancel()->save();
                    Mage::getModel('transparente/email_cancel')->sendEmail($order, $details_cancel);
                    $translate_details = Mage::helper('transparente')->__($details_cancel);
                    $msg               = Mage::helper('transparente')->__('Email de cancelamento enviado ao cliente. Motivo real: %s, motivo exibido ao cliente: %s', $details_cancel, $translate_details);
                    $order->addStatusHistoryComment($msg);
                    $order->save();
                    $this->_getSession()->addError($this->__('O pedido %s está cancelado na conta moip.', $order->getIncrementId()));
                    $api->generateLog($order_id. " state apos ação moip " .$order->getState(), 'MOIP_StateAll.log');
                    
                }
            }
    }

    
    public function getApi()
    {
        return Mage::getModel('transparente/api');
    }

    
    
}
