<?php

class MOIP_Transparente_Model_Source_TypeApp
{
    public function toOptionArray()
    {
        return [
            ['value' => 'fluxo', 'label' => 'Definido via conta Moip'],
            ['value' => 'd14', 'label' => 'Recebimento em 14 dias'],
            ['value' => 'd30', 'label' => 'Recebimento em 30 dias']
        ];
    }
}
