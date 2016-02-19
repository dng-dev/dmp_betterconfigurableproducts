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

class DerModPro_BCP_Block_Checkout_Cart_Item_Renderer_Configurable extends Mage_Checkout_Block_Cart_Item_Renderer_Configurable
{
	/**
	 * Set the module translaton namespace
	 */
	public function _construct()
	{
		$this->setData('module_name', 'Mage_Checkout');
	}

	/**
	 * Get url to item product
	 *
	 * @return string
	 */
	public function getProductUrl()
	{
		$url = $this->getProduct()->getProductUrl();

		if ($children = $this->getItem()->getChildren())
		{
			foreach ($children as $child)
			{
				if ($child->getProductId())
				{
					$link = strpos($url, '?') === false ? '?' : '&';
					$url .= $link . DerModPro_BCP_Model_Observer::SELECTION_VAR_NAME . '=' . $child->getProductId();
					break;
				}
			}
		}
		return $url;
	}
}