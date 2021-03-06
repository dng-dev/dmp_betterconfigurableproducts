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

$productTypes = Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ; //'configurable';

$this->addAttribute('catalog_product', 'bcp_default_product', array(
	'group'			=> 'Design',
	'label'			=> 'Default Simple Product',
	'type'			=> 'int',
	'input'			=> 'select',
	'source'		=> 'bcp/entity_attribute_source_associatedproduct',
	'global'		=> Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
	'required'		=> false,
	'default'		=> 0,
	'user_defined'	=> 0,
	'apply_to'		=> $productTypes,
	'used_in_product_listing' => 1,
	'is_configurable' => 0,
	'filterable_in_search' => 0,
	'used_for_price_rules' => 0,
));

$this->endSetup();