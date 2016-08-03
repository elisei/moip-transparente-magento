<?php

class MOIP_Transparente_Block_Adminhtml_System_Config_Form_Fieldset_Modules_Oauth 
extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{

    const EndPointProd  = "https://api.moip.com.br/oauth/authorize";
    const EndPointDev   = "https://sandbox.moip.com.br/oauth/authorize";
    const AppIdDev      = "APP-9MUFQ39Y4CQU";
    const AppIdProd     = "APP-AKYBMMVU1FL1";
    const SCOPE_APP     = "CREATE_ORDERS|VIEW_ORDERS|CREATE_PAYMENTS|VIEW_PAYMENTS";
    const responseType  = "CODE";
    
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s">
                <td colspan="5">
                    <h4 id="%s">%s</h4>
                    <p class="subheading-note" style="font-size:11px;font-style:italic;color:#666; margin-bottom:30px;"><span>%s</span></p>
                    <div class="action-moip">%s</div>
                </td>
            </tr>',
            $element->getHtmlId(),  $element->getHtmlId(), $this->getTitleSetup(), $this->getTextAmbiente(), $this->getActionSetup()
        );
    }

    public function getTitleSetup(){
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        if(!$validacao){
            $title = "1º Passo";
        } else {
            $oauth      = $this->getIfOauth();
            $webhooks   = $this->getifWebHooks();
            if(!$oauth) {
                $title = "2º Passo - Autorizar sua Loja a Realizar vendas";
            } else {
                if($webhooks){ 
                    $title = "Configuração concluídas com sucesso";
                } else {
                    $title = "3ª Habilitar Retorno da transação";
                }
            }

        }
        return $title;
    }

    public function getIfOauth(){

        if($this->getAmbiente() == "teste"){
            $oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');    
        } else {
            $oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');    
        }
        return $oauth;
        
    }

    public function getifWebHooks(){
        if($this->getAmbiente() == "teste"){
            
            $webhooks   = Mage::getSingleton('transparente/standard')->getConfigData('webhook_key_dev');
        } else {
            $webhooks   = Mage::getSingleton('transparente/standard')->getConfigData('webhook_key_prod');
        }
        return $webhooks;
    }

    public function getAmbiente(){
        return Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
    }

    public function getTextAmbiente(){
        if(Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste"){
            $texto = "O ambiente escolhido é de <b>Teste (Sandbox Moip)</b> - O Moip não irá comunicar as vendas a operadora de cartão, essa versão é apenas para testes.";
        } else {
            $texto = "O ambiente escolhido é de <b>Produção</b> - Suas vendas serão processadas normalmente.";
        }
        return $texto;
    }

    public function getUrlClearMoip(){
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        $url_frontend = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, array(
            '_nosid' => true,
            'store_scope' => 'default',
            '_secure' => true,
            '_type' => 'direct_link'
        ));
        return $url_frontend.'Transparente/standard/ClearMoip/validacao/'.$validacao.'/';
    }

    public function getUrlOuathMoip(){
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        $url_frontend = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, array(
            '_nosid' => true,
            'store_scope' => 'default',
            '_secure' => true,
            '_type' => 'direct_link'
        ));
        return $url_frontend.'Transparente/standard/Oauth/validacao/'.$validacao.'/';
    }
    public function getUrlEnableWebhooks(){
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        $url_frontend = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, array(
            '_nosid' => true,
            'store_scope' => 'default',
            '_secure' => true,
            '_type' => 'direct_link'
        ));
        return $url_frontend.'Transparente/standard/EnableWebhooks/validacao/'.$validacao.'/';
    }
    public function getRedirectUri(){
        $redirectUri = $this->getUrlOuathMoip();
        $redirectUri = urlencode($redirectUri);
        $redirectUri = "http://moip.o2ti.com/magento/redirect/?client_id=".$redirectUri; //Aqui voce pode construir sua url URI no entanto precisa estar exatamente como indicado no app construido...
        return $redirectUri;
    }

    public function getLinkMoipApp(){
        
        if(Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste"){
            $endpoint       = self::EndPointDev;
            $responseType   = self::responseType;
            $appId          = self::AppIdDev; 
            $scope          = self::SCOPE_APP;
            $redirectUri    = $this->getRedirectUri();
        } else {
            $endpoint       = self::EndPointProd;
            $responseType   = self::responseType;
            $appId          = self::AppIdProd; 
            $scope          = self::SCOPE_APP;
            $redirectUri    = $this->getRedirectUri();
        }

        $link = $endpoint.'?responseType='.$responseType.'&appId='.$appId.'&redirectUri='.$redirectUri.'&scope='.$scope;
        return $link;
    }

    public function getSrcBtnMoipOauth(){
        $src =  $this->getSkinUrl('MOIP/transparente/imagem/btn-login-moip.png');
        return $src;
    }

    public function getActionSetup(){
        $validacao  = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        $oauth      = $this->getIfOauth();
        $webhooks   = $this->getifWebHooks();
        if($validacao){
            if($oauth){
                if($webhooks){
                    $texto      = "Apagar configuração de permissão do módulo.";
                    $acao       = "Apagar Configuração Atuais";
                    $class_btn  = 'danger';
                    $comentario = "Esse processo permite trocar a conta que receberá o pagamento, mas atenção, ele é IREVERSÌVEL. Para prosseguir clique no link:";
                    $link       = $this->getUrlClearMoip();
                } else {
                    $texto      = "Configurar o Retorno de Transação do Moip para o seu Magento";
                    $acao       = "Configurar retorno";
                    $class_btn  = '';
                    $comentario = "Esse processo permite receber a notificação de pedido pago ou cancelado. Para prosseguir clique no link:";
                    $link       = $this->getUrlEnableWebhooks();
                }
            } else {
                $texto      = "Criar permissão para o Moip realizar vendas em seu Magento";
                $acao       = "Autorizar Moip";
                $class_btn  = '';
                $comentario = "Esse processo permite passar a receber transações com sua conta Moip. Para prosseguir Clique no link:";
                $link       = $this->getLinkMoipApp();
            }
        } else {
            $texto      = "Configuração inicial do Módulo";
            $acao       = "Precisa de Ajuda?";
            $class_btn  = 'help';
            $comentario = "Escolha a sua chave de notificação";
            $link       = 'https://www.youtube.com/watch?v=5e5j407VLGI';
        }
        $action_setup  = "<h4>{$texto}</h4>";
        $action_setup .= "<p class='subheading-note' style='font-size:11px;font-style:italic;color:#666;'>{$comentario}</p>";
        $action_setup .= "<p class='p-actin-moip'><a href='{$link}' class='btn-moip {$class_btn}'>{$acao}</a></p>";
        return $action_setup;
    }
}
?>