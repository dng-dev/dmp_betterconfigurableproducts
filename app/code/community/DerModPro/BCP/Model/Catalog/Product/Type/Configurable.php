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

class DerModPro_BCP_Model_Catalog_Product_Type_Configurable extends Mage_Catalog_Model_Product_Type_Configurable
{
	/**
	 * This public method was used until Magento 1.5.0.1
	 *
	 * @param Varien_Object $buyRequest
	 * @param Mage_Catalog_Model_Product $product
	 * @return mixed
	 */
	public function prepareForCart(Varien_Object $buyRequest, $product = null)
	{
		$this->_bcpCopyCustomOptionsToChildren($buyRequest, $product);
		return parent::prepareForCart($buyRequest, $product);
	}

	/**
	 * This protected method is used from Magento 1.5.1.0
	 *
	 * @param Varien_Object $buyRequest
	 * @param Mage_Catalog_Model_Product $product
	 * @param string $processMode
	 * @return mixed
	 */
	protected function _prepareProduct(Varien_Object $buyRequest, $product, $processMode)
	{
		$this->_bcpCopyCustomOptionsToChildren($buyRequest, $product);
		return parent::_prepareProduct($buyRequest, $product, $processMode);
	}

	/**
	 * Add the cpo of the selected simple product to the product before initializing
	 * the product(s) for the add to cart process.
	 *
	 * @param   Varien_Object $buyRequest
	 * @param   Mage_Catalog_Model_Product $product
	 * @return  mixed
	 */
	protected function _bcpCopyCustomOptionsToChildren(Varien_Object $buyRequest, $product = null)
	{
		if ($attributes = $buyRequest->getSuperAttribute())
		{
			$product = $this->getProduct($product);
			if ($subProduct = $this->getProductByAttributes($attributes, $product))
			{
				if ($subProduct->getHasOptions())
				{
					foreach ($subProduct->getProductOptionsCollection() as $option)
					{
						$option->setProduct($product);
						$product->addOption($option);
					}
				}
			}
		}
		return $this;
	}

	/**
	 * Retrieve array of "subproducts"
	 * This is a clone of the parents method, except we don't need to filter
	 * out simple products with required cpo.
	 * With the filter in place simple product options with required cpo are not
	 * included in the dropdown(s) - we don't wont that behaviour with BCP.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */
	public function getUsedProducts($requiredAttributeIds = null, $product = null)
	{
		Varien_Profiler::start('CONFIGURABLE:'.__METHOD__);
		if (!$this->getProduct($product)->hasData($this->_usedProducts))
		{
			if (is_null($requiredAttributeIds)
				and is_null($this->getProduct($product)->getData($this->_configurableAttributes)))
			{
				// If used products load before attributes, we will load attributes.
				$this->getConfigurableAttributes($product);
				// After attributes loading products loaded too.
				Varien_Profiler::stop('CONFIGURABLE:'.__METHOD__);
				return $this->getProduct($product)->getData($this->_usedProducts);
			}

			$usedProducts = array();
			$collection = $this->getUsedProductCollection($product)
				->addAttributeToSelect('*')
				/*
				 * This is the only modification.
				 */

				;//->addFilterByRequiredOptions();
				
				/*
				 * End of the modification
				 */
			if (is_array($requiredAttributeIds))
			{
				foreach ($requiredAttributeIds as $attributeId)
				{
					$attribute = $this->getAttributeById($attributeId, $product);
					if (!is_null($attribute))
						$collection->addAttributeToFilter($attribute->getAttributeCode(), array('notnull'=>1));
				}
			}
			foreach ($collection as $item)
			{
				$usedProducts[] = $item;
			}

			$this->getProduct($product)->setData($this->_usedProducts, $usedProducts);
		}
		Varien_Profiler::stop('CONFIGURABLE:'.__METHOD__);
		return $this->getProduct($product)->getData($this->_usedProducts);
	}

}