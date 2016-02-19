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

class DerModPro_BCP_Block_Adminhtml_Catalog_Product_Edit_Tab_Super_Config_Grid
	extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config_Grid
{
	/**
	 * Remove the exclude filter for products with required cpo from the grid in
	 * the associated products tab.
	 *
	 * @return DerModPro_BCP_Block_Adminhtml_Catalog_Product_Edit_Tab_Super_Config_Grid
	 */
	protected function _preparePage()
	{
		parent::_preparePage();

		$select = $this->getCollection()->getSelect();
		$conditions = $select->getPart(Zend_Db_Select::WHERE);

		$select->reset(Zend_Db_Select::WHERE);
		foreach ($conditions as $condition) {
			/*
			 * Remove the exclude filter for products with required cpo
			 */
			if (strpos($condition, 'e.required_options ') !== false) continue;

			/*
			 * Re-add the remaining conditions
			 */
			if (preg_match('/^AND /', $condition))
			{
				$condition = substr($condition, 5);
				$condition = substr($condition, 0, -1);
				$select->where($condition);
			}
			elseif (preg_match('/^OR /', $condition))
			{
				$condition = substr($condition, 4);
				$condition = substr($condition, 0, -1);
				$select->orWhere($condition);
			}
			else
			{
				$select->where($condition);
			}
		}

		return $this;
	}
}