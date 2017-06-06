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
	
	public function getParcelas($price, $method) {
		
		if($price){
			$parcelamento = $this->getParcelamentoProduct($price);
			
			foreach ($parcelamento as $key => $value):
			  		if($key > 0){
			  			$juros = $value['juros'];
				        $parcelas_result = $value['parcela'];
				        $total_parcelado = $value['total_parcelado'];
				        if($juros > 0)
				            $asterisco = '';
				        else
				            $asterisco = ' sem juros';
				        $parcelas[]= '<strong>'.$key.'x</strong> '.$asterisco.' de <strong>'.$parcelas_result.'</strong>';
			  		}
			        
			endforeach;
			if($method == 'reduzido'){
				return end($parcelas);
			} elseif($method == 'integral') {
				return $parcelas;
			} else {
				return ;
			}

		} else {
			return ;
		}


	}
	

	public function getParcelamentoProduct($valor){
        $config_parcelas_juros = $this->getInfoParcelamentoJuros();
        $config_parcelas_minimo = $this->getInfoParcelamentoMinimo();
        $config_parcelas_maximo = Mage::getStoreConfig('payment/moip_cc/nummaxparcelamax');
        $json_parcelas = array();
        $count = 0;
        $json_parcelas[0] = array(
                                    'parcela' => Mage::helper('core')->currency($valor, true, false),
                                    'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                    'total_juros' =>  0,
                                    'juros' => 0
                                );
        $json_parcelas[1] = array(
                                    'parcela' => Mage::helper('core')->currency($valor, true, false),
                                    'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                    'total_juros' =>  0,
                                    'juros' => 0
                                );

        
        $max_div = (int)$valor/$config_parcelas_minimo;
        if($max_div > $config_parcelas_maximo) {
            $max_div = $config_parcelas_maximo;
        } elseif ($max_div > 12) {
            $max_div = 12;
        } 
        
        foreach ($config_parcelas_juros as $key => $value) {
            if($count <= $max_div){
                if($value > 0){
                    if(Mage::getStoreConfig('payment/moip_cc/tipodejuros') == 1) {
                        $parcela =  $this->getJurosComposto($valor, $value, $count);
                    } else {
                        $parcela =  $this->getJurosSimples($valor, $value, $count);
                    }
                    $total_parcelado = $parcela * $count;
                    $juros = $value;
                    if($parcela > 5 && $parcela > $config_parcelas_minimo){
                        $json_parcelas[$count] = array(
                            'parcela' => Mage::helper('core')->currency($parcela, true, false),
                            'total_parcelado' =>  Mage::helper('core')->currency($total_parcelado, true, false),
                            'total_juros' =>  $total_parcelado - $valor,
                            'juros' => $juros,
                        );
                    }
                } else {
                    if($valor > 0 && $count > 0){
                     $json_parcelas[$count] = array(
                                        'parcela' => Mage::helper('core')->currency(($valor/$count), true, false),
                                        'total_parcelado' =>  Mage::helper('core')->currency($valor, true, false),
                                        'total_juros' =>  0,
                                        'juros' => 0
                                    );
                    }
                }
            }
                
            
            
                

            $count++;
        }
    return $json_parcelas;
    }

    public function getJurosSimples($valor, $juros, $parcela)
    {
        $principal = $valor;
        $taxa = $juros/100;
        $valjuros = $principal * $taxa;
        $valParcela = ($principal + $valjuros)/$parcela;
        return $valParcela;
    }
    
    public function getJurosComposto($valor, $juros, $parcela)
    {
        $principal = $valor;
        $taxa = $juros/100;
        $valParcela = ($principal * $taxa) / (1 - (pow(1 / (1 + $taxa), $parcela)));
        return $valParcela;
    }

    public function getInfoParcelamentoJuros() {
        $juros = array();

        $juros['0'] = 0;

        $juros['1'] = 0;

        $juros['2'] =  Mage::getStoreConfig('payment/moip_cc/parcela2');

        
        $juros['3'] =  Mage::getStoreConfig('payment/moip_cc/parcela3');

        
        $juros['4'] =  Mage::getStoreConfig('payment/moip_cc/parcela4');

        
        $juros['5'] =  Mage::getStoreConfig('payment/moip_cc/parcela5');


        $juros['6'] =  Mage::getStoreConfig('payment/moip_cc/parcela6');


        $juros['7'] =  Mage::getStoreConfig('payment/moip_cc/parcela7');


        $juros['8'] =  Mage::getStoreConfig('payment/moip_cc/parcela8');


        $juros['9'] =  Mage::getStoreConfig('payment/moip_cc/parcela9');
       

        $juros['10'] =  Mage::getStoreConfig('payment/moip_cc/parcela10');
       

        $juros['11'] =  Mage::getStoreConfig('payment/moip_cc/parcela11');
       

        $juros['12'] =  Mage::getStoreConfig('payment/moip_cc/parcela12');
       
        return $juros;
    }

     public function getInfoParcelamentoMinimo() {
       
        
        $valor = Mage::getStoreConfig('payment/moip_cc/valor_minimo');
        
       
        return $valor;
    }
}
