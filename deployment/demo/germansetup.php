#!/usr/bin/php
<?php

// see Packr
require_once dirname(__FILE__) . GermanSetup::MAGEDIR . '/app/Mage.php';

class GermanSetup
{
    const MAGEDIR = '/../..';

    public static function init()
    {
        Mage::getSingleton('germansetup/setup_cms')->setup();
        Mage::getSingleton('germansetup/setup_agreements')->setup();
        Mage::getSingleton('germansetup/setup_email')->setup();
        Mage::getSingleton('germansetup/setup_tax')->setup();

        // default
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(1, 1);
        // taxable goods
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(2, 1);
        // shipping
        Mage::getSingleton('germansetup/setup_tax')->updateProductTaxClasses(4, 4);

        Mage::getModel('eav/entity_setup', 'core_setup')->setConfigData('germansetup/is_initialized', '1');
    }
}


$app = Mage::app('admin');
Mage_Core_Model_Resource_Setup::applyAllUpdates();
Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

try {
    GermanSetup::init();
} catch (Exception $ex) {
    $msg = "An error occured while initializing GermanSetup:\n";
    $msg.= $ex->getMessage() . ' (' . $ex->getFile() . ' l. ' . $ex->getLine() . ")\n";
    print($msg);
}

