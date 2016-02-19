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

class DerModPro_BCP_Model_System_Config_Source_Theme
	extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
			$mage13 = array('value' => 'default', 'label' => 'Magento 1.3 Default');
			$mage14 = array('value' => 'default14', 'label' => 'Magento 1.4 Default');

			if (version_compare(Mage::getVersion(), '1.4.0', '<'))
			{
				$first = $mage13;
				$second = $mage14;
			}
			else
			{
				$first = $mage14;
				$second = $mage13;
			}
        	$this->_options = array(
				$first['value'] => Mage::helper('bcp')->__($first['label']),
				$second['value'] => Mage::helper('bcp')->__($second['label']),
				'blue' => Mage::helper('bcp')->__('Blue Skin'),
				'blank' => Mage::helper('bcp')->__('Blank Theme'),
				'blank_seo' => Mage::helper('bcp')->__('Yoast Blank SEO Theme'),
				'modern' => Mage::helper('bcp')->__('Modern Theme'),
				//'advanced' => Mage::helper('bcp')->__('Advanced (use advanced settings below)'),
			);
        }
        return $this->_options;
    }

    public function getAllOptions()
    {
    	return $this->toOptionArray();
    }
}