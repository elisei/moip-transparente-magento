<?php
/**
 * Transparente - Transparente Payment Module
 *
 * @title      Magento -> Custom Payment Module for Transparente (Brazil)
 * @category   Payment Gateway
 * @package    MOIP_Transparente
 * @author     Transparente Pagamentos S/a
 * @copyright  Copyright (c) 2010 Transparente Pagamentos S/A
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @return     Criação dos estados brasileiros
 */
$installer = $this;

$installer->startSetup();

$directory_country_region = Mage::getSingleton('core/resource')->getTableName('directory_country_region');
$directory_country_region_name = Mage::getSingleton('core/resource')->getTableName('directory_country_region_name');
$collection = Mage::getModel('directory/region')->getResourceCollection()
        ->addCountryCodeFilter('BR')
        ->load();

if (count($collection) == 0) {

$installer->run("


INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AC', 'Acre');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Acre'), ('pt_BR', LAST_INSERT_ID(), 'Acre');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AL', 'Alagoas');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Alagoas'), ('pt_BR', LAST_INSERT_ID(), 'Alagoas');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AP', 'Amapá');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Amapá'), ('pt_BR', LAST_INSERT_ID(), 'Amapá');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'AM', 'Amazonas');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Amazonas'), ('pt_BR', LAST_INSERT_ID(), 'Amazonas');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'BA', 'Bahia');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Bahia'), ('pt_BR', LAST_INSERT_ID(), 'Bahia');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'CE', 'Ceará');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Ceará'), ('pt_BR', LAST_INSERT_ID(), 'Ceará');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'DF', 'Distrito Federal');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Distrito Federal'), ('pt_BR', LAST_INSERT_ID(), 'Distrito Federal');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'ES', 'Espírito Santo');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Espírito Santo'), ('pt_BR', LAST_INSERT_ID(), 'Espírito Santo');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'GO', 'Goiás');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Goiás'), ('pt_BR', LAST_INSERT_ID(), 'Goiás');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MA', 'Maranhão');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Maranhão'), ('pt_BR', LAST_INSERT_ID(), 'Maranhão');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MT', 'Mato Grosso');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Mato Grosso'), ('pt_BR', LAST_INSERT_ID(), 'Mato Grosso');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MS', 'Mato Grosso do Sul');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Mato Grosso do Sul'), ('pt_BR', LAST_INSERT_ID(), 'Mato Grosso do Sul');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'MG', 'Minas Gerais');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Minas Gerais'), ('pt_BR', LAST_INSERT_ID(), 'Minas Gerais');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PA', 'Pará');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Pará'), ('pt_BR', LAST_INSERT_ID(), 'Pará');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PB', 'Paraíba');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Paraíba'), ('pt_BR', LAST_INSERT_ID(), 'Paraíba');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PR', 'Paraná');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Paraná'), ('pt_BR', LAST_INSERT_ID(), 'Paraná');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PE', 'Pernambuco');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Pernambuco'), ('pt_BR', LAST_INSERT_ID(), 'Pernambuco');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'PI', 'Piauí');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Piauí'), ('pt_BR', LAST_INSERT_ID(), 'Piauí');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RJ', 'Rio de Janeiro');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio de Janeiro'), ('pt_BR', LAST_INSERT_ID(), 'Rio de Janeiro');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RN', 'Rio Grande do Norte');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio Grande do Norte'), ('pt_BR', LAST_INSERT_ID(), 'Rio Grande do Norte');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RS', 'Rio Grande do Sul');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rio Grande do Sul'), ('pt_BR', LAST_INSERT_ID(), 'Rio Grande do Sul');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RO', 'Rondônia');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Rondônia'), ('pt_BR', LAST_INSERT_ID(), 'Rondônia');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'RR', 'Roraima');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Roraima'), ('pt_BR', LAST_INSERT_ID(), 'Roraima');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SC', 'Santa Catarina');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Santa Catarina'), ('pt_BR', LAST_INSERT_ID(), 'Santa Catarina');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SP', 'São Paulo');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'São Paulo'), ('pt_BR', LAST_INSERT_ID(), 'São Paulo');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'SE', 'Sergipe');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Sergipe'), ('pt_BR', LAST_INSERT_ID(), 'Sergipe');

INSERT INTO `".$directory_country_region."` (`country_id`, `code`, `default_name`) VALUES
    ('BR', 'TO', 'Tocantins');
INSERT INTO `".$directory_country_region_name."` (`locale`, `region_id`, `name`) VALUES
    ('en_US', LAST_INSERT_ID(), 'Tocantins'), ('pt_BR', LAST_INSERT_ID(), 'Tocantins');

    ");
$installer->startSetup();
}



$installer->endSetup();
