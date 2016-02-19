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

class DerModPro_BCP_Model_Sales_Mysql4_Quote_Item_Collection extends Mage_Sales_Model_Mysql4_Quote_Item_Collection
{

	/**
	 * Add products to items and item options
	 * This override only changes one thing:
	 * The assigned product model is cloned, so that no two child products share
	 * the same parent object. This is needed because the custom product options
	 * are set on the parent product models. So if one simple child product with
	 * a required cpo is added to the cart, and then the same configurable with
	 * a different child without the required cpo is added, the check if all re-
	 * quired options are set failes.
	 *
	 * @return Mage_Sales_Model_Mysql4_Quote_Item_Collection
	 */
	protected function _assignProducts()
	{
		Varien_Profiler::start('QUOTE:' . __METHOD__);
		$productIds = array();
		foreach ($this as $item)
		{
			$productIds[] = $item->getProductId();
		}
		$this->_productIds = array_merge($this->_productIds, $productIds);

		$productCollection = Mage::getModel('catalog/product')->getCollection()
						->setStoreId($this->getStoreId())
						->addIdFilter($this->_productIds)
						->addAttributeToSelect(Mage::getSingleton('sales/quote_config')->getProductAttributes())
						->addOptionsToResult()
						->addStoreFilter()
						->addUrlRewrite();

		Mage::dispatchEvent('sales_quote_item_collection_products_after_load', array('product_collection' => $productCollection));

		$recollectQuote = false;
		foreach ($this as $item)
		{
			/*
			 * This is the only change compared to the original parent method ##################################
			 * Clone the product model
			 */
			$product = $productCollection->getItemById($item->getProductId());
			if ($product && is_object($product)) $product = clone $product;
			//$product = clone $productCollection->getItemById($item->getProductId());
			if ($product)
			{
				/*
				 * End of the change compared to the parent method ##############################################
				 */
				$product->setCustomOptions(array());

				foreach ($item->getOptions() as $option)
				{
					/**
					 * Call type specified logic for product associated with quote item
					 */
					$product->getTypeInstance(true)->assignProductToOption(
							$productCollection->getItemById($option->getProductId()),
							$option,
							$product
					);
				}
				$item->setProduct($product);
			}
			else
			{
				$item->isDeleted(true);
				$recollectQuote = true;
			}
			$item->checkData();
		}

		if ($recollectQuote && $this->_quote)
		{
			$this->_quote->collectTotals();
		}
		Varien_Profiler::stop('QUOTE:' . __METHOD__);
		return $this;
	}

}

