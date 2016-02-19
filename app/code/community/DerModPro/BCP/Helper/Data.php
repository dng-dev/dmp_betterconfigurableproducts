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
class DerModPro_BCP_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Return a BCP specific setting form the main config group.
     *
     * @param string $key The config key under dermodpro_bcp/bcp/
     * @param int|string|Mage_Core_Model_Store $store
     * @return string
     */
    public function getConfig($key, $store = null)
    {
        return Mage::getStoreConfig('dermodpro_bcp/bcp/' . $key, $store);
    }

    /**
     * Return a BCP specific setting form the advanced config group.
     *
     * @param string $key The config key under dermodpro_bcp/bcp_advanced/
     * @param int|string|Mage_Core_Model_Store $store
     * @return mixed
     */
    public function getAdvancedConfig($key, $store = null)
    {
        return Mage::getStoreConfig('dermodpro_bcp/bcp_advanced/' . $key, $store);
    }

    /**
     * Get the default theme depending on the magento version
     *
     * @return string
     */
    public function getThemeSelection()
    {
        if (!($theme = Mage::helper('bcp')->getConfig('theme_selection'))) {
            /*
             * Fallback to version dependent default on new installations
             */
            $theme = version_compare(Mage::getVersion(), '1.4.0', '<') ? 'default' : 'default14';
        }
        return $theme;
    }

    /**
     * Get the specified theme config setting, falling back to the default theme
     *
     * @param string $key
     * @param string $theme
     * @return string
     */
    public function getThemeConfig($key, $theme = null)
    {
        if (is_null($theme)) {
            $theme = Mage::helper('bcp')->getThemeSelection();
        }
        foreach ($this->getThemeFallbackArray($theme) as $checkTheme) {
            $sectionFunc = $checkTheme == 'advanced' ? 'getAdvancedConfig' : 'getConfig';
            if ($config = $this->{$sectionFunc}($key . $checkTheme)) {
                /*
                 * Found a value configuration for the theme
                 */
                break;
            }
        }
        return $config;
    }

    /**
     * Return the array of themes config keys.
     *
     * @param string
     * @return array
     */
    public function getThemeFallbackArray($theme)
    {
        $themeFallback = array('advanced', $theme);
        if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
            if ($theme != 'default14')
                $themeFallback[] = 'default14';
        }
        if ($theme != 'default')
            $themeFallback[] = 'default';

        return $themeFallback;
    }

    /**
     * Check if the specified page part should be updated for the given product.
     *
     * @param string $key
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function checkUpdateDocumentPart($key, Mage_Catalog_Model_Product $product = null)
    {
        if ($alias = $this->getConfig('update_' . $key . '_handle')) {
            return $this->checkUpdateDocumentPart($alias, $product);
        }

        $setting = DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT;
        if (isset($product)) {
            $productSetting = $product->getDataUsingMethod('bcp_update_' . $key);
            if (!is_null($productSetting)) {
                $setting = $productSetting;
            }
        }

        if ($setting == DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT) {
            return $this->getConfig('update_' . $key);
        }
        return $setting == DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::YES;
    }

    /**
     * Return the layout update handle names to apply when building the simple product detail view page.
     *
     * @return array
     */
    public function getLayoutUpdateHandles()
    {
        $handles = Mage::getConfig()->getNode('global/bcp/update_handles')->asArray();
        return array_keys($handles);
    }

    /**
     * Check if the specified configurable product should use the cheapest simple product as default.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function getUseCheapestChildAsDefault(Mage_Catalog_Model_Product $product)
    {
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            if ($product->getBcpDefaultOverride() == DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::USE_DEFAULT) {
                return (bool) $this->getConfig('use_cheapest_simple_as_default', $product->getStoreId());
            }
            return $product->getBcpDefaultOverride() == DerModPro_BCP_Model_Entity_Attribute_Source_Yesnodefault::YES;
        }
        return false;
    }

    /**
     * Return the cheapest associated simple product for a configurable product.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Catalog_Model_Product|false
     */
    public function getCheapestChildProduct(Mage_Catalog_Model_Product $product)
    {
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $collection = $product->getTypeInstance(true)->getUsedProductCollection($product)
                ->addStoreFilter($product->getStoreId())
                ->addAttributeToSelect(array('price', 'special_price', 'special_from_date', 'special_to_date'));

            /*
             * only support saleable products
             */
            $collection->joinTable(
                $collection->getTable('cataloginventory_stock_item'), "product_id=entity_id", array('is_in_stock')
            )->addAttributeToFilter('is_saleable', 1);


            $cheapestPrice = null;
            $cheapestChildId = null;
            foreach ($collection as $childProduct) {
                if ($childProduct->getIsInStock() && (false === isset($cheapestPrice) || $childProduct->getFinalPrice() < $cheapestPrice)) {
                    $cheapestPrice = $childProduct->getFinalPrice();
                    $cheapestChildId = $childProduct->getId();
                }
            }
            if (isset($cheapestChildId) && $cheapestChildId) {
                return $collection->getItemById($cheapestChildId);
            }
        }
        return false;
    }

    /**
     * 
     * @param float $optionPrice
     * @return array|float
     */
    public function getChildProductOptionPrice($optionPrice)
    {
        $usePlainPrice = version_compare(Mage::getVersion(), '1.7.0', '<');
        $optionPrice = Mage::helper('core')->currency($optionPrice, false, false);
        if ($usePlainPrice) {
            return $optionPrice;
        }

        return array(
            // TODO(nr): add missing entries: excludeTax, includeTax, oldPrice, priceValue, type
            'price' => $optionPrice
        );
    }

    /**
     * Build and return a page block cache key
     *
     * @param string|int $extraData
     * @return string
     */
    public function getCacheKey($extraData)
    {
        $key = 'BCP_PAGE_CACHE_' . Mage::app()->getStore()->getId()
            . ($extraData !== '' ? '_' . $extraData : '')
            . '_' . Mage::getDesign()->getPackageName()
            . '_' . Mage::getDesign()->getTheme('template')
            . '_' . Mage::getSingleton('customer/session')->getCustomerGroupId()
        ;
        if ($selection = Mage::app()->getRequest()->getParam(DerModPro_BCP_Model_Observer::SELECTION_VAR_NAME)) {
            $key .= '_s_' . $selection;
        }

        return $key;
    }

    /**
     * Get product sku by id
     *
     * @param int $productId
     * @return string
     */
    public function getSkuByProductId($productId)
    {
        $skus = Mage::getResourceModel("catalog/product")->getProductsSku(array($productId));
        $entity = array_shift($skus);

        if (true === isset($entity['sku'])) {
            return $entity['sku'];
        } else {
            return null;
        }
    }

    /**
     * Get product id by sku
     *
     * @param string $sku
     * @return int
     */
    public function getProductIdBySku($sku)
    {
        return Mage::getResourceModel("catalog/product")->getIdBySku($sku);
    }

    /**
     * returns the prefix if there is a cheaper simple product for the given configurable
     * 
     * @param Mage_Catalog_Product $product
     * @return string | boolean
     */
    public function getBcpPricePrefix($product)
    {
        // check if product type is configurable and the option 'use_cheapest_simple_as_default'
        // is enabled OR the configurable product hast the attribute to use simple as default set to true
        // and there is a simple product set as default
        if ($product->getTypeID() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
           && (Mage::helper('bcp')->getConfig('use_cheapest_simple_as_default') || $product->getBcpDefaultOverride() == 1 )
           && $product->getBcpDefaultOverride() != 2 
           && false === is_null($product->getBcpDefaultProductSku())) {

            $childProducts = Mage::getModel('catalog/product_type_configurable')
                ->getUsedProducts(null, $product);

            foreach ($childProducts as $simpleProduct) {

                if ($simpleProduct->getFinalPrice() != $product->getFinalPrice()) {
                    return Mage::helper('bcp')->__('Price from:');
                }
            }
        }
        return false;
    }
    
      /**
     * returns the support-email from config.xml
     * 
     * @return string
     */
    public function getSupportMail()
    {
        return Mage::getStoreConfig('dermodpro_bcp/support/support_mail');
    }
}