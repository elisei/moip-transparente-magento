<?php


$installer = $this;
$installer->startSetup();

if ($this->getAttribute('customer', 'tipopessoa', 'attribute_id')) {
    $this->removeAttribute('customer', 'tipopessoa');
    $this->removeAttribute('customer_address', 'tipopessoa');
}

//usando padrão do OSC de https://github.com/deivisonarthur/OSC-Magento-Brasil-6-Pro

if (!$this->getAttribute('customer', 'tipopessoa', 'attribute_id')) {
    $installer->addAttribute('customer', 'tipopessoa', array(
        'type' => 'int',
        'input' => 'select', // o ideal seria usar o input como radio mas manteremos o padrão já existente.
        'label' => 'Tipo de Pessoa',
        'global' => 1,
        'visible' => 1,
        'required' => 0,
        'user_defined' => 1,
        'sort_order' => 95,
        'visible_on_front' => 1,
        'source' => 'eav/entity_attribute_source_table',
        'option' => array('values' => array('Física', 'Jurídica')),
    ));
    if (version_compare(Mage::getVersion(), '1.6.0', '<=')) {
        $customer = Mage::getModel('customer/customer');
        $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
        $installer->addAttributeToSet('customer', $attrSetId, 'General', 'tipopessoa');
    }
    if (version_compare(Mage::getVersion(), '1.4.2', '>=')) {
        Mage::getSingleton('eav/config')
                ->getAttribute('customer', 'tipopessoa')
                ->setData('used_in_forms', array('adminhtml_customer', 'customer_account_create', 'customer_account_edit', 'checkout_register'))
                ->save();
    }
}
if (!$this->getAttribute('customer', 'cnpj', 'attribute_id')) {
    $installer->addAttribute('customer', 'cnpj', array(
        'input' => 'text',
        'type' => 'varchar',
        'label' => 'CNPJ',
        'visible' => 1,
        'required' => 0,
        'user_defined' => 1,
    ));
    if (version_compare(Mage::getVersion(), '1.6.0', '<=')) {
        $customer = Mage::getModel('customer/customer');
        $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
        $installer->addAttributeToSet('customer', $attrSetId, 'General', 'cnpj');
    }
    if (version_compare(Mage::getVersion(), '1.4.2', '>=')) {
        Mage::getSingleton('eav/config')
                ->getAttribute('customer', 'cnpj')
                ->setData('used_in_forms', array('adminhtml_customer', 'customer_account_create', 'customer_account_edit', 'checkout_register'))
                ->save();
    }
}
$installer->getConnection()
    ->addColumn($installer->getTable('sales/order'), 'customer_tipopessoa', 'smallint(1) default null');
$installer->getConnection()
    ->addColumn($installer->getTable('sales/quote'), 'customer_tipopessoa', 'smallint(1) default null');

$installer->endSetup();