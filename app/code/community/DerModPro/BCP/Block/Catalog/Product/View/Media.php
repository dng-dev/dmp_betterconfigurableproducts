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

class DerModPro_BCP_Block_Catalog_Product_View_Media extends Mage_Catalog_Block_Product_View_Media
{
	/**
	 * Set the module translaton namespace
	 */
	public function _construct()
	{
		$this->setData('module_name', 'Mage_Catalog');
		parent::_construct();
	}

	/**
	 * Set the gallery link product id parameter to the configurable products id
	 *
	 * @param string $route
	 * @param array $params
	 * @return string
	 */
	public function getUrl($route='', $params=array())
	{
		if ($route == '*/*/gallery' && $this->getProduct()->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
		{
			if (Mage::helper('bcp')->getConfig('keep_configurable_media_gallery'))
			{
				if ($parentProduct = $this->getProduct()->getBcpParentProduct())
				{
					$params['id'] = $parentProduct->getId();
				}
			}
		}
		return parent::getUrl($route, $params);
	}
}