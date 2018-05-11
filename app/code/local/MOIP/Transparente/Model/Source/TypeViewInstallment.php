<?php

class MOIP_Transparente_Model_Source_TypeViewInstallment {

    public function toOptionArray() {

        return array(
            array('value' => 'full', 'label' => 'Exibir o máximo de parcelas'),
            array('value' => 'notinterest', 'label' => 'Exibir o menor valor sem juros'),
        );
    }

}
