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

class DerModPro_BCP_Model_Entity_Attribute_Source_Associatedproduct
	extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions()
	{
		if (is_null($this->_options)) {
			$this->_options = array(
				array(
					'label' => Mage::helper('bcp')->__('None'),
					'value' => '',
				),
			);
			if ($product = $this->_getProduct())
			{
				$collection = $product->getTypeInstance(true)->getUsedProductCollection($product)
					->addAttributeToSelect('name');
				foreach ($collection as $childProduct)
				{
					$this->_options[] = array(
						'label' => $childProduct->getName() . ' (' . $childProduct->getSku() . ')',
						'value' => $childProduct->getSku(),
					);
				}
			}
			/*
			 * This probably is no good idea because the options are cached in the import
			 * profile, too, and because of that we miss the sku's of the freshly
			 * created products.
			 * Still, since magento entity id's aren't known before the import, export SKU's here.
			 * Figure out a way to make the import work once it's needed (probably overwrite the
			 * catalog convert model).
			 */
			elseif ($this->_isDataflow() && $this->_getDataflowDirection() == 'export')
			{
				$resource = $this->getAttribute()->getEntity();
				$select = $resource->getReadConnection()->select()
					->from($resource->getTable('catalog/product'), array('entity_id', 'sku'))
					->where('`type_id`=?', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
					->where('`entity_type_id`=?', $resource->getTypeId())
				;
				
				foreach ($resource->getReadConnection()->fetchAll($select) as $row)
				{
					$this->_options[] = array(
						'label' => $row['sku'],
						'value' => $row['entity_id'],
					);
				}
			}
		}
		return $this->_options;
	}

	/**
	 * Return true if called during a dataflow action
	 *
	 * @return bool
	 */
	protected function _isDataflow()
	{
		return Mage::app()->getRequest()->getControllerName() == 'system_convert_gui' && $this->_getConvertProfile();
	}

	/**
	 * Return the current dataflow profile model.
	 *
	 * @return Mage_Dataflow_Model_Profile
	 */
	protected function _getConvertProfile()
	{
		return Mage::registry('current_convert_profile');
	}

	/**
	 * Return the direction of the dataflow profile, i.e. export or import.
	 * If not called during a dataflow action return false.
	 *
	 * @return string|bool
	 */
	protected function _getDataflowDirection()
	{
		if ($this->_getConvertProfile())
		{
			return $this->_getConvertProfile()->getDirection();
		}
		return false;
	}


	/**
	 * Return the currently loaded product model
	 *
	 * @return Mage_Catalog_Model_Product or false if no current product is loaded or it's not configurable
	 */
	protected function _getProduct()
	{
		$product = Mage::registry('current_product');

		if (! $product || ! $product->getId())
		{
			return false;
		}
		
		if ($product->getTypeId() !== Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
		{
			return false;
		}
		return $product;
	}

	/**
	 * Return the input value if no current product is loaded.
	 * This is needed for dataflow compatibility.
	 *
	 * @param int $value
	 * @return string
	 */
	public function getOptionText($value)
	{
		$option = parent::getOptionText($value);

		/*
		 * Bugfix for Magento 1.3 - only return the option label, not the option array entry
		 */
		if (is_array($option) && isset($option['label']))
		{
			$option = $option['label'];
		}
		return $option;
	}

    /**
     * Retrieve Column(s) for Flat
     *
     * @return array
     */
    public function getFlatColums()
    {
        $columns = array();
        $columns[$this->getAttribute()->getAttributeCode()] = array(
            'type'      => 'varchar(255)',
            'unsigned'  => true,
            'is_null'   => true,
            'default'   => null,
            'extra'     => null
        );

        return $columns;
    }

	/**
	 * Retrieve Indexes(s) for Flat
	 *
	 * @return array
	 */
	public function getFlatIndexes()
	{
		$indexes = array();

		$index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
		$indexes[$index] = array(
			'type'      => 'index',
			'fields'    => array($this->getAttribute()->getAttributeCode())
		);
		
		return $indexes;
	}

	/**
	 * Retrieve Select For Flat Attribute update
	 *
	 * @param int $store
	 * @return Varien_Db_Select|null
	 */
	public function getFlatUpdateSelect($store)
	{
		return Mage::getResourceModel('eav/entity_attribute')
		->getFlatUpdateSelect($this->getAttribute(), $store);
	}
}