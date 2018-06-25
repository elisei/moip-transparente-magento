<?php
class Moip_Transparente_Model_Observer
{

    public function getApi()
    {
        $api = Mage::getModel('transparente/api');
        return $api;
    }

    public function addWidgetJs(Varien_Event_Observer $observer){
        /*var_dump("olasrrrrr");die;*/
        $update = Mage::getSingleton('core/layout')->getUpdate()->addHandle('MOIP_TRANSPARENTE_WIDGET_JS');
        return $this;

    }
    
    public function changeAPP(Varien_Event_Observer $observer){
        $new = Mage::getStoreConfig('payment/moip_transparente_standard/type_app');
        $old = Mage::getStoreConfig('payment/moip_transparente_standard/type_old_app');
        if($new != $old){
            Mage::getSingleton('core/session')->addSuccess("Ao alterar o tipo de recebimento você deverá realizar nova autorização agora para sua nova taxa.");
            return Mage::helper('transparente')->ClearMoip();
        }
        
        return $this;
    }

    public function getPriceBundle($product) {
        
           $grouped_product_model = Mage::getModel('catalog/product_type_grouped');
        $groupedParentId = $grouped_product_model->getParentIdsByChild($product->getId());
        $_associatedProducts = $product->getTypeInstance(true)->getAssociatedProducts($product);

        foreach($_associatedProducts as $_associatedProduct) {
            if($ogPrice = $_associatedProduct->getPrice()) {
                $ogPrice = $_associatedProduct->getPrice();
            }
        }

        return $ogPrice;
    }

    public function addMassAction($observer)
    {
        $block = $observer->getEvent()->getBlock();
        if(get_class($block) =='Mage_Adminhtml_Block_Widget_Grid_Massaction'
            && $block->getRequest()->getControllerName() == 'sales_order')
        {
            $block->addItem('transparente', array(
                'label' => 'Consultar status no Moip',
                'url' =>  Mage::helper('adminhtml')->getUrl('adminhtml/adminhtml_statusmoip/setstate'),
            ));
        }
    }


    public function catalog_product_save_after_plans($observer){
        $product = $observer->getProduct();
        if($product->getIsRecurring()){
            $recurring = $product->getRecurringProfile();
          
            if($recurring['init_amount']){
                $setup = number_format($recurring['init_amount'], 2, '', '');    
            } else {
                $setup = $recurring['init_amount'];    
            }
            if($recurring['trial_period_frequency']){
              Mage::getSingleton('core/session')->addError('Moip - No momento não suportamos período de testes');
              return $this;
            } 
            if($recurring['start_date_is_editable']){
                 Mage::getSingleton('core/session')->addError('Moip - No momento não suportamos agendamento de dia de pagamento.');
              return $this;
            }

           
            if($product->getTypeId() != "bundle"){
                $data = array(
                            'code' => $product->getSku(),
                            'name' => $product->getName(),
                            'description' => $recurring['schedule_description'],
                          
                            'amount' => number_format($product->getFinalPrice(), 2, '', ''),
                            'setup_fee' => $setup,
                            'interval' => array(
                                                    'length' => $recurring['period_frequency'],
                                                    'unit' => $recurring['period_unit'],
                                                ),
                            'billing_cycles' => $recurring['period_max_cycles'],
                            
                            'status'    => 'ACTIVE',
                            'payment_method' => 'ALL',
                             );
            } else {
                $data = array(
                            'code' => $product->getSku(),
                            'name' => $product->getName(),
                            'description' => $recurring['schedule_description'],
                           
                            'amount' => "100",
                            'setup_fee' => $setup,
                            'interval' => array(
                                                    'length' => $recurring['period_frequency'],
                                                    'unit' => $recurring['period_unit'],
                                                ),
                            'billing_cycles' => $recurring['period_max_cycles'],
                            'status'    => 'ACTIVE',
                            'payment_method' => 'ALL',
                            

                             );
            }
            
            $api = Mage::getSingleton('transparente/recurringapi');
            $plans_data = $api->ConsultPlans($data);
            return $this;
        }
        
    }
    
}