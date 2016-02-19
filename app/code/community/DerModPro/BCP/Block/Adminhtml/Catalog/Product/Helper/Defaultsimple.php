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

class DerModPro_BCP_Block_Adminhtml_Catalog_Product_Helper_Defaultsimple extends Varien_Data_Form_Element_Select
{
	/**
	 * Retrieve Element HTML fragment
	 *
	 * @return string
	 */
	public function getElementHtml()
	{
		$storeId = Mage::app()->getRequest()->getParam('store', 0);
		if (Mage::helper('bcp')->getConfig('use_cheapest_simple_as_default', $storeId))
		{
			$this->setReadonly(true, true);
		}
		$this->setData('after_element_html', '<br/>' . $this->_getAutoSetExplanation());

		return parent::getElementHtml();
	}

	protected function _getAutoSetExplanation()
	{
		$text = array();
		$text[] = Mage::helper('bcp')->__('Will be set to the cheapest simple product when saved.');

		return '<label id="bcp_ds_desc">' . implode(' ', $text) . '</label>' . $this->_getSelectJs();
	}

	protected function _getSelectJs()
	{
		$storeId = Mage::app()->getRequest()->getParam('store', 0);

		$js  = '';
		$js .= "if ($('bcp_default_product_sku')) { ";
		$js .= " function bcpUpdateActiveState() { ";
		$js .= '  var bcpSystemSetting = ' . intval(Mage::helper('bcp')->getConfig('use_cheapest_simple_as_default', $storeId)) . '; ';
		$js .= "  var yes = " . DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::YES . '; ';
		$js .= "  var useDefault = " . DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT . '; ';
		$js .= "  var val = $('bcp_default_override').value; ";
		$js .= '  if (val == yes || (val == useDefault && bcpSystemSetting == yes)) { ';
		$js .= "   $('bcp_default_product_sku').disable(); ";
		$js .= "   $('bcp_ds_desc').show(); ";
		$js .= '  } else { ';
		$js .= "   $('bcp_default_product_sku').enable(); ";
		$js .= "   $('bcp_ds_desc').hide(); ";
		$js .= '  } ';
		$js .= ' } ';
		$js .= " document.observe('dom:loaded', function() { ";
		$js .= '  bcpUpdateActiveState(); ';
		$js .= "  $('bcp_default_override').observe('change', function() {bcpUpdateActiveState();} ); ";
		$js .= ' }); ';
		$js .= '}';
		return '<script type="text/javascript">'."\n".'//<![CDATA['."\n" . $js . "\n".'//]]>'."\n".'</script>';
	}




	
}