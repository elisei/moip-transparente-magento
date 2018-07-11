<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MOIP_Transparente_Helper_Data extends Mage_Core_Helper_Abstract {
	
	const MINAMMOUT = 5;
    const MAXINSTALMENT = 12;

    public function getParcelas($price, $method){
        if($price) {
            $installment = $this->getCalcInstallment($price);
            foreach ($installment as $key => $_installment):      
                $_interest = $_installment['interest'];
                
                if($_interest > 0)
                    $text_interest = $this->__('*');
                else
                    $text_interest = $this->__(' sem juros');
                if($key >=2){
                    $installments[]= $this->__('em até <strong>%sx</strong> de %s%s',$key,$_installment['installment'],$text_interest);    
                } else {
                    $installments[]= $this->__('À vista no valor total <strong>%s</strong>',Mage::helper('core')->currency($price, true, false));
                }
            endforeach;
            if($method == 'reduzido'){
                $last_zero_interest = $this->getFilterNoInterestRate($installment);
                if(is_int($last_zero_interest)){
                    $last_text_zero_interest = end(array_keys($last_zero_interest));
                    return $installments[$last_text_zero_interest-1];
                } else {
                    return end($installments);
                }
                
                
            } elseif($method == 'integral') {
                return $installments;
            } else {
                return $this;
            }
            return end($installment);
        }
    }

    public function getCalcInstallment($ammount){
        $limit      = $this->getInstallmentLimit($ammount);
        $interest   = $this->getInfoInterest();
        $plotlist   = array();

        foreach ($interest as $key => $_interest) {
            if($key > 0 && $key <= $limit){
                if($_interest > 0){
                    
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        $plotValue =  $this->getJurosComposto($ammount, $_interest, $key);
                    } else {
                        $plotValue =  $this->getJurosSimples($ammount, $_interest, $key);
                    }
                    $total = $plotValue*$key;
                    $totalInterest  = ($plotValue*$key)-$ammount;
                } else {
                    $total = $ammount;
                    $totalInterest  = 0;
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        $plotValue =  $this->getJurosComposto($ammount, $_interest, $key);
                    } else {
                        $plotValue =  $this->getJurosSimples($ammount, $_interest, $key);
                    }
                }
                $plotlist[$key] = array(
                                        'installment' => Mage::helper('core')->currency($plotValue, true, false),
                                        'total_installment' =>  Mage::helper('core')->currency($total, true, false),
                                        'total_interest' =>  $totalInterest,
                                        'interest' => $_interest
                                    );
            }
        }
        return $plotlist;
    }
    public function getInterestByOrderTotal($ammount) {
       
        if( ($ammount >=  Mage::getStoreConfig('payment/moip_cc/condicional_1_sem_juros')) && ($ammount <  Mage::getStoreConfig('payment/moip_cc/condicional_2_sem_juros'))){
            $limit =  Mage::getStoreConfig('payment/moip_cc/condicional_1_max_parcela');
        } elseif(($ammount >=  Mage::getStoreConfig('payment/moip_cc/condicional_2_sem_juros')) && ($ammount <  Mage::getStoreConfig('payment/moip_cc/condicional_3_sem_juros'))) {
            $limit =  Mage::getStoreConfig('payment/moip_cc/condicional_2_max_parcela');
        } elseif($ammount >=  Mage::getStoreConfig('payment/moip_cc/condicional_3_sem_juros')) {
            $limit = Mage::getStoreConfig('payment/moip_cc/condicional_1_max_parcela');
        } else {
             $limit = !1;
        }
        return  $limit;
    }

    public function getComplexCalcInstallment($ammount){
        //parcelas_avancadas
       
        $limit          = $this->getInstallmentLimit($ammount);
        $interest       = $this->getInfoInterest();
        $interestOrder  = $this->getInterestByOrderTotal($ammount);
        $plotlist       = array();

        foreach ($interest as $key => $_interest) {
            if($key > 0 && $key <= $limit){
                if($interestOrder) {
                    if($interestOrder >= $key) {
                        $_interest = 0;
                    }
                }
                if($_interest > 0){
                    
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        $plotValue =  $this->getJurosComposto($ammount, $_interest, $key);
                    } else {
                        $plotValue =  $this->getJurosSimples($ammount, $_interest, $key);
                    }
                    $total = $plotValue*$key;
                    $totalInterest  = ($plotValue*$key)-$ammount;
                } else {
                    $total = $ammount;
                    $totalInterest  = 0;
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        $plotValue =  $this->getJurosComposto($ammount, $_interest, $key);
                    } else {
                        $plotValue =  $this->getJurosSimples($ammount, $_interest, $key);
                    }
                }
                $plotlist[$key] = array(
                    'installment' => Mage::helper('core')->currency($plotValue, true, false),
                    'total_installment' =>  Mage::helper('core')->currency($total, true, false),
                    'total_interest' =>  $totalInterest,
                    'interest' => $_interest
                );
            }
        }
        return $plotlist;
    }


    
    public function getFilterNoInterestRate($arr){
        $typeview = Mage::getStoreConfig('moipall/oneclick_config/type_min_installment');
        if($typeview == "notinterest") {
            $like = '0';
            $result = array_filter($arr, function ($item) use ($like) {
                if ($item['interest'] == $like) {
                    return true;
                }
                return false;
            });
            return $result; 
        } else {
            return $arr;
        }
    }

    public function getJurosComposto($valor, $juros, $parcela)
    {
        if($juros > 0){
            $principal = $valor;
            $taxa = $juros/100;
            $valParcela = ($principal * $taxa) / (1 - (pow(1 / (1 + $taxa), $parcela)));
            return $valParcela;
        } else {
            return $valor/$parcela;    
        }
    }

    public function getJurosSimples($valor, $juros, $parcela)
    {
        if($juros > 0) {
            $principal = $valor;
            $taxa = $juros/100;
            $valjuros = $principal * $taxa;
            $valParcela = ($principal + $valjuros)/$parcela;
            return $valParcela;    
        } else {
            return $valor/$parcela;
        }
    }

    public function getInfoInterest(){
        $interest = array();

        $interest['0'] = 0;

        $interest['1'] = 0;

        $interest['2'] =  Mage::getStoreConfig('payment/moip_cc/parcela2');

        
        $interest['3'] =  Mage::getStoreConfig('payment/moip_cc/parcela3');

        
        $interest['4'] =  Mage::getStoreConfig('payment/moip_cc/parcela4');

        
        $interest['5'] =  Mage::getStoreConfig('payment/moip_cc/parcela5');


        $interest['6'] =  Mage::getStoreConfig('payment/moip_cc/parcela6');


        $interest['7'] =  Mage::getStoreConfig('payment/moip_cc/parcela7');


        $interest['8'] =  Mage::getStoreConfig('payment/moip_cc/parcela8');


        $interest['9'] =  Mage::getStoreConfig('payment/moip_cc/parcela9');
       

        $interest['10'] =  Mage::getStoreConfig('payment/moip_cc/parcela10');
       

        $interest['11'] =  Mage::getStoreConfig('payment/moip_cc/parcela11');
       

        $interest['12'] =  Mage::getStoreConfig('payment/moip_cc/parcela12');
       
        return $interest;
    }


    public function getLimitByPortionNumber(){
        $maxconfig = Mage::getStoreConfig('payment/moip_cc/nummaxparcelamax');
        return ($maxconfig < self::MAXINSTALMENT) ? $maxconfig : self::MAXINSTALMENT;
    }

    public function getLimitByPlotPrice(){
        $minconfig = Mage::getStoreConfig('payment/moip_cc/valor_minimo');
        return ($minconfig > self::MINAMMOUT) ? $minconfig : self::MINAMMOUT;
    }


    public function getInstallmentLimit($ammount){      
        $perNumber     = $this->getLimitByPortionNumber();
        $perPrice      = $this->getLimitByPlotPrice();
       

        if($ammount >= $perPrice){
            $MaxPerPrice = intval($ammount/$perPrice);
        } else {
            $MaxPerPrice = 1;
        }

        if($MaxPerPrice >= $perNumber) {
            $limit = $perNumber;
        } else {
            $limit = $MaxPerPrice;
        }

        return $limit;
    }

    public function ClearMoip(){
        $ambiente = Mage::getSingleton('transparente/standard')->getConfigData('ambiente');
        
        $moipdb = Mage::getModel('transparente/transparente');
        $moipcollection = $moipdb->getCollection()->addFieldToFilter('moip_ambiente', array('eq' => $ambiente))->getItems();

        foreach ($moipcollection as $key => $value) {
            $value->setMoipCardId(null)->save();
        }

        $model = new Mage_Core_Model_Config();
    
    
        if (Mage::getSingleton('transparente/standard')->getConfigData('ambiente') == "teste") {
            $model->deleteConfig('payment/moip_transparente_standard/webhook_key_dev');
            $model->deleteConfig('payment/moip_transparente_standard/oauth_dev');

        } else {
            $model->deleteConfig('payment/moip_transparente_standard/webhook_key_prod');
            $model->deleteConfig('payment/moip_transparente_standard/oauth_prod');
            
        }
        Mage::app()->cleanCache();
        Mage::getSingleton('core/session')->addSuccess("Configurações atuais foram apagadas. Por favor, repita o processo de instalação.");
        $redirect_url = (Mage::helper('core/http')->getHttpReferer() ? Mage::helper('core/http')->getHttpReferer() : Mage::helper("adminhtml")->getUrl("*/system_config/edit/section/payment/"));
        Mage::app()->getFrontController()->getResponse()->setRedirect($redirect_url);
    }
}
