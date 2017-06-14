<?php 
class MOIP_Transparente_Model_Source_TypeState
{
	
	 public function toOptionArray() {

        return array(
			array('value' => 'pending_payment', 'label' => 'Usar - Pagamento Pendente'),
        	array('value' => 'onhold', 'label' => 'Usar - Pedido Segurado'),
        	array('value' => 'not', 'label' => 'Não aplicar mudança de status intermediário'),
        );

    }
}