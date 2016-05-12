<?php

class MOIP_Transparente_Block_Adminhtml_System_Config_Form_Fieldset_Modules_Oauth extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

    /**
     * Return header html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
    	$validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
    	
    	$code = Mage::getSingleton('adminhtml/config_data')->getStore();
    	

    	if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore()))
    	{
    	    $store_id = Mage::getModel('core/store')->load($code)->getId();
    	} elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
    	    $website_id = Mage::getModel('core/website')->load($code)->getId();
    	    $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
    	} else 	{
    	    $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
    	}
        if($code == ""){
            $code = "default";
        }
    	
        $redirectUri = Mage::getUrl('Transparente/standard/Oauth/'.'validacao/'.$validacao.'/store_scope/'.$code.'/store_id/'.$store_id); // Esse é um controle para get da autorização...
        $redirectUri = urlencode($redirectUri);
        $redirectUri = "http://moip.o2ti.com/magento/redirect/?client_id=".$redirectUri; //Aqui voce pode construir sua url URI no entanto precisa estar exatamente como indicado no app construido...
        $responseType = "CODE";
        $scope = "CREATE_ORDERS|VIEW_ORDERS|CREATE_PAYMENTS|VIEW_PAYMENTS";
        $webhooks_url = Mage::getUrl('Transparente/standard/EnableWebhooks/validacao/'.$validacao.'/');
    	if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            
            $app_id_moip_dev = "APP-9MUFQ39Y4CQU"; //Alterar aqui caso necessário.

    		$endpoint_moip = "https://sandbox.moip.com.br/oauth/authorize";
    		$set_url_btn = $endpoint_moip.'?responseType='.$responseType.'&appId='.$app_id_moip_dev.'&redirectUri='.$redirectUri.'&scope='.$scope;
    		$oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $webhooks_return = Mage::getSingleton('transparente/standard')->getConfigData('webhook_key_dev');

    		if(!is_null($oauth) || isset($oauth)) {


    			$text_logar_se .= "<h2>Sua loja está autorizada a realizar vendas no ambiente:</h2> <p> Teste - Não processa compras reais.</p><hr/>";
                if(!$webhooks_return || is_null($webhooks_return)){
                   $text_logar_se .= "<h2>Para configurar o retorno da transação acesse:</h2> <p> <a href='{$webhooks_url}''>Clique aqui</a> para configurar o retorno de status da transação </p>";    
                } else {
                    $text_logar_se .= "Registro do MOIP: ".$webhooks_return;
                }
                

	    	
            } else {
                   
	    		$set_url_btn = $endpoint_moip.'?responseType='.$responseType.'&appId='.$app_id_moip_dev.'&redirectUri='.$redirectUri.'&scope='.$scope;
                $criar_conta = 'Para criar uma conta de teste acesse <a href="https://labs.moip.com.br/login/">clique aqui</a>';
	    		$btn = '<p><a href="'.$set_url_btn.'"><img src="' . $this->getSkinUrl('MOIP/transparente/imagem/btn-login-moip.png') . '" alt="Botão Login Moip" /></a></p><p>'.$criar_conta.'</p>';
	    		$text_logar_se .= "<p>2º  - Passo - Para Realizar Transações no ambiente de Sandbox (ambiente para testes), por favor autorize o aplicativo:</p>".$btn;
    			

	    	}


    	} else {

            $app_id_moip_prod = "APP-AKYBMMVU1FL1";
    		$endpoint_moip = "https://api.moip.com.br/oauth/authorize";
    		$oauth = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $webhooks_return = Mage::getSingleton('transparente/standard')->getConfigData('webhook_key_prod');

    		if(!is_null($oauth) || isset($oauth)) {
                
    			$text_logar_se = "<h2>Sua loja está autorizada a para realizar vendas no ambiente:</h2> <p>Produção</p><hr/>";
                 if(!$webhooks_return || is_null($webhooks_return)){
                    $text_logar_se .= "<h2>Para configurar o retorno da transação acesse:</h2> <p> <a href='{$webhooks_url}''>Clique aqui</a> para configurar o retorno de status da transação </p>";    
                } else {
                    $text_logar_se .= "Registro do MOIP: ".$webhooks_return;
                }

	    	} else {

	    		$set_url_btn = $endpoint_moip.'?responseType='.$responseType.'&appId='.$app_id_moip_prod.'&redirectUri='.$redirectUri.'&scope='.$scope;
                $criar_conta = 'Para criar uma conta no Moip acesse <a href="https://www.moip.com.br/login/">clique aqui</a>';
	    		$btn = '<p><a href="'.$set_url_btn.'"><img src="' . $this->getSkinUrl('MOIP/transparente/imagem/btn-login-moip.png') . '" alt="Botão Login Moip" /></a></p><p>'.$criar_conta.'</p>';
	    		$text_logar_se = "<h2>Para Realizar Transações no ambiente de Desenvolvimento (ambiente para produção, compras reais), por favor autorize o aplicativo:</h2>".$btn;
    			

	    	}
    	}


    	if($validacao){
    		$info_config = $text_logar_se;
    	}else {
    		$info_config = '<h2>Vamos Configurar a sua conta?</h2><p>1º  - Passo - Insira no campo a baixo  "Validação de comunicação" para continuar a sua instalação e clique em Salvar Configuração.</p>';
    	}
        	$html = parent::_getHeaderHtml($element);
        	$html = $html.$info_config;
        return $html;
    }
}
?>