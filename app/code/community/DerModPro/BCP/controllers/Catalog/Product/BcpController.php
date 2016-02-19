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

class DerModPro_BCP_Catalog_Product_BcpController extends Mage_Core_Controller_Front_Action
{
	protected $_cacheTtl;

	/*
	 * Mage_Core_Block_Abstract::CACHE_GROUP only exists since 1.4
	 * As long as Magento 1.3 still is supported duplicate the constant here
	 */
    const CACHE_GROUP = 'block_html';
	
	public function updateAction()
	{
		/*
		 * Fix gallery links
		 */
		$this->getRequest()->setControllerName('product');

		$id = $this->getRequest()->getParam('id');
		$parentId = $this->getRequest()->getParam('parent_id');
		$responseData = $this->_loadCache($id, $parentId);
		
		if (! $responseData)
		{
			$product = Mage::getModel('catalog/product')
				->setStoreId(Mage::app()->getStore()->getId())
				->load($id);
			if (! $product->getId() ||
				($product->getWebsiteIds() && ! in_array(Mage::app()->getWebsite()->getId(), $product->getWebsiteIds())))
			{
				Mage::throwException(Mage::helper('bcp')->__('Product invalid (current website "%s" is not one of the products websites "%s")',
					Mage::app()->getWebsite()->getId(), implode(', ', (array) $product->getWebsiteIds()))
				);
			}

			/*
			 * Both are required
			 */
			Mage::register('product', $product);
			Mage::register('current_product', $product);

			$parentProduct = Mage::getModel('catalog/product')->load($parentId);
			$product->setBcpParentProduct($parentProduct);

			$update = $this->getLayout()->getUpdate();
			foreach (Mage::helper('bcp')->getLayoutUpdateHandles() as $handle)
			{
				$update->addHandle($handle);
			}

			$responseData = new Varien_Object(array('updates' => array(), 'product_id' => $id));

			/*
			 * Possibility to add extra update handles via an event observer
			 */
			Mage::dispatchEvent('bcp_catalog_product_view_update_before', array('response_data' => $responseData, 'product' => $product, 'parent_product' => $parentProduct, 'front' => $this));

			$this->loadLayoutUpdates()
				->generateLayoutXml()
				->generateLayoutBlocks();

			$this->getLayout()->setDirectOutput(false);

			$output = $this->getLayout()->getOutput();

			$pageData = new Varien_Object(array('html' => $output));

			/*
			 * Build the update parameters via event observer
			 */
			Mage::dispatchEvent('bcp_catalog_product_view_update', array('response_data' => $responseData, 'product' => $product, 'parent_product' => $parentProduct, 'page_data' => $pageData));
			$responseData = $responseData->getData();

			$this->_saveCache($id, $parentId, $responseData);
		}
		$this->getResponse()->setBody(Zend_Json::encode($responseData));
	}

	protected function _loadCache($id, $parentId)
	{
		$responseData = array();

		/*
		 * Mage_Core_Block_Abstract::CACHE_GROUP only exists since 1.4
		 * As long as Magento 1.3 still is supported duplicate the constant here
		 */
		if ($this->_getCacheLifetime() > 0 && Mage::app()->useCache(self::CACHE_GROUP))
		{
			if ($cacheData = Mage::app()->loadCache($this->_getCacheKey($id, $parentId)))
			{
				$responseData = unserialize($cacheData);
			}
		}
		return $responseData;
	}

	protected function _saveCache($id, $parentId, $data)
	{
		if ($this->_getCacheLifetime() > 0 && Mage::app()->useCache(self::CACHE_GROUP))
		{
			Mage::app()->saveCache(serialize($data), $this->_getCacheKey($id, $parentId), $this->_getCacheTags(), $this->_getCacheLifetime());
		}
		return $this;
	}

	protected function _getCacheLifetime()
	{
		if (is_null($this->_cacheTtl))
		{
			$this->_cacheTtl = Mage::helper('bcp')->getAdvancedConfig('ajax_response_cache_time');
		}
		return $this->_cacheTtl;
	}

	protected function _getCacheKey($id, $parentId)
	{
		$key = 'BCP_RESPONSE_' . Mage::app()->getStore()->getId();
		$key .= Mage::helper('bcp')->getThemeSelection();
		$key .= '_' . $id;
		$key .= '_' . $parentId;
		$key .= Mage::getSingleton('customer/session')->getCustomerGroupId();
		if ($selection = $this->getRequest()->getParam(DerModPro_BCP_Model_Observer::SELECTION_VAR_NAME))
		{
			$key .= '_s_' . $selection;
		}

		return $key;
	}

	protected function _getCacheTags()
	{
		$tags = array(
			self::CACHE_GROUP,
			Mage_Catalog_Model_Product::CACHE_TAG,
			Mage_Core_Model_Store_Group::CACHE_TAG,
		);

		return $tags;
	}
}