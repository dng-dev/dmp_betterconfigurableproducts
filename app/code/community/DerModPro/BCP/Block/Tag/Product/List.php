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

class DerModPro_BCP_Block_Tag_Product_List extends Mage_Tag_Block_Product_List
{
	/**
	 * Set the module translaton namespace
	 */
	public function _construct()
	{
		$this->setData('module_name', 'Mage_Tag');
		parent::_construct();
	}

	/**
	 * Return the Id of the BCP parent configurable product if available
	 * instead of the simple products Id
	 *
	 * @return int | false
	 */
	public function getProductId()
	{
		$product = Mage::registry('current_product');
		if ($product && $product->getBcpParentProduct())
		{
			return $product->getBcpParentProduct()->getId();
		}
		return parent::getProductId();
	}
}