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

class DerModPro_BCP_Model_Catalog_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
	extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
{
	/**
	 * Reindex temporary (price result data) for all products
	 *
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
	 */
	public function reindexAll()
	{
		if (version_compare(Mage::getVersion(), '1.4.1', '<') && strpos(Mage::getVersion(), '-devel') === 'false')
		{
			$this->_prepareFinalPriceData();
			$this->_oldPrepareBCPConfigurablePriceData();
		}
		else
		{
			$this->useIdxTable(true);
			$this->_prepareFinalPriceData();
			$this->_applyBCPConfigurablePriceData();
			$this->_applyCustomOption();
			$this->_movePriceDataToIndexTable();
		}
		return $this;
	}

	/**
	 * Reindex temporary (price result data) for defined product(s)
	 *
	 * @param int|array $entityIds
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
	 */
	public function reindexEntity($entityIds)
	{
		if (version_compare(Mage::getVersion(), '1.4.1', '<') && strpos(Mage::getVersion(), '-devel') === 'false')
		{
			$this->_prepareFinalPriceData($entityIds);
			$this->_oldPrepareBCPConfigurablePriceData($entityIds);
		}
		else
		{
			$this->_prepareFinalPriceData($entityIds);
			$this->_applyBCPConfigurablePriceData($entityIds);
			$this->_applyCustomOption();
			$this->_movePriceDataToIndexTable();
		}
		return $this;
	}

	/**
	 * Use the prices of the simple products instead of the configurable product options.
	 * This is very similar to the way grouped products work.
	 * The simple product prices have been adjusted with the custom product options of
	 * the configurable product.
	 *
	 * @param array $entityIds
	 * @return DerModPro_BCP_Model_Catalog_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
	 * @see Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Grouped::_prepareGroupedProductPriceData()
	 */
	protected function _applyBCPConfigurablePriceData($entityIds = null)
	{
		$write = $this->_getWriteAdapter();
		$table = $this->getIdxTable();

		$select = $write->select()
			->from(array('e' => $this->getTable('catalog/product')), 'entity_id')
			->join(
				array('l' => $this->getTable('catalog/product_super_link')),
				'l.parent_id = e.entity_id',
				array()) //array('parent_id', 'product_id'))
			->join(
				array('cg' => $this->getTable('customer/customer_group')),
				'',
				array('customer_group_id'));
		$this->_addWebsiteJoinToSelect($select, true);
		$this->_addProductWebsiteJoinToSelect($select, 'cw.website_id', 'e.entity_id');
		$defaultProduct = $this->_addAttributeToSelect($select, 'bcp_default_product_sku', 'e.entity_id', 'cs.store_id');
		$select->columns('website_id', 'cw')
			->joinLeft(
				array('le' => $this->getTable('catalog/product')),
				'le.entity_id = l.product_id',
				array())
			/* Handle the case when no default product is configured (join with the default price index table to get the conf. products price) */
			->joinLeft(
				array('dic' => $this->_getDefaultFinalPriceTable()), // dic = default index configurable
				"`dic`.`entity_id` = `e`.`entity_id` AND `dic`.`website_id` = `cw`.`website_id` "
					. " AND `dic`.`customer_group_id` = `cg`.`customer_group_id`",
				array())
			/* Handle the case when a default product is configured (join with the price index table to get the simple products price) */
            ->joinLeft(
                array('dis' => $table), // dis = default index simple
                "dis.entity_id = ("
                     . " SELECT e2.entity_id"
                     . " FROM {$this->getTable('catalog/product')} e2"
                     . " WHERE e2.sku = {$defaultProduct})"
                 . " AND dis.website_id = cw.website_id "
                 . " AND dis.customer_group_id = cg.customer_group_id",
                array())
			->joinLeft(
				array('i' => $table),
				'i.entity_id = l.product_id AND i.website_id = cw.website_id'
					. ' AND i.customer_group_id = cg.customer_group_id',
				array(
					'tax_class_id'=> new Zend_Db_Expr('IFNULL(dic.tax_class_id, dis.tax_class_id)'),
					'orig_price'       => new Zend_Db_Expr('IFNULL(dis.price, dic.orig_price)'),
					'price' => new Zend_Db_Expr('IFNULL(dis.final_price, dic.price)'),
					//'min_price'   => new Zend_Db_Expr('MIN(IF(le.required_options = 0, i.min_price, 0))'),
					'min_price'   => new Zend_Db_Expr('MIN(i.min_price)'),
					//'max_price'   => new Zend_Db_Expr('MAX(IF(le.required_options = 0, i.max_price, 0))'),
					'max_price'   => new Zend_Db_Expr('MAX(i.max_price)'),
					'tier_price'  => new Zend_Db_Expr('IFNULL(dis.tier_price, dic.tier_price)'),
					'base_tier'  => new Zend_Db_Expr('IFNULL(dis.tier_price, dic.base_tier)')
				))
			->group(array('e.entity_id', 'cg.customer_group_id', 'cw.website_id'))
			->where('e.type_id=?', $this->getTypeId());

		if (!is_null($entityIds)) {
			$select->where('e.entity_id IN(?)', $entityIds);
		}

		/**
		 * Add additional external limitation
		 */
		Mage::dispatchEvent('catalog_product_prepare_index_select', array(
			'select'        => $select,
			'entity_field'  => new Zend_Db_Expr('e.entity_id'),
			'website_field' => new Zend_Db_Expr('cw.website_id'),
			'store_field'   => new Zend_Db_Expr('cs.store_id')
		));

		//Mage::log((string) $select->assemble());
		//Mage::log($write->fetchAll($select));
		
		$query = $select->insertFromSelect($this->_getDefaultFinalPriceTable(), array(
			'entity_id',
			'customer_group_id',
			'website_id',
			'tax_class_id',
			'orig_price',
			'price',
			'min_price',
			'max_price',
			'tier_price',
			'base_tier'
		), true); // insert on duplicate key update
		$write->query($query);

		return $this;
	}

	/**
	 * Old Version of the _applyBCPConfigurablePriceData() Method for Magento 1.4.0.x
	 *
	 * @param array $entityIds
	 * @return DerModPro_BCP_Model_Catalog_Resource_Eav_Mysql4_Product_Indexer_Price_Configurable
	 */
	protected function _oldPrepareBCPConfigurablePriceData($entityIds = null)
	{
		$write = $this->_getWriteAdapter();
		$table = $this->getIdxTable();

		$select = $write->select()
			->from(array('e' => $this->getTable('catalog/product')), 'entity_id')
			->join(
				array('l' => $this->getTable('catalog/product_super_link')),
				'l.parent_id = e.entity_id',
				array()) //array('parent_id', 'product_id'))
			->join(
				array('cg' => $this->getTable('customer/customer_group')),
				'',
				array('customer_group_id'));
		$this->_addWebsiteJoinToSelect($select, true);
		$this->_addProductWebsiteJoinToSelect($select, 'cw.website_id', 'e.entity_id');
		$defaultProduct = $this->_addAttributeToSelect($select, 'bcp_default_product_sku', 'e.entity_id', 'cs.store_id');
		$select->columns('website_id', 'cw')
			->joinLeft(
				array('le' => $this->getTable('catalog/product')),
				'le.entity_id = l.product_id',
				array())
			/* Handle the case when no default product is configured (join with the default price index table to get the conf. products price) */
			->joinLeft(
				array('dic' => $this->_getDefaultFinalPriceTable()),
				"dic.entity_id = `e`.`entity_id`",
				array())
			/* Handle the case when a default product is configured (join with the price index table to get the simple products price) */
            ->joinLeft(
                array('dis' => $table), // dis = default index simple
                "dis.entity_id = ("
                     . " SELECT e2.entity_id"
                     . " FROM {$this->getTable('catalog/product')} e2"
                     . " WHERE e2.sku = {$defaultProduct})",
                array())
			->joinLeft(
				array('i' => $table),
				'i.entity_id = l.product_id AND i.website_id = cw.website_id'
					. ' AND i.customer_group_id = cg.customer_group_id',
				array(
					'tax_class_id'=> new Zend_Db_Expr('IFNULL(i.tax_class_id, 0)'),
					'price'       => new Zend_Db_Expr('MAX(IFNULL(dis.price, dic.orig_price))'),
					'final_price' => new Zend_Db_Expr('MIN(IFNULL(dis.final_price, dic.price))'),
					//'min_price'   => new Zend_Db_Expr('MIN(IF(le.required_options = 0, i.min_price, 0))'),
					'min_price'   => new Zend_Db_Expr('MIN(i.min_price)'),
					//'max_price'   => new Zend_Db_Expr('MAX(IF(le.required_options = 0, i.max_price, 0))'),
					'max_price'   => new Zend_Db_Expr('MAX(i.max_price)'),
					'tier_price'  => new Zend_Db_Expr('NULL')
				))
			->group(array('e.entity_id', 'cg.customer_group_id', 'cw.website_id'))
			->where('e.type_id=?', $this->getTypeId());

		if (!is_null($entityIds)) {
			$select->where('e.entity_id IN(?)', $entityIds);
		}

		/**
		 * Add additional external limitation
		 */
		Mage::dispatchEvent('catalog_product_prepare_index_select', array(
			'select'        => $select,
			'entity_field'  => new Zend_Db_Expr('e.entity_id'),
			'website_field' => new Zend_Db_Expr('cw.website_id'),
			'store_field'   => new Zend_Db_Expr('cs.store_id')
		));

		//Mage::log((string) $select->assemble());
		$query = $select->insertFromSelect($table);
		$write->query($query);

		return $this;
	}
}