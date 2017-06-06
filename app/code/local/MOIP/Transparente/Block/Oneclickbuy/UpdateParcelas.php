<?php 
class MOIP_Transparente_Block_Oneclickbuy_UpdateParcelas extends Mage_Core_Block_Template
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
			} else {
				$parcelas[]= '<option value="1">Valor à vista</option>';
			}
		}
		return $parcelas;
	}
}