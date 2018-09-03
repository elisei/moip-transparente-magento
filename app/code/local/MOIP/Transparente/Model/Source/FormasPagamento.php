<?php

class MOIP_Transparente_Model_Source_FormasPagamento
{
    public function toOptionArray()
    {
        return [
            ['value' => 'BoletoBancario', 'label' => 'Boleto Bancário'],
            ['value' => 'DebitoBancario', 'label' => 'Débito Online'],
            ['value' => 'CartaoCredito', 'label' => 'Cartão de Crédito'],
        ];
    }
}
