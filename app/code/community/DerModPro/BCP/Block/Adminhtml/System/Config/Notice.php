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
class DerModPro_BCP_Block_Adminhtml_System_Config_Notice 
    extends Mage_Adminhtml_Block_Abstract 
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Custom template
     *
     * @var string
     */
    protected $_template = 'bcp/system/config/notice.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $fieldset)
    {
        $originalData = $fieldset->getOriginalData();
        $this->addData(array(
            'fieldset_label' => $fieldset->getLegend(),
        ));
        return $this->toHtml();
    }

    public function getVersion()
    {
        return (string) Mage::getConfig()->getNode('modules')->children()->DerModPro_BCP->version;
    }
}