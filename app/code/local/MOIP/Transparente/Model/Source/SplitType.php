<?php

class MOIP_Transparente_Model_Source_SplitType
{
    public function toOptionArray()
    {
        return [
            ['value' => 'attributeproduct', 'label' => 'Por Atributo de Produto'],
            ['value' => 'perstoreview', 'label' => 'Fixar em padrão da store'],
            ['value' => 'fullstoreview', 'label' => 'Pagamento Total realizado via Store'],
            ['value' => 'custom', 'label' => 'Customizado (ideial para quem usa plugins de mktplace)'],
        ];
    }
}
