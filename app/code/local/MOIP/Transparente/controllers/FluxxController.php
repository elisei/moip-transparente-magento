<?php
/**
 * Transparente - Transparente Payment Module
 */
class MOIP_Transparente_FluxxController extends Mage_Core_Controller_Front_Action
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

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function notifyAction()
    {
      
        $api = $this->getApi();
        $api->generateLog("----- Entrada em Webhooks -----", 'Fluxx_WebHooks.log');
        if (!$this->getRequest()->getRawBody()) {
            $api->generateLog("Não foi possiviel ler o body", 'Fluxx_WebHooksError.log');
            return $this->set404();
        }
        $params       = $this->getRequest()->getParams();
        $json_fluxx    = $this->getRequest()->getRawBody();
        $autorization = $this->getRequest()->getHeader('Authorization');
        
        if ($params['validacao'] == Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno')) {

            $json_fluxx         = json_decode($json_fluxx, true);

            $newMethodForOrder = $this->getMageOrder($json_fluxx);
            if ($newMethodForOrder->getState()) {
                $this->getResponse()->setHttpResponseCode(201);
                $this->getResponse()->setBody($newMethodForOrder->getState());
                $api->generateLog($json_fluxx, 'Fluxx_WebHooks.log');
            } else {
                $api->generateLog($json_fluxx, 'Fluxx_WebHooksError.log');
                return $this->set404();
            }
                
            return $this;
        } else {
            $api->generateLog("Validação de comunicação INVÁLIDA: " . $params, 'Fluxx_WebHooksError.log');
            return $this->set404();
        }
    }

    public function getMageOrder($json_fluxx)
    {
        sleep(5);
        $api = $this->getApi();
        $api->generateLog($json_fluxx['ownId'], 'Fluxx_WebHooks.log');
        
        if ($json_fluxx['ownId']) {
            $api->generateLog("order id", 'Fluxx_WebHooks.log');
            $api->generateLog($json_fluxx['ownId'], 'Fluxx_WebHooks.log');
            $mage_order     = $json_fluxx['ownId'];
           
        } else {
            $api->generateLog("Fluxx ORDER não localizada: ", 'Fluxx_WebHooksError.log');
            return $this->set404();
        }
        $order   = Mage::getModel('sales/order')->load($mage_order, 'increment_id');

        $approved = $json_fluxx['fluxx']['auth_hold'] / 100;
        if($approved > 0){
            $order->setStatus('canceled_opportunity_available')->save();
            $autorizationcode = $json_fluxx['fluxx']['id'];
            $approved = Mage::helper('core')->currency($approved, true, false);
            $msg = Mage::helper('transparente')->__('Análise de crédito via Fluxx aprovou crédito de %s. Código de autorização: %s', $approved, $autorizationcode);
            $order->addStatusHistoryComment($msg);
            $order->save();
        }
        
        return $order;
    }
}
