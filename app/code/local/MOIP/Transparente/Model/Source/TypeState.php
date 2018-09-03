<?php 
class MOIP_Transparente_Model_Source_TypeState
{
    public function toOptionArray()
    {
        return [
            ['value' => 'pending_payment', 'label' => 'Usar - Pagamento Pendente - Atenção esse tipo de status não é visível ao consumidor até o estágio final do pedido (cancelado ou autorizado)'],
            ['value' => 'onhold', 'label' => 'Usar - Pedido Segurado'],
            ['value' => 'not', 'label' => 'Não aplicar mudança de status intermediário'],
        ];
    }
}
