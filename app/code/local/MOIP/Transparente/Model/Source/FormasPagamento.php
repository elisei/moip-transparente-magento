<?php

class MOIP_Transparente_Model_Source_FormasPagamento {

    public function toOptionArray() {

        return array(
            array('value' => 'BoletoBancario', 'label' => 'Boleto Bancário'),
            array('value' => 'DebitoBancario', 'label' => 'Débito Online'),
            array('value' => 'CartaoCredito', 'label' => 'Cartão de Crédito'),
        );
    }

}
