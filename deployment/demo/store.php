#!/usr/bin/php
<?php 

// see Packr
require_once dirname(__FILE__) . '/../../app/Mage.php';

// Set robots to "noindex, nofollow" and demostore to true
$app = Mage::app();

$config = Mage::getModel('core/config');

/* @var $config Mage_Core_Model_Config */
/* seo and legals */
$config->saveConfig('design/head/default_robots', 'NOINDEX,NOFOLLOW');
$config->saveConfig('design/head/demonotice', '1');
/* demo extension */
$config->saveConfig('demo/frontend/active', '1');
$config->saveConfig('demo/adminhtml/active', '1');
$config->saveConfig(
    'demo/adminhtml/logins',
    serialize(array(
        array(),
        array(
            'username'  => 'demo',
            'password'  => 'demo',
            'parameter' => 'demo_en'
        ),
        array(
            'username'  => 'demo_de',
            'password'  => 'demo',
            'parameter' => 'demo_de'
        )
    ))
);
/* allow template symlinks */
$config->saveConfig('dev/template/allow_symlink', '1');

// preconditions:
// * sample data are installed (including different store views)
// * german setup is installed (setting taxes, locale etc.)

// use url paths to depict the current store view
$config->saveConfig('web/url/use_store', '1');

// query german and english stores
$collection = Mage::getModel('core/store')
    ->getCollection()
    ->addFieldToFilter('code', array('in' => array('german', 'default')));

foreach ($collection as $store) {
    /* @var $store Mage_Core_Model_Store */
    if ($store->getCode() === 'default') {
        // set url path
        $store->setCode('en')->save();
        // reset store locale to en_US
        $config->saveConfig('general/locale/code', 'en_US', 'stores', $store->getId());
    } elseif ($store->getCode() === 'german') {
        // set url path
        $store->setCode('de')->save();
    }
}
