<?php

class MOIP_Transparente_Block_Adminhtml_System_Config_Form_Fieldset_Modules_Reset extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

    /**
     * Return header html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
        $info_config = null;
    	$validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
    	
    	$code = Mage::getSingleton('adminhtml/config_data')->getStore();
    	

    
            
           
            $info_config .= '<hr><h3>Detalhes do token:</h3>';

    		$oauth_dev = Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            $publickey_dev= Mage::getSingleton('transparente/standard')->getConfigData('publickey_dev');
            
         
    		
            if(!is_null($oauth_dev)) {

               $info_config .= '<tr id="row_moipall_advanced_rest">
                                    <td class="label">
                                        <label for="row_moipall_advanced_rest"> Seu Token Oauth - Ambiente de Teste:</label>
                                    </td>
                                    <td class="value">
                                        <input readonly value="'.$oauth_dev.'" type="text" class="input-text" >
                                    </td>
                                </tr>';
               
              

	    	} else {

	    		
                $info_config .= "<p>O Ambiente de Teste não está configurado</p>";

	    	}


    	
    		
    		$oauth_prod = Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
            $publickey_prod = Mage::getSingleton('transparente/standard')->getConfigData('publickey_prod');

    		if(!is_null($oauth_prod)) {

    			
                $info_config .= '<tr id="row_moipall_advanced_rest">
                                    <td class="label">
                                        <label for="row_moipall_advanced_rest"> Seu Token Oauth - Ambiente de Produção:</label>
                                    </td>
                                    <td class="value">
                                        <input readonly value="'.$oauth_prod.'" type="text" class="input-text" >
                                    </td>
                                </tr>';
               

	    	} else {

	    		$info_config .= "<p>O Ambiente de Produção não está configurado</p>";

	    	}
    	   
    
        	$html = parent::_getHeaderHtml($element);
        	$html = $html.$info_config;
        return $html;
    }
}
?>