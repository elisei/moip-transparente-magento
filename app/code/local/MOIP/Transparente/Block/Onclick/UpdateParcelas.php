<?php 
class MOIP_Transparente_Block_Onclick_UpdateParcelas extends Mage_Core_Block_Template
{
	public function getParcelamentoSelect() {
		
		$api = Mage::getSingleton('transparente/api');
		$parcelamento = $api->getParcelamento();

			
			foreach ($parcelamento as $key => $value) {

						if($key > 0){
							$juros = $value['juros'];
							$parcelas_result = $value['parcela'];
							$total_parcelado = $value['total_parcelado'];
							
							if($juros > 0)
								$asterisco = '*';
							else
								$asterisco = '';
							
								$parcelas[]= '<option value="'.$key.'">'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.$asterisco.'</option>';
								#$parcelas[]= '<li><input type="radio" name="payment[credito_parcelamento]" title="Selecione as Parcelas" id="credito_parcelamento" class="input-radio  validate-one-required-by-name" value="'.$key.'"><label>'.$key.'x de '.$parcelas_result.' no total de '.$total_parcelado.' '.$asterisco.'</label></li>';
							
						}
			}
			

		return $parcelas;

	}
	
}