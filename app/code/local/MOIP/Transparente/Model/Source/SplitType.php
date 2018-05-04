<?php

class MOIP_Transparente_Model_Source_SplitType {

    public function toOptionArray() {

        return array(
            array('value' => 'attributeproduct', 'label' => 'Por Atributo de Produto'),
            array('value' => 'perstoreview', 'label' => 'Fixar em padrão da store'),
            array('value' => 'fullstoreview', 'label' => 'Pagamento Total realizado via Store'),
            array('value' => 'custom', 'label' => 'Customizado (ideial para quem usa plugins de mktplace)'),
        );
    }

}
