<?php

class MOIP_Transparente_Model_Source_TypeViewInstallment
{
    public function toOptionArray()
    {
        return [
            ['value' => 'full', 'label' => 'Exibir o máximo de parcelas'],
            ['value' => 'notinterest', 'label' => 'Exibir o menor valor sem juros'],
        ];
    }
}
