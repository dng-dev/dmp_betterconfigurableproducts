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

$sections = array(
	'catalog/bcp/' => 'dermodpro_bcp/bcp/'
);

foreach ($sections as $from => $to)
{
	if (substr($from, -1) !== '/') Mage::throwException(sprintf('Config section source path "%s" does not end with a / character', $from));
	if (substr($to, -1) !== '/') Mage::throwException(sprintf('Config section source path "%s" does not end with a / character', $from));

	$select = $this->getConnection()->select()
		->from($this->getTable('core_config_data'), '*')
		->where("`path` LIKE ?", $from . '%')
	;

	foreach ($this->getConnection()->fetchAll($select) as $row)
	{
		$newPath = $to . substr($row['path'], strlen($from));

		$this->getConnection()->update('core_config_data',
			array('path' => $newPath),
			$this->getConnection()->quoteInto("`config_id`=?", $row['config_id'])
		);
	}
}


$this->endSetup();