<?php

if (version_compare(PHP_VERSION, '5.3', '<')) {
    echo 'Magento Unit Tests can run only on PHP version higher then 5.3';
    exit(1);
}

$_baseDir = getcwd();


// Include Mage file by detecting app root
require_once $_baseDir . '/../magento/' . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

if (!Mage::isInstalled()) {
    echo 'Magento Unit Tests can run only on installed version';
    exit(1);
}

/* Replace server variables for proper file naming */
$_SERVER['SCRIPT_NAME'] = $_baseDir . DS . 'index.php';
$_SERVER['SCRIPT_FILENAME'] = $_baseDir . DS . 'index.php';

Mage::app('admin');
Mage::getConfig()->init();

spl_autoload_unregister(array(Varien_Autoload::instance(), 'autoload'));
spl_autoload_register(function($class) {
    try {
        return Varien_Autoload::instance()->autoload($class);
    } catch (Exception $e) {
        if (false !== strpos($e->getMessage(), 'Warning: include(')) {
            return null;
        } else {
            throw $e;
        }
    }
});
