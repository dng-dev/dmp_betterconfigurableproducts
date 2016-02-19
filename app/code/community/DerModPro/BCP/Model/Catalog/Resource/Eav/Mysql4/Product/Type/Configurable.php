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

class DerModPro_BCP_Model_Catalog_Resource_Eav_Mysql4_Product_Type_Configurable extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Type_Configurable
{
	/**
	 * Change original method to allow children with required options.
	 * This change is required since Magento 1.4.1
	 *
	 * Retrieve Required children ids
	 * Return grouped array, ex array(
	 *   group => array(ids)
	 * )
	 *
	 * @param int $parentId
	 * @param bool $required
	 * @return array
	 */
	public function getChildrenIds($parentId, $required = true)
	{
		$childrenIds = array();
		$select = $this->_getReadAdapter()->select()
			->from(array('l' => $this->getMainTable()), array('product_id', 'parent_id'))
			->join(
			array('e' => $this->getTable('catalog/product')),
			'e.entity_id=l.product_id', // Original Condition: // AND e.required_options=0',
			array()
			)
			->where('parent_id=?', $parentId);

		$childrenIds = array(0 => array());
		foreach ($this->_getReadAdapter()->fetchAll($select) as $row)
		{
			$childrenIds[0][$row['product_id']] = $row['product_id'];
		}

		return $childrenIds;
	}
}