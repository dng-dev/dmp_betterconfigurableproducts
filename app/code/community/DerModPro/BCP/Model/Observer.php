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

class DerModPro_BCP_Model_Observer
{
	const SELECTION_VAR_NAME = 's';

	/*
	 * Mage_Core_Block_Abstract::CACHE_GROUP only exists since 1.4
	 * As long as Magento 1.3 still is supported duplicate the constant here
	 */
    const CACHE_GROUP = 'block_html';

	/**
	 * Call prepare on the products before the page content for the ajax updates is generated.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function bcpCatalogProductViewUpdateBefore($observer)
	{
		$simpleProduct = $observer->getEvent()->getProduct();
		$parentProduct = $observer->getEvent()->getParentProduct();

		$this->_prepareProducts($parentProduct, $simpleProduct);
	}

	/**
	 * Build the json parameters for the configurable product view updates.
	 * Called when making ajax page updates.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function bcpCatalogProductViewUpdate($observer)
	{
		$responseData = $observer->getEvent()->getResponseData();
		$simpleProduct = $observer->getEvent()->getProduct();
		$parentProduct = $observer->getEvent()->getParentProduct();
		$pageData = $observer->getEvent()->getPageData();
		Mage::getModel('bcp/updater')->loadUpdateData($responseData, $simpleProduct, $parentProduct, $pageData);
	}

	/**
	 * Replace the updated sections for the default simple product if one is configured.
	 * This event doesn't come into play when the ajax updates are made, this is only for
	 * the default simple product functionality.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function httpResponseSendBefore($observer)
	{
		$request = Mage::app()->getRequest();
		if ($request->getActionName() == 'view' &&
			$request->getControllerName() == 'product' &&
			$request->getModuleName() == 'catalog')
		{
			$parentProduct = Mage::registry('current_product');
			if ($parentProduct &&
				$parentProduct->getId() &&
				$parentProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
			{
				if (! $parentProduct->getBcpDefaultProductSku()) return;

				//get the productId with the SKU
                $simpleProductId = Mage::getResourceModel('catalog/product')
                    ->getIdBySku($parentProduct->getBcpDefaultProductSku());

                // if productID is not null, load the simple product with via ID to get all
                // product informations
                if (! $simpleProductId ) return;
                $simpleProduct = Mage::getModel('catalog/product')->load($simpleProductId);

				if (! $simpleProduct->getId() ||
					! in_array(Mage::app()->getWebsite()->getId(), $simpleProduct->getWebsiteIds()))
				{
					return;
				}

				$this->_prepareProducts($parentProduct, $simpleProduct);

                $response = Mage::app()->getFrontController()->getResponse();
//                 $response = $observer->getEvent()->getResponse();

				$configProductHtml = $response->getBody();
				$simpleProductHtml = $this->_getProductViewHtml($simpleProduct);

				$html = Mage::getModel('bcp/updater')->processHtml($configProductHtml, $simpleProductHtml, $simpleProduct, $parentProduct);

				$response->setBody($html);
			}

		}
	}

	/**
	 * Prepare the parent and the simple product for the copying over the attribute.
	 * Used for default simple product and for ajax updates as well as displays outside the product detail page.
	 *
	 * @param Mage_Catalog_Model_Product $parentProduct
	 * @param Mage_Catalog_Model_Product $simpleProduct
	 * @return DerModPro_BCP_Model_Observer
	 */
	protected function _prepareProducts(Mage_Catalog_Model_Product $parentProduct, Mage_Catalog_Model_Product $simpleProduct)
	{
		if (Mage::helper('bcp')->getConfig('keep_configurable_media_gallery'))
		{
			$images = $parentProduct->getMediaGalleryImages();
			$simpleProduct->setData('media_gallery_images', $images);
		}
		
		if (Mage::helper('bcp')->getConfig('keep_configurable_description'))
		{
			$description = $parentProduct->getDescription();
			$simpleProduct->setData('description', $description);
		}

		if (Mage::helper('bcp')->getConfig('update_price'))
		{
			/*
			 * Load the simple products tier prices and set the data on the configurable product
			 */
			$simpleProduct->getTierPrice();
			$parentProduct->setData('tier_price', $simpleProduct->getData('tier_price'));
		}
		
		$simpleProduct->setBcpParentProduct($parentProduct);

		return $this;
	}

	/**
	 * Showing the product images of the associated simple products that might have the visibility set to nowhere,
	 * enable the producs to be loaded and displayed in the product image gallery.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogProductLoadAfter($observer)
	{
		if (! Mage::app()->getStore()->isAdmin())
		{
			$product = $observer->getEvent()->getProduct();

			if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
			{
				/*
				 * Set the default simple product if an id is specified via query string
				 */
				if ($defaultSimpleProduct = (int) Mage::app()->getRequest()->getParam(self::SELECTION_VAR_NAME, 0))
				{
					$childrenIds = $product->getTypeInstance(true)->getChildrenIds($product->getId());
					if ($childrenIds && isset($childrenIds[0]) && in_array($defaultSimpleProduct, $childrenIds[0]))
					{
						$product->setBcpDefaultProductSku(
						    Mage::helper('bcp')->getSkuByProductId($defaultSimpleProduct)
                        );
					}
				}

				/*
				 * Set the default simple product if none is set to the cheapest
				 */
				if (! $product->getBcpDefaultProductSku() && Mage::helper('bcp')->getUseCheapestChildAsDefault($product))
				{
					/*
					 * According to the system configuration the cheapest simple product should be used as the default, but none is set.
					 *
					 * This is a little resource intensive, the prefered method would be to re-save all products.
					 */
					$cheapestChild = Mage::helper('bcp')->getCheapestChildProduct($product);
					if ($cheapestChild && $cheapestChild->getId())
					{
						$product->setBcpDefaultProductSku($cheapestChild->getSku());
					}
				}
			}

			/*
			 * Set the product visibility and update the products attributes
			 */
			$request = Mage::app()->getRequest();
			if (
				$request->getActionName() == 'gallery' &&
				$request->getControllerName() == 'product' &&
				$request->getModuleName() == 'catalog'
			)
			{
				$product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH);
			};

			/*
			 * Whenever the configurable product is displayed, we want to show the selected default simple products values.
			 * This also triggers the event for external modules to set values on the configurable product.
			 * Thats the reason we also need this in the product detail view.
			 */
			$this->_updateConfigurableProductAttributes($product);
		}
	}

	/**
	 * Set the default simple product images and price on configurable products if applicable.
	 * Used for the product list view.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogProductCollectionLoadAfter($observer)
	{
		if (! Mage::app()->getStore()->isAdmin())
		{
			$collection = $observer->getEvent()->getCollection();
			foreach ($collection as $product)
			{
				$this->_updateConfigurableProductAttributes($product);
			}
		}
	}

	/**
	 * Set the default simple product images and price on configurable products if applicable.
	 * Used for product list view and other places outside the product detail view.
	 * Also called on the product detail view to give other extensions the option to set values via the event.
	 *
	 * @param Mage_Catalog_Model_Product|Mage_Catalog_Model_Product_Compare_Item|Mage_Wishlist_Model_Item $product
	 */
	protected function _updateConfigurableProductAttributes(Mage_Core_Model_Abstract $product)
	{
		if (
			$product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ||
			! $product->getBcpDefaultProductSku()
		)
		{
			return;
		}

		if ($product->getBcpDefaultProductSku() != $product->getBcpDefaultLoaded())
		{
			$simpleProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $product->getBcpDefaultProductSku());

			$this->_prepareProducts($product, $simpleProduct);

			if ($simpleProduct->getId())
			{
				/*
				 * Update Price Data
				 */
				$product
					->setPrice($simpleProduct->getPrice())
					->setSpecialPrice($simpleProduct->getSpecialPrice())
					->setSpecialFromDate($simpleProduct->getSpecialFromDate())
					->setSpecialToDate($simpleProduct->getSpecialToDate())
					->setFinalPrice($simpleProduct->getFinalPrice())
				;

				if (Mage::helper('bcp')->checkUpdateDocumentPart('images', $product))
				{
					$product->setImage($simpleProduct->getImage())
						->setThumbnail($simpleProduct->getThumbnail())
						->setSmallImage($simpleProduct->getSmallImage())
					;
				}
				if (Mage::helper('bcp')->checkUpdateDocumentPart('short_desc', $product))
				{
					$product->setShortDescription($simpleProduct->getShortDescription());
				}
				if (Mage::helper('bcp')->checkUpdateDocumentPart('collateral', $product) && ! Mage::helper('bcp')->getConfig('keep_configurable_description'))
				{
					$product->setDescription($simpleProduct->getDescription());
				}
				
				Mage::dispatchEvent('bcp_update_defaults_on_configurable_product', array('product' => $product, 'simple_product' => $simpleProduct));
			}
			$product->setBcpDefaultLoaded($simpleProduct->getSku());
		}
	}

	/**
	 * Make sure the simple products final price is used.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogProductGetFinalPrice($observer)
	{
		$product = $observer->getEvent()->getProduct();
		$qty = $observer->getEvent()->getQty();
		if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
		{
			if ($product->getCustomOption('simple_product'))
			{
				$simpleProduct = $product->getCustomOption('simple_product')->getProduct();
			}
			
			if (isset($simpleProduct) && $simpleProduct->getId())
			{
				if (! $qty)
				{
					/*
					 * Workaround because the event doesn't contain the qty.
					 * This has changed in Magento 1.4, BUT as long as Version 1.3 is supported keep this.
					 */
					$qty = $product->getBcpFinalPriceQty() ? $product->getBcpFinalPriceQty() : 1;
				}
                
                //if the simple product has no price attribute loaded
                // reload the whole simple product with its ID
                if (true === is_null($simpleProduct->getPrice()))
                {
                    $simpleProduct = Mage::getModel('catalog/product')->load($simpleProduct->getId());
                }
                
				$finalPrice = $simpleProduct->getFinalPrice($qty);
				$product->setFinalPrice($finalPrice);
			}
		}
	}

	/**
	 * Generate and return the product detail html for the given product.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	protected function _getProductViewHtml(Mage_Catalog_Model_Product $product)
	{
		$output = $this->_loadProductViewCache($product);
		if (! $output)
		{
			/*
			 *  Set up environment
			 */
			$product->setBcpParentProduct(Mage::registry('current_product'));
			
			Mage::unregister('product');
			Mage::unregister('current_product');
			Mage::register('product', $product);
			Mage::register('current_product', $product);

			$layout = Mage::getModel('core/layout')
				->setDirectOutput(false);
			$update = $layout->getUpdate();
			foreach (Mage::helper('bcp')->getLayoutUpdateHandles() as $handle)
			{
				$update->addHandle($handle);
			}
			$update->load();
			$layout->generateXml()->generateBlocks();
			$output = $layout->getOutput();
			$this->_saveProductViewCache($product, $output);

			Mage::unregister('product');
			Mage::unregister('current_product');
			Mage::register('product', $product->getBcpParentProduct());
			Mage::register('current_product', $product->getBcpParentProduct());
		}
		return $output;
	}

	/**
	 * Load the simple product html view from cache if available
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return string
	 */
	protected function _loadProductViewCache(Mage_Catalog_Model_Product $product)
	{
		$responseData = '';

		/*
		 * Mage_Core_Block_Abstract::CACHE_GROUP only exists since 1.4
		 * As long as Magento 1.3 still is supported duplicate the constant here
		 */
		if ($this->_getCacheLifetime() > 0 && Mage::app()->useCache(self::CACHE_GROUP))
		{
			$key = Mage::helper('bcp')->getCacheKey('PRODUCT_' . $product->getId());
			if ($cacheData = Mage::app()->loadCache($key))
			{
				$responseData = $cacheData;
			}
		}
		return $responseData;
	}

	/**
	 * Save the product view page in the magento cache
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param string $data
	 * @return DerModPro_BCP_Model_Observer
	 */
	protected function _saveProductViewCache(Mage_Catalog_Model_Product $product, $data)
	{
		if ($this->_getCacheLifetime() > 0 && Mage::app()->useCache(self::CACHE_GROUP))
		{
			$key = Mage::helper('bcp')->getCacheKey('PRODUCT_' . $product->getId());
			Mage::app()->saveCache($data, $key, $this->_getCacheTags(), $this->_getCacheLifetime());
		}
		return $this;
	}

	/**
	 * Return the cache tages for the simple product page cache
	 *
	 * @return array
	 */
	protected function _getCacheTags()
	{
		$tags = array(
			self::CACHE_GROUP,
			Mage_Catalog_Model_Product::CACHE_TAG,
			Mage_Core_Model_Store_Group::CACHE_TAG,
		);

		return $tags;
	}

	/**
	 * Return the simple product page cache lifetime configured in the BCP advanced settings
	 *
	 * @return string
	 */
	protected function _getCacheLifetime()
	{
		return Mage::helper('bcp')->getAdvancedConfig('html_page_cache_time');
	}

	public function catalogProductSaveAfter($observer)
	{
		$product = $observer->getEvent()->getProduct();

		/*
		 * Display warning in admin interface if standard price options are used
		 */
		if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
		{
			/*
			 * During Dataflow the type instance returned sometimes is messed up.
			 * NOT getting a singleton type instance, and, to be 10% sure, checking the method exists should help ;)
			 */
			$typeInstance = $product->getTypeInstance();
			if (method_exists($typeInstance, 'getConfigurableAttributeCollection'))
			{
				if ($attributes = $typeInstance->getConfigurableAttributeCollection($product))
				{
					foreach ($attributes as $attribute)
					{
						if ($attribute->getPrices())
						{
							foreach ($attribute->getPrices() as $option)
							{
								if (isset($option['pricing_value']) && 0 != $option['pricing_value'])
								{
                                    $msg = Mage::helper('bcp')->__(
                                        '[SKU %s] Please do not use the Standard Price Options with the Better Configurable Products Extension (they will be added to the selected simple products price)!',
                                        $product->getSku()
                                    );
									Mage::throwException($msg);
								}
							}
						}
					}
				}
			}
		}

		/*
		 * Set the cheapest default simple product, if configured in the system settings
		 */
		if (Mage::helper('bcp')->getUseCheapestChildAsDefault($product))
		{
			if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
			{
				$cheapestChild = Mage::helper('bcp')->getCheapestChildProduct($product);
				if ($cheapestChild && $cheapestChild->getSku() != $product->getBcpDefaultProductSku())
				{
					$product->setBcpDefaultProductSku($cheapestChild->getSku())
						->getResource()->saveAttribute($product, 'bcp_default_product_sku');
				}
			}
			elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
			{
				$stockItem = $product->getStockItem();
				if (
					$product->getOrigData('price') != $product->getPrice() ||
					$product->getOrigData('special_price') != $product->getSpecialPrice() ||
					$this->_hasTierPriceChanged($product) ||
					($stockItem->getManageStock() && $stockItem->getOrigData('is_in_stock') != $stockItem->getData('is_in_stock'))
				)
				{
					/*
					 * Set the default simple product on all parent products
					 */
					if (null == $product->getParentProductIds())
					{
						$product->loadParentProductIds();
					}
					if ($product->getParentProductIds())
					{
						$parentProducts = $product->getCollection()
							->addIdFilter($product->getParentProductIds())
							->addStoreFilter($product->getStoreId())
							->addAttributeToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
							->addAttributeToSelect(array('bcp_default_product_sku'));
						;
						foreach ($parentProducts as $parent)
						{
							$cheapestChild = Mage::helper('bcp')->getCheapestChildProduct($parent);
							if ($cheapestChild->getSku() != $parent->getBcpDefaultProductSku())
							{
								$parent->setBcpDefaultProductSku($cheapestChild->getSku())
									->getResource()->saveAttribute($parent, 'bcp_default_product_sku');
							}
						}

					}
				}
			}
		}
	}

	/**
	 * Check if the tier price has changed on the product.
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return bool
	 */
	protected function _hasTierPriceChanged(Mage_Catalog_Model_Product $product)
	{
		$orig = $product->getOrigData('tier_price');
		$new = $product->getData('tier_price');
		
		if (count($orig) != count($new)) return true;

		if (is_array($new))
		{
			foreach ($new as $i => $tier)
			{
				if ($tier['website_id'] != $orig[$i]['website_id']) return true;

				if ($tier['price_qty'] != $orig[$i]['price_qty']) return true;

				if ($tier['price'] != $orig[$i]['price']) return true;

				if (isset($tier['delete']) && $tier['delete']) return true;
			}
		}

		return false;
	}

	/**
	 * Add the custom product option selectors if configured
	 *
	 * @param Varien_Object $observer
	 */
	public function coreBlockAbstractToHtmlBefore($observer)
	{
		$block = $observer->getEvent()->getBlock();
		if ($block instanceof Mage_Catalog_Block_Product_View_Options)
		{
			if (Mage::helper('bcp')->getConfig('update_cpo'))
			{
				$startTag = '';
				$product = $block->getProduct();
				if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
				{
					$emptyNode = (string) Mage::helper('bcp')->getThemeConfig('update_cpo_empty_');

					$startTag = 'div class="bcp-cpo-configurable"';
					$endTag = '/div>' . substr($emptyNode, 0, -1); // the trailing > is added automatically
				}
				elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
				{
					$startTag = 'div class="bcp-cpo-simple"';
					$endTag = '/div';
				}
				if ($startTag)
				{
					$block->setFrameTags($startTag, $endTag);
				}
			}
		}
	}

	/**
	 * Set the cache lifetime on configurable product page view blocks
	 *
	 * @param Varien_Object $observer
	 */
	public function coreBlockAbstractPrepareLayoutBefore($observer)
	{
		$block = $observer->getEvent()->getBlock();
		if ($block instanceof Mage_Catalog_Block_Product_View_Type_Configurable)
		{
			if (! $block->getBcpNoCache() && ($lifetime = $this->_getCacheLifetime()) && is_null($block->getCacheLifetime()))
			{
				$key = Mage::helper('bcp')->getCacheKey($block->getCacheKey() . '.' . $block->getNameInLayout());
				$id = $block->getProduct() ? $block->getProduct()->getId() : 0;
				$block->setCacheKey($key . '_' . $id);
				$block->setCacheLifetime($lifetime);
			}
		}
	}

	/**
	 * Set the child product custom options on the parent option.
	 * Thst way the cpo's off the parent and the child options can be combined.
	 *
	 * @param Varien_Object $observer
	 */
	public function salesQuoteItemSetProduct($observer)
	{
		$quoteItem = $observer->getQuoteItem();
		$product = $observer->getProduct();

		if ($product->isConfigurable() && $quoteItem->getHasChildren())
		{
			foreach ($quoteItem->getChildren() as $child)
			{
				foreach ($child->getProduct()->getProductOptionsCollection() as $option)
				{
					$option->setProduct($product);
					$product->addOption($option);
				}
			}
		}
	}

	/**
	 * If a product was added to the cart set a flag to recollect the totals on the next page
	 * 
	 * only necessary if redirect to cart is disabled
	 * without this fix the cart sidebar is 0 for configurable products
	 *
	 * @param Varien_Object $observer
	 */
	public function checkoutCartAddProductComplete($observer)
	{
	    if (0 == Mage::getStoreConfig('checkout/cart/redirect_to_cart')) {
	        Mage::getSingleton('core/session')->setBcpRecollectTotals(true);
	    }
	}

	/**
	 * 
	 * Recollect the totals if a product was added and option redirect to cart is disabled
	 * 
	 * @param Varien_Object $observer
	 */
	public function controllerActionLayoutLoadBefore($observer)
	{
	    if (0 == Mage::getStoreConfig('checkout/cart/redirect_to_cart')
	        && true === Mage::getSingleton('core/session')->getBcpRecollectTotals()) {
    	    $quote = Mage::getModel('checkout/cart')->getQuote();
    	    $quote->setTotalsCollectedFlag(false)->collectTotals();
    	    Mage::getSingleton('core/session')->setBcpRecollectTotals(false);
	    }
	}
}
