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

$this->startSetup();

if (version_compare(Mage::getVersion(), '1.4.0', '>='))
{
	try
	{
		$process = Mage::getModel('index/indexer')->getProcessByCode('catalog_product_flat');
		$process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
	}
	catch (Exception $e)
	{ }
}

$this->endSetup();