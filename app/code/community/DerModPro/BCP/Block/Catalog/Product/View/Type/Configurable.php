<?php

class DerModPro_BCP_Block_Catalog_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{
    /* Get Allowed Products
     * 
     * Overwritten to add also products which are out of stock
     *
     * @return array
     */
    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            //BCP out of stock config check
            $showOutOfStock = (bool) (int) Mage::helper('bcp')->getConfig('show_out_of_stock_products');
            
            //Product Helper
            $productHelper = Mage::helper('catalog/product');
            $skipSaleableCheck = false;
            //CE >= 1.7
            if (true === method_exists($productHelper, 'getSkipSaleableCheck')
                && true === Mage::helper('catalog/product')->getSkipSaleableCheck()) {
                $skipSaleableCheck = true;
            }

            $products = array();
            $allProducts = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
            foreach ($allProducts as $product) {
                if ($product->isSaleable()
                    || (true === $showOutOfStock)
                    || (true === $skipSaleableCheck)) {
                    $products[] = $product;
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }
}