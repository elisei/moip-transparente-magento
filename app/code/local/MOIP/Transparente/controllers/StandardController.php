<?php
/**
 * Transparente - Transparente Payment Module
 */
class MOIP_Transparente_StandardController extends Mage_Core_Controller_Front_Action
{
    public function getStandard()
    {
        return Mage::getSingleton('transparente/standard');
    }

    public function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    public function set404()
    {
        $this->getResponse()->setHeader('HTTP/1.1', '404 Not Found');
        $this->getResponse()->setHeader('Status', '404 File not found');
        $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_NO_ROUTE_PAGE);
        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('defaultNoRoute');
        }
    }

    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getTransparenteStandardQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
                $state   = Mage_Sales_Model_Order::STATE_CANCELED;
                $status  = 'canceled';
                $comment = $session->getMoipError();
                $order->setState($state, $status, $comment, $notified = true, $includeComment = true);
                $order->save();
            }
        }
        $this->_redirect('checkout/onepage/failure');
    }

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function successAction()
    {
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        $api = $this->getApi();
        if (!$this->getRequest()->getRawBody()) {
            $api->generateLog("Não foi possiviel ler o body", 'MOIP_WebHooksError.log');
            return $this->set404();
        }
        $params       = $this->getRequest()->getParams();
        $json_moip    = $this->getRequest()->getRawBody();
        $autorization = $this->getRequest()->getHeader('Authorization');
        $api->generateLog("autorizationAction: " . $json_moip, 'MOIP_WebHooks.log');
        if ($params['validacao'] == $this->getStandard()->getConfigData('validador_retorno')) {
            $json_moip         = json_decode($json_moip);
            $newMethodForOrder = $this->getMageOrder($json_moip);
            if ($newMethodForOrder) {
                $this->getResponse()->setHttpResponseCode(201);
                $this->getResponse()->setBody($newMethodForOrder->getState());
                $api->generateLog(json_encode($json_moip), 'MOIP_WebHooks.log');
            } else {
                $api->generateLog(json_encode($json_moip), 'MOIP_WebHooksError.log');
                return $this->set404();
            }
                
            return $this;
        } else {
            $api->generateLog("Validação de comunicação INVÁLIDA: " . $params, 'MOIP_WebHooksError.log');
            return $this->set404();
        }
    }

    public function getMageOrder($json_moip)
    {
        $api = $this->getApi();
        if (isset($json_moip->resource->order->id)) {
            //recupera infos para ORD.*
            $moip_order  = $json_moip->resource->order->id;
            $status_moip = $json_moip->resource->order->status;
        } elseif (isset($json_moip->resource->payment)) {
            //recupera infos para PAY.*
            $moip_order  = $json_moip->resource->payment->_links->order->title;
            $amount      = $json_moip->resource->payment->amount->total / 100;
            $status_moip = $json_moip->resource->payment->status;
            if (isset($json_moip->resource->payment->cancellationDetails)) {
                $details_cancel = $json_moip->resource->payment->cancellationDetails->description;
            } else {
                $details_cancel = "Indefinido";
            }
        } else {
            $api->generateLog("MOIP ORDER não localizada: ", 'MOIP_WebHooksError.log');
            return $this->set404();
        }

        
        $order   = Mage::getModel('sales/order')->load($moip_order, 'ext_order_id');
        $payment = $order->getPayment();
        $order_state = $order->getState();

        if ($status_moip == "AUTHORIZED" || $status_moip == "PAID") {
            if ($order_state != Mage_Sales_Model_Order::STATE_PROCESSING) {
                if ($order->canInvoice()) {
                    $test = $payment->getMethodInstance()->authorize($payment, $amount);
                    try {
                        return $order;
                    } catch (Exception $e) {
                        $api->generateLog("MOIP {$moip_order} não foi processada erro: " . $e, 'MOIP_WebHooksError.log');
                        return $this->set404();
                    }
                } else {
                    return $this->set404();
                }
            }
        } elseif ($status_moip == "CANCELLED" || $status_moip == "NOT_PAID") {
            if ($order_state != Mage_Sales_Model_Order::STATE_CANCELED) {
                $transactionAuth = $payment->getMethodInstance()->cancel($payment);
                if ($transactionAuth) {
                    if ($order->canCancel()) {
                        $order->cancel()->save();
                        Mage::getModel('transparente/email_cancel')->sendEmail($order, $details_cancel);
                        $translate_details = Mage::helper('transparente')->__($details_cancel);
                        $msg               = Mage::helper('transparente')->__('Email de cancelamento enviado ao cliente. Motivo real: %s, motivo exibido ao cliente: %s', $details_cancel, $translate_details);
                        $order->addStatusHistoryComment($msg);
                        $order->save();
                        try {
                            return $order;
                        } catch (Exception $e) {
                            $api->generateLog("MOIP {$moip_order} não foi processada erro: " . $e, 'MOIP_WebHooksError.log');
                            return $this->set404();
                        }
                    } else {
                        return $this->set404();
                    }
                } else {
                    return $this->set404();
                }
            }
        } else {
            return $this->set404();
        }
    }
}
