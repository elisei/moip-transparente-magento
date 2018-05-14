<?php

class MOIP_Transparente_Model_Source_TypeApp {

    public function toOptionArray() {

        return array(
            array('value' => 'fluxo', 'label' => 'Definido via conta Moip'),
            array('value' => 'd14', 'label' => 'Recebimento em 14 dias'),
            array('value' => 'd30', 'label' => 'Recebimento em 30 dias')
        );
    }

}
