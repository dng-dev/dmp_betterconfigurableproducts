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

class DerModPro_BCP_Adminhtml_System_Tools_Bcp_DefaultController extends Mage_Adminhtml_Controller_Action
{
	protected $_configurableProducts;

	public function indexAction()
	{
		$this->loadLayout();
		$this->_addContent($this->getLayout()->createBlock('bcp/adminhtml_system_tools_bcp_default', 'bcp.default'));
		$this->renderLayout();
	}

	public function cheapestAction()
	{
		$cacheKey = $this->_getCacheKey('setCheapest');
		$state = (int) Mage::app()->loadCache($cacheKey);
		$collection = $this->getConfigurableProductCollection();
		$count = 0;
		foreach ($collection as $configProduct)
		{
			if ($count++ < $state) continue;

			$cheapestChild = Mage::helper('bcp')->getCheapestChildProduct($configProduct);
			
			/**** User Patch added ****/
			if (!is_object($cheapestChild)){ 
                continue; 
            }
			
			$configProduct->setBcpDefaultProductSku($cheapestChild->getSku())
				->getResource()
				->saveAttribute($configProduct, 'bcp_default_product_sku')
			;
			Mage::app()->saveCache($count, $cacheKey);
		}
		Mage::app()->removeCache($cacheKey);
		Mage::app()->cleanCache($this->_getCacheTags());
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bcp')->__('BCP Default set to cheapest Simple Product'));
		$this->_forward('index');
	}

	public function unsetAction()
	{
		$cacheKey = $this->_getCacheKey('unsetDefault');
		$state = (int) Mage::app()->loadCache($cacheKey);
		$collection = $this->getConfigurableProductCollection();
		$count = 0;
		foreach ($collection as $configProduct)
		{
			if ($count++ < $state) continue;

			$configProduct->setBcpDefaultProductSku('')
				->getResource()
				->saveAttribute($configProduct, 'bcp_default_product_sku')
			;
			Mage::app()->saveCache($count, $cacheKey);
		}
		Mage::app()->removeCache($cacheKey);
		Mage::app()->cleanCache($this->_getCacheTags());
		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bcp')->__('BCP Default Simple Products Unset'));
		$this->_forward('index');
	}
    
    public function migrateCheapestAction()
    {
        $cacheKey = $this->_getCacheKey('migrateCheapest');
        $state = (int) Mage::app()->loadCache($cacheKey);
        $collection = $this->getConfigurableProductCollection();
        $count = 0;
        foreach ($collection as $configProduct)
        {
            if ($count++ < $state) continue;
            
            //check if old value for "bcp_default_product" exists
            if (true === is_null($configProduct->getBcpDefaultProduct())
                || $configProduct->getBcpDefaultProduct() == 0) {
                continue;
            }
            
            //check if new value for "bcp_default_product_sku" doesn't exist yet
            if (false === is_null($configProduct->getBcpDefaultProductSku())) {
                continue;
            }
            
            //Set cheapest child
            $cheapestChild = Mage::getModel("catalog/product")->load($configProduct->getBcpDefaultProduct());
            
            /**** User Patch added ****/
            if (!is_object($cheapestChild)){ 
                continue; 
            }
            
            $configProduct->setBcpDefaultProductSku($cheapestChild->getSku())
                ->getResource()
                ->saveAttribute($configProduct, 'bcp_default_product_sku')
            ;
            Mage::app()->saveCache($count, $cacheKey);
        }
        Mage::app()->removeCache($cacheKey);
        Mage::app()->cleanCache($this->_getCacheTags());
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bcp')->__('Migration of product values from attribute "bcp_default_product" (product_id-based) to attribute "bcp_default_product_sku" (sku-based) succeeded.'));
        $this->_forward('index');
    }

	protected function _getCacheKey($key)
	{
		$cacheKey = 'BCP_PROCESS_KEY_' . $key;
		return $cacheKey;
	}

	protected function _getCacheTags()
	{
		$tags = array();

		if (version_compare(Mage::getVersion(), '1.4.0', '<'))
		{
			$tags[] = 'block_html';
		}
		else
		{
			$tags[] = Mage_Core_Block_Abstract::CACHE_GROUP;
		}
		return $tags;
	}

	public function getConfigurableProductCollection()
	{
		if (is_null($this->_configurableProducts))
		{
			$this->_configurableProducts = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
				->addAttributeToSelect('store_id')
                ->addAttributeToSelect('bcp_default_product')
                ->addAttributeToSelect('bcp_default_product_sku')
				->addOrder('entity_id', 'ASC')
			;
		}
		return $this->_configurableProducts;
	}

	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('system/tools/bcp');
	}
}