<?php

class DerModPro_BCP_Test_Helper_DataTest extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var Mage_Core_Model_Store
     */
    protected $store;

    public function setUp()
    {
        $this->store = Mage::app()->getStore(0)->load(0);
    }
    
    public function testGetConfig()
    {
        $key   = 'show_option_price';
        $path = 'dermodpro_bcp/bcp/' . $key;
        $this->store->resetConfig();
        $this->store->setConfig($path, 1);
        $this->assertEquals("1", Mage::helper('bcp/data')->getConfig($key));
    }
}
