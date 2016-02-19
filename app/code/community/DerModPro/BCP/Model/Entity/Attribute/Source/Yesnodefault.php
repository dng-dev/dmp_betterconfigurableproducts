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

class DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault
	extends Mage_Eav_Model_Entity_Attribute_Source_Boolean
{
	const USE_DEFAULT = 0;
	const YES = 1;
	const NO = 2;

	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions()
	{
		if (is_null($this->_options)) {
			$this->_options = array(
				array(
					'label' => Mage::helper('bcp')->__('Use Store Setting'),
					'value' =>  self::USE_DEFAULT,
				),
				array(
					'label' => Mage::helper('bcp')->__('Yes'),
					'value' =>  self::YES,
				),
				array(
					'label' => Mage::helper('bcp')->__('No'),
					'value' =>  self::NO,
				),
			);
		}
		return $this->_options;
	}
	
	/**
	 * Bugfix for Magento 1.3 - do not return the option array entry, only the label.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function getOptionText($value)
	{
		$option = parent::getOptionText($value);
		if (is_array($option) && isset($option['label']))
		{
			$option = $option['label'];
		}
		return $option;
	}
}