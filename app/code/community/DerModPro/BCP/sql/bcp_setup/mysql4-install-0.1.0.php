<?php
/**
 * Der Modulprogrammierer - Magento App Factory AG
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the
 * Der Modulprogrammierer - COMMERCIAL SOFTWARE LICENSE (v1.0) (DMCSL 1.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 *
 * @category   DerModPro
 * @package    DerModPro_BCP
 * @copyright  Copyright (c) 2012 Der Modulprogrammierer - Magento App Factory AG
 * @license    Der Modulprogrammierer - COMMERCIAL SOFTWARE LICENSE (v1.0) (DMCSL 1.0)
 */

$this->startSetup();

$entityTypeId     = $this->getEntityTypeId('catalog_product');
$productTypes = 'configurable';

$this->addAttribute('catalog_product', 'bcp_update_images', array(
	'group'			=> 'Design',
	'label'			=> 'Update view to show images of selected simple product',
	'type'			=> 'int',
	'input'			=> 'select',
	'source'		=> 'bcp/entity_attribute_source_yesnodefault',
	'global'		=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'required'		=> false,
	'default'		=> DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT,
	'user_defined'	=> 0,
	'apply_to'		=> $productTypes,
	'used_in_product_listing' => 0,
	'is_configurable' => 0,
	'filterable_in_search' => 0,
	'used_for_price_rules' => 0,
));

$this->addAttribute('catalog_product', 'bcp_update_short_desc', array(
	'group'			=> 'Design',
	'label'			=> 'Update view to show short description of selected simple product',
	'type'			=> 'int',
	'input'			=> 'select',
	'source'		=> 'bcp/entity_attribute_source_yesnodefault',
	'global'		=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'required'		=> false,
	'default'		=> DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT,
	'user_defined'	=> 0,
	'apply_to'		=> $productTypes,
	'used_in_product_listing' => 0,
	'is_configurable' => 0,
	'filterable_in_search' => 0,
	'used_for_price_rules' => 0,
));

$this->addAttribute('catalog_product', 'bcp_update_collateral', array(
	'group'			=> 'Design',
	'label'			=> 'Update view to show collateral section of selected simple product',
	'type'			=> 'int',
	'input'			=> 'select',
	'source'		=> 'bcp/entity_attribute_source_yesnodefault',
	'global'		=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'required'		=> false,
	'default'		=> DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT,
	'user_defined'	=> 0,
	'apply_to'		=> $productTypes,
	'used_in_product_listing' => 0,
	'is_configurable' => 0,
	'filterable_in_search' => 0,
	'used_for_price_rules' => 0,
));


$this->endSetup();
