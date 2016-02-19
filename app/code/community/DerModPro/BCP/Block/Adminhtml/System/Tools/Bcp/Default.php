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

class DerModPro_BCP_Block_Adminhtml_System_Tools_Bcp_Default extends Mage_Adminhtml_Block_Template
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('bcp/system/tools/defaultchild.phtml');
	}

	protected function _prepareLayout()
	{
		parent::_prepareLayout();
		$this->setChild('setCheapestAsDefault',
			$this->getLayout()->createBlock('adminhtml/widget_button')
				->setData(array(
					'label' => Mage::helper('bcp')->__('Set Cheapest as Default'),
					'onclick' => "window.location.href='" . $this->getUrl('*/*/cheapest') . "'",
					'class'  => 'task'
				))
		);
		$this->setChild('unsetDefault',
			$this->getLayout()->createBlock('adminhtml/widget_button')
				->setData(array(
					'label' => Mage::helper('bcp')->__('Unset Default Products'),
					'onclick' => "window.location.href='" . $this->getUrl('*/*/unset') . "'",
					'class'  => 'task'
				))
		);
        $this->setChild('migrateCheapestProduct',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label' => Mage::helper('bcp')->__('Migrate cheapest Products'),
                    'onclick' => "window.location.href='" . $this->getUrl('*/*/migrateCheapest') . "'",
                    'class'  => 'task'
                ))
        );
	}

	public function getStatus($key)
	{
		$cacheKey = 'BCP_PROCESS_KEY_' . $key;
		$num = (int) Mage::app()->loadCache($cacheKey);
		return $num;
	}

	public function getConfigurableProductCount()
	{
		$count = $this->getData('configurable_product_count');
		if (is_null($count))
		{
			$count = $this->getConfigurableProductCollection()->load()->count();
			$this->setConfigurableProductCount($count);
		}
		return $count;
	}

	public function getConfigurableProductCollection()
	{
		$collection = $this->getData('configurable_product_collection');
		if (is_null($collection))
		{
			$collection = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToFilter('type_id', 'configurable')
			;
			$this->setConfigurableProductCollection($collection);
		}
		return $collection;
	}

	public function getCacheTag()
	{
		if (version_compare(Mage::getVersion(), '1.4.0', '<'))
		{
			return 'block_html';
		}
		return Mage_Core_Block_Abstract::CACHE_GROUP;
	}
}