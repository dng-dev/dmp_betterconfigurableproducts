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

class DerModPro_BCP_Model_Catalog_Product_Type_Configurable_Price extends Mage_Catalog_Model_Product_Type_Configurable_Price
{
	/**
	 * Override this method so the qty is passed as a parameter to the event, too.
	 * Obsolete in Magento 1.4.1.1, but keep this for as long as the mage 1.3 branch is supported.
	 *
	 * @param   double $qty
	 * @param   Mage_Catalog_Model_Product $product
	 * @return  double
	 */
	public function getFinalPrice($qty=null, $product)
	{
		$product->setBcpFinalPriceQty($qty);
		return parent::getFinalPrice($qty, $product);
	}
}