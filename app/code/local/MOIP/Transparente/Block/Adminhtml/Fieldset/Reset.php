<?php

class MOIP_Transparente_Block_Adminhtml_Fieldset_Reset
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {   
        $useContainerId = $element->getData('use_container_id');
        $validacao = Mage::getSingleton('transparente/standard')->getConfigData('validador_retorno');
        
        $code = Mage::getSingleton('adminhtml/config_data')->getStore();
        
        $typeConsult = $element->getOriginalData('type_consult');

        if($typeConsult === 'db'){
            $typeValueId = $element->getOriginalData('type_value_info');

            $value = Mage::getSingleton('transparente/standard')->getConfigData($typeValueId);
            
            if(isset($value)){
                return sprintf(
                    '<tr class="moip-info-head" id="row_%s">
                            <td colspan="5" >
                                    <p class="moip-info-value">
                                      <b>%s</b> -  %s
                                    </p>
                                    <p class="moip-info-note">
                                       %s 
                                       <hr>
                                    </p>
                            </td>
                    </tr>',
                        $element->getHtmlId(), 
                        $element->getLabel(),
                        $value,
                        $element->getComment()
                );
            } else {
                $textNotSet = $element->getOriginalData('text_not_set');
                return sprintf(
                    '<tr class="moip-info-head" id="row_%s">
                            <td colspan="5" >
                                    <h4 class="moip-info-title">%s</h4>
                                    <p class="moip-info-note">
                                        %s
                                        <hr>
                                    </p>
                            </td>
                    </tr>',
                        $element->getHtmlId(),
                        $element->getLabel(),
                        $textNotSet
                        
                );
            }
        } else {
            $path           = $element->getOriginalData('environment_path');
            $environment    = $element->getOriginalData('type_environment');


            $webhooks_set   = $this->getExternalConsult($path, $environment);
            

            $consult = json_decode($webhooks_set, true);

            
            
            if(!empty($consult) && !isset($consult['ERROR']) ){
                foreach ($consult as $key => $value) {
                    $_html  .= '<tr class="moip-info-head">
                                <td colspan="5" >
                                        <h4 class="moip-info-title">'.$element->getLabel() .'</h4>
                                       
                                        <p class="moip-info-note"> Url: '.$value['target'].'</p>
                                        <p class="moip-info-note"> Eventos de retorno: '.implode(" - ", $value['events']).'</p>
                                         <p class="moip-info-note"> <a href="' .$this->urlClearWebhooks($value['id'], $environment).'">Apagar configuração de retorno</a></p>
                                         <hr>
                                </td>
                            </tr>';
                   
                }
                return $_html;
            } else {
                $textNotSet    = $element->getOriginalData('text_not_set');
                $_html  = '<tr class="moip-info-head">
                                <td colspan="5" >
                                        <h4 class="moip-info-title">'.$element->getLabel().'</h4>
                                        <p>'.$textNotSet.'</p>
                                </td>
                            </tr>';
                return $_html;
            }
           

           
            
           
        }

        
    }

    public function getExternalConsult($path, $environment) {
      
        
        $documento = 'Content-Type: application/json; charset=utf-8';
        
        if ($environment == "teste") {
            $url = MOIP_Transparente_Model_Api::ENDPOINT_TEST.$path;
            $header = "Authorization: OAuth " . Mage::getSingleton('transparente/standard')->getConfigData('oauth_dev');
            
        } else {
            $url = MOIP_Transparente_Model_Api::ENDPOINT_PROD.$path;
            $header = "Authorization: OAuth " . Mage::getSingleton('transparente/standard')->getConfigData('oauth_prod');
        }

            $res = array();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array($header, $documento));
            curl_setopt($ch,CURLOPT_USERAGENT,'MoipMagento/2.0.0');
            $res = curl_exec($ch);
            curl_close($ch);
          /*  var_dump($header); die();*/
        return $res;
    
    }

    public function urlClearWebhooks($npr, $environment){
         return Mage::helper("adminhtml")->getUrl("adminhtml/adminhtml_oauthmoip/KillWebhooks", array('_secure' => true))."id/".$npr."/environment/".$environment;
    }
}
?>