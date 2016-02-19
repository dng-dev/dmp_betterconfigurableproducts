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

class DerModPro_BCP_Block_Catalog_Product_View_Type_Configurable_Bcp
    extends DerModPro_BCP_Block_Catalog_Product_View_Type_Configurable
{
    protected $_productOptionsCollection;

    /**
     * Set the module translaton namespace
     */
    public function _construct()
    {
        $this->setData('module_name', 'Mage_Catalog');
        parent::_construct();
        if ($lifetime = Mage::helper('bcp')->getAdvancedConfig('html_page_cache_time'))
        {
            $this->setCacheLifetime($lifetime);
            $this->setCacheTags(array(
                $this->_getHtmlBlockCacheTag(),
                Mage_Catalog_Model_Product::CACHE_TAG,
                Mage_Core_Model_Store_Group::CACHE_TAG,
            ));
        }
        else
        {
            $this->setCacheLifetime(null);
        }
    }

    protected function _getHtmlBlockCacheTag()
    {
        if (version_compare(Mage::getVersion(), '1.4.0', '<'))
        {
            return 'block_html';
        }
        else
        {
            return Mage_Core_Block_Abstract::CACHE_GROUP;
        }
    }

    public function getCacheKey()
    {
        $id = $this->getProduct() ? $this->getProduct()->getId() : 0;
        return Mage::helper('bcp')->getCacheKey(parent::getCacheKey() . '_' . $id);
    }

    public function getJsonConfig()
    {
        $bcpConfig = array(
            'cpPrice' => $this->_preparePrice($this->getProduct()->getFinalPrice()),
            'spPrices' => array(),
            'spImages' => array(),
            'spQty'    => array(),
            'spIsSaleable' => array(),
            'spOldPrices' => array(),
            'optionPrices' => array(),
            'optionTitles' => array(),
            'spDefault' => (string) Mage::helper('bcp')->getProductIdBySku($this->getProduct()->getBcpDefaultProductSku()),
            'cpId' => $this->getProduct()->getId(),
            'format' => array(
                'price' => array(
                    'prefix' => Mage::helper('bcp')->getConfig('price_prefix'),
                    'sufix' => Mage::helper('bcp')->getConfig('price_sufix'),
                ),
                'outOfStockSufix' => Mage::helper('bcp')->getConfig('out_of_stock_sufix'),
            ),
            'showOptionPrice' => (int) Mage::helper('bcp')->getConfig('show_option_price'),
            'showOptionPriceIfSame' => (int) Mage::helper('bcp')->getConfig('show_option_price_if_same'),
            'showOutOfStockOptions' => (int) Mage::helper('bcp')->getConfig('dropdown_out_of_stock_grey_products'),
            'unknownPriceLabel' => Mage::helper('bcp')->__(Mage::helper('bcp')->getConfig('unknown_price_label')),
            'updateProductUrl' => Mage::getUrl('catalog/product_bcp/update'),
            'updateSections' => array(
                'media' => (int) Mage::helper('bcp')->getConfig('update_images'),
                'shortDesc' => (int) Mage::helper('bcp')->getConfig('update_short_desc'),
                'collateral' => (int) Mage::helper('bcp')->getConfig('update_collateral'),
                'price' => (int) Mage::helper('bcp')->getConfig('update_price'),
                'cpo' => (int) Mage::helper('bcp')->getConfig('update_cpo'),
            ),
            'showSpinner' =>  (int) Mage::helper('bcp')->getConfig('show_spinner'),
            'preloadImages' => (int) (Mage::helper('bcp')->getConfig('preload_simple_product_images') && Mage::helper('bcp')->getConfig('update_images')),
            'useCache' => (int) (Mage::helper('bcp')->getAdvancedConfig('ajax_response_cache_time') > 0),
            'priceCloneSelektor' => Mage::helper('bcp')->getThemeConfig('price_clone_')
        );

        foreach ($this->getAllowProducts() as $product)
        {
            $bcpConfig['spOldPrices'][$product->getId()] = $this->_preparePrice($product->getPrice());
            $bcpConfig['spPrices'][$product->getId()] = $this->_preparePrice($product->getFinalPrice());
            $bcpConfig['spQty'][$product->getId()] = floor($product->getStockItem()->getQty());
            $bcpConfig['spIsSaleable'][$product->getId()] = (int) $product->isSaleable();
            
            /*
             * Set the custom product options information in the javascript configuration array
             */
            if ($this->getProductOptionsCollection()->count() > 0)
            {
                $bcpConfig['optionPrices'][$product->getId()] = $this->_getChildProductOptionPrices($product);
                //$bcpConfig['optionTitles'][$product->getId()] = $this->_getChildProductOptionTitles($product);
            }
            
            /*
             * Add the simple products main image if preloading is enabled
             */
            if ($bcpConfig['preloadImages'])
            {
                $bcpConfig['spImages'][$product->getId()] = (string) Mage::helper('catalog/image')->init($product, 'image');
            }
            
            $bcpConfig['optionPrices'][$product->getId()] = $this->_getChildProductOptionPrices($product);
        }
        return Zend_Json::encode($bcpConfig);
    }

    protected function getProductOptionsCollection()
    {
        if (! isset($this->_productOptionsCollection))
        {
            $this->_productOptionsCollection = $this->getProduct()->getProductOptionsCollection();
        }
        return $this->_productOptionsCollection;
    }

    /**
     * Fetch the custom product option.
     * For the cpo on the configurable product, the prices need to be adjusted to match
     * the simple products (needed only if the price adjustment a percentage, but we do it
     * for all simple products so we don't have to handle each case differently).
     * Also add the prices from the simple product cpo to the array.
     *
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _getChildProductOptionPrices($product)
    {

        $config = array();

        /*
         * Adjust the configurable products cpo for the simple products
         */
        if ($this->getProductOptionsCollection()->count() > 0)
        {
            foreach ($this->getProductOptionsCollection() as $option)
            {
                $option->setProduct($product);

                /* @var $option Mage_Catalog_Model_Product_Option */
                $priceValue = 0;
                if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT)
                {
                    $_tmpPriceValues = array();
                    foreach ($option->getValues() as $value)
                    {
                        /* @var $value Mage_Catalog_Model_Product_Option_Value */
                        $_tmpPriceValues[$value->getId()] = Mage::helper('bcp')->getChildProductOptionPrice($value->getPrice(true));
                    }
                    $priceValue = $_tmpPriceValues;
                }
                else
                {
                    $priceValue = Mage::helper('core')->currency($option->getPrice(true), false, false);
                }
                $config[$option->getId()] = $priceValue;
            }
        }

        /*
         * Add the simple products cpo
         */
        $childCpo = $product->getProductOptionsCollection();
        if ($childCpo->count() > 0)
        {
            foreach ($childCpo as $option)
            {
                $option->setProduct($product);
                $product->addOption($option);
                
                /* @var $option Mage_Catalog_Model_Product_Option */
                $priceValue = 0;
                if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                    $_tmpPriceValues = array();
                    $usePlainPrice = version_compare(Mage::getVersion(), '1.7.0', '<');
                    foreach ($option->getValues() as $value)
                    {
                        $optionsPrice = Mage::helper('core')->currency($value->getPrice(true), false, false);
                        if ($usePlainPrice) {
                            /* @var $value Mage_Catalog_Model_Product_Option_Value */
                            $_tmpPriceValues[$value->getId()] = $optionsPrice;
                        } else {
                            $_tmpPriceValues[$value->getId()] = array('price' => $optionsPrice); 
                        }
                    }
                    $priceValue = $_tmpPriceValues;
                }
                else
                {
                    $priceValue = Mage::helper('core')->currency($option->getPrice(true), false, false);
                }
                $config[$option->getId()] = $priceValue;
                
            }
        }
        return $config;
    }
}