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

class DerModPro_BCP_Model_Updater extends Varien_Object
{
	/**
	 * List of updates
	 *
	 * @var array
	 */
	protected $_documentUpdates;
        
        /**
         * Flag for is doctype exchanged 
         * There is a bug with HTML5 and DomDocument
         * @var bool 
         */
        protected $_isDoctypeExchanged;

	const SELECTOR_SEPERATOR = '&&&';

	/**
	 * Set an array with the updates ready for json encoding on the passed $responseData object.
	 * Used when the simple product data is loaded via ajax.
	 *
	 * @param Varien_Object $responseData
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Catalog_Model_Product $parentProduct
	 * @param Varien_Object $pageData
	 */
	public function loadUpdateData(Varien_Object $responseData, Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $parentProduct, Varien_Object $pageData)
	{
		$html = $this->_prepareSimpleProductHtml($pageData->getHtml(), $product, $parentProduct);

		$updates = $responseData->getUpdates();

		foreach($this->_getUpdatesArrayFromSimleProductHtml($product, $parentProduct, $html, $forJsonConfig = true) as $key => $data)
		{
			$updates[$key] = $data;
		}
		//Mage::log($updates);

		$responseData->setUpdates($updates);
	}

	/**
	 * Replace the parts to be updated in the configurable product html with the parts from the simple product html.
	 * Used for the default simple product feature.
	 *
	 * @param string $configurableProductHtml
	 * @param string $simpleProductHtml
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Catalog_Model_Product $parentProduct
	 * @return string
	 */
	public function processHtml($configurableProductHtml, $simpleProductHtml, Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $parentProduct)
	{
		$simpleProductHtml = $this->_prepareSimpleProductHtml($simpleProductHtml, $product, $parentProduct, $configurableProductHtml);

		$configDom = $this->_getDomModel($configurableProductHtml);
		$configDomPath = new DOMXPath($configDom);
		foreach($this->_getUpdatesArrayFromSimleProductHtml($product, $parentProduct, $simpleProductHtml, $forJsonConfig = false) as $key => $data)
		{
			for ($i = 0; $i < $data['node_list']->length; $i++) // should be only one match anyway
			{
				// get the parent so we can replace the child nodes
				// (assume we allways get a match since the same xpath matched a child on the simple product dom model)
				$parent = $configDomPath->query($data['xpath'] . '/..')->item(0);

				// get the matching node list from the configurable product dom model
				$configNodeList = $configDomPath->query($data['xpath']);

				// import (and clone) the new node into the configurable product dom model
				$newNode = $configDom->importNode($data['node_list']->item($i), true);

				for ($j = 0; $j < $configNodeList->length; $j++) // should be only one match here anyway
				{
					// replace the original node from the config html with the new node from the simple product html
					$item = $configNodeList->item($j);
					try
					{
						$parent->replaceChild($newNode, $item);
					}
					catch (Exception $e)
					{
						//Mage::log('replaceChild() error, found ' . $configNodeList->length . ' items for xpath ' . $data['xpath']);
						continue;
					}

					break ; // only replace the first match
				}
				break;
			}
		}

		// return the new html
		$html = $this->_getDocumentHtmlUsingsaveXml($configDom);
		return $html;
	}

	/**
	 * Use the saveHTML() Method to generate the page HTML.
	 * This is the old method, currently obsolete.
	 *
	 * @param DOMDocument $configDom
	 * @return string
	 */
	protected function _getDocumentHtmlUsingsaveHtml(DOMDocument $dom)
	{
		$html = $dom->saveHTML();
		//$html = mb_convert_encoding($html, 'UTF-8', 'HTML-ENTITIES'); // buggy since php 5.2, use html_entity_decode instead
		$html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');
		$html = preg_replace('/<(meta|link|img|input|br)([^>]+)>/i', '<$1$2 />', $html);
		return $html;
	}

	/**
	 * Use the saveXML() Method to render the page.
	 * A little cleanup is required to produce valid HTML from the XML.
	 *
	 * @param DOMDocument $configDom
	 * @return string
	 */
	protected function _getDocumentHtmlUsingSaveXml(DOMDocument $dom)
	{
		$html = '';
		/*
		 * Output nodes seperately to avoid the XML declaration at the start of the output <?xml version="1.0" ... ?>
		 */
		foreach ($dom->childNodes as $node)
		{
			if ($node instanceof DOMElement)
			{
				/*
				 * Remove attributes that are added automatically
				 */
				if ($node->hasAttributeNS(null, 'xmlns'))
				{
					$node->removeAttributeNS(null, 'xmlns');
				}
				if ($node->hasAttributeNS(null, 'xml:lang'))
				{
					$node->removeAttributeNS(null, 'xml:lang');
				}
			}
			$nodeHtml = $dom->saveXML($node) . "\n";
			$html .= $nodeHtml;
		}
		// stupid double cdata quotes... grml
		$html = preg_replace(array('#(?<!//)<!\[CDATA\[#', '#(?<!//)]]>#'), '', $html);
                
                // replace if the doctype is change
                if ($this->_isDoctypeExchanged) {
                        $html = preg_replace('/<\!DOCTYPE[^>]*>/', '<!DOCTYPE html>', $html);
                }
                
		return $html;
	}

	/**
	 * Fetch the update parts from the passed html
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @param Mage_Catalog_Model_Product $parentProduct
	 * @param string $html
	 * @param bool $forJsonConfig
	 * @return array
	 */
	protected function _getUpdatesArrayFromSimleProductHtml(Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $parentProduct, $html, $forJsonConfig)
	{
		$theme = Mage::helper('bcp')->getThemeSelection();
		$dom = $this->_getDomModel($html);
		$domPath = new DOMXPath($dom);
		$updates = array();
		foreach ($this->getDocumentUpdates() as $configKey)
		{
			/*
			 * Special case:
			 * Only update images if the selected simple product has some assigned
			 */
			/* DON'T USE THIS - MAKES THINGS SO MUCH MORE CONFUSING FOR ADMINS!
			 * ENFORCING CORRECT USAGE OF SETTINGS IS A BETTER POLICY
			 *
			if ($configKey == 'images')
			{
				if (! $product->getMediaGalleryImages() ||
					$product->getMediaGalleryImages()->count() == 0)
				{
					continue;
				}
			}
			*/
			if ($this->_updateDocumentPart($configKey, $parentProduct))
			{
				$xpathList = explode(self::SELECTOR_SEPERATOR, $this->_getThemeConfig('update_' . $configKey . '_dom_xpath_', $theme));
				$htmlSelectorList = explode(self::SELECTOR_SEPERATOR, $this->_getThemeConfig('update_' . $configKey . '_dom_csspath_', $theme));
				$prepare = $this->_getThemeConfig('update_' . $configKey . '_prepare_', $theme);
				$callback = $this->_getThemeConfig('update_' . $configKey . '_callback_', $theme);

				//if ($configKey == 'price') Mage::log(array('xpathlist' => $xpathList, 'cssselecttorlist' => $htmlSelectorList, 'prepare' => $prepare, 'callback' => $callback));

				foreach ($xpathList as $i => $xpath)
				{
					$nodeList = $domPath->query($xpath);
					$update = array();
					if ($forJsonConfig)
					{
						//if ($configKey == 'price') Mage::log(array('xpath' => $xpath, 'nodelist.length' => $nodeList->length, 'nodelist' => $this->_getDomNodeListAsHtml($nodeList)));
						if ($nodeList->length)
						{
							$this->_convertNodelistToHtmlBefore($nodeList, $configKey, $product, $parentProduct);
							$html = $this->_getDomNodeListAsHtml($nodeList);

						}
						else
						{
							$html = (string) $this->_getThemeConfig('update_' . $configKey . '_empty_', $theme);
						}

						$htmlSelector = isset($htmlSelectorList[$i]) ? $htmlSelectorList[$i] : $htmlSelectorList[0];

						$update['html'] = $html;
						$update['dom_selector'] = $htmlSelector;
						$update['prepare'] = (string) $prepare;
						$update['callback'] = (string) $callback;
					}
					else
					{
						$update['node_list'] = $nodeList;
						$update['xpath'] = $xpath;
					}
					$updates[] = $update;
				}
			}
		}

		return $updates;
	}

	/**
	 * DOMDocument::saveXML() escapes script tag contents with CDATA.
	 * The prototype JS evaluation in element replace operations chokes on them, so we need to remove them.
	 * Since DOMDocument::loadHTML() does no support setting options, the  LIBXML_NOCDATA cant be set (*sigh*)
	 *
	 * This solution is a bit hackish, a regex checking the section is in a <script> tag would be nicer.
	 * But, since I am lazy, I will leave this the way it is as long as nobody complains.
	 *
	 * @param string $html
	 * @return string
	 */
	protected function _removeCdataSections($html)
	{
		$html = str_replace(
			array(
				']]]]>',
				'<![CDATA[>',
				'<![CDATA[',
				']]>'
			),
			'', $html
		);

		return $html;
	}

	/**
	 * Reurn the document update parts array
	 *
	 * @return array
	 */
	protected function getDocumentUpdates()
	{
		if (! isset($this->_documentUpdates))
		{
			$this->_documentUpdates = array_keys(Mage::getConfig()->getNode('global/bcp/update_selector_list')->asArray());
		}
		return $this->_documentUpdates;
	}

	/**
	 * Checks wether the passed update part should be replaced for the specified configurable product.
	 * If no product is passed the store setting is returned.
	 *
	 * @param string $key
	 * @param Mage_Catalog_Model_Product $product
	 * @return bool
	 */
	protected function _updateDocumentPart($key, Mage_Catalog_Model_Product $product = null)
	{
		return Mage::helper('bcp')->checkUpdateDocumentPart($key, $product);
	}

	/**
	 * Get the specified theme config setting, falling back to the default theme
	 *
	 * @param string $key
	 * @param string $theme
	 * @return string
	 */
	protected function _getThemeConfig($key, $theme)
	{
		return Mage::helper('bcp')->getThemeConfig($key, $theme);
	}

	/**
	 * Fetch a DOMDocument model from the passed html, trying to silently recover from parse errors
	 *
	 * @param string $html
	 * @return DOMDocument
	 */
	protected function _getDomModel($html)
	{
                // check for doctype exchange
                $this->_checkIsDoctypeExchanged($html);
                
		$oldSetting = libxml_use_internal_errors(true);
		libxml_clear_errors();

		/*
		 * If you get an error because of the following line check the README or
		 * this thread: http://bonsai.php.net/bug.php?id=32743&edit=1
		 */
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
		//$dom->loadHtml($html);
		libxml_clear_errors();
		libxml_use_internal_errors($oldSetting);
		return $dom;
	}
        
        /**
        * Change DocType as there is a bug with HTML5 and DomDocument
        *
        * @param string $html
        * @return DerModPro_BCP_Model_Updater
        */
       protected function _checkIsDoctypeExchanged($html)
       {
           $this->_isDoctypeExchanged = false;
           if (strpos($html, '<!DOCTYPE html>') !== false) {

               $html = str_replace('<!DOCTYPE html>', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', $html);
               $this->_isDoctypeExchanged = true;
           }
           
           return $this;
       }

	/**
	 * Returns the HTML representation of the first DOMNode in the list
	 *
	 * @param DOMNodeList $nodeList
	 * @return string
	 */
	protected function _getDomNodeListAsHtml($nodeList)
	{
		$html = '';
		if ($nodeList->length)
		{
			for ($i = 0; $i < $nodeList->length; $i++)
			{
				$node = $nodeList->item(0);
				// a pitty we cant call saveHTML on single nodes
				$html .= $node->ownerDocument->saveXml($node);
				break; // only use the first match
			}
		}
		// DOMDocument::saveXML() escapes script tag contents with CDATA, remove those
		return $this->_removeCdataSections($html);
	}

	/**
	 * If some specific preparations need to be done before conversting the
	 * matched nodelist to html, this can be done here (e.g. remove <script> nodes).
	 *
	 * @param DOMNodeList $nodeList
	 * @param string $configKey
	 * @param Mage_Catalog_Model_Product $simpleProduct
	 * @param Mage_Catalog_Model_Product $parentProduct
	 * @return DerModPro_BCP_Model_Updater
	 */
	protected function _convertNodelistToHtmlBefore($nodeList, $configKey, $simpleProduct, $parentProduct)
	{
		switch ($configKey)
		{
			case 'cpo':
				/*
				 * Remove <script> tag from simple products cpo options.phtml template,
				 * otherwise the Product.Options JS is duplicated.
				 * This is only needed if the configurable product has cpo assigned, too.
				 */
				if ($nodeList->length && $nodeList->item(0)->childNodes->length && $parentProduct->getOptions())
				{
					foreach ($nodeList->item(0)->childNodes as $node)
					{
						if ($node->nodeType === XML_ELEMENT_NODE && $node->nodeName === 'script')
						{
							$nodeList->item(0)->removeChild($node);
							break;
						}
					}
				}
				break;
		}
		return $this;
	}

	/**
	 * Pre-process simple product html before selecting the updates.
	 * See inline coments for specifics.
	 *
	 * @param string $html
	 * @param Mage_Catalog_Model_Product $simpleProduct
	 * @param Mage_Catalog_Model_Product $configurableProduct
	 * @return string
	 */
	protected function _prepareSimpleProductHtml($html, $simpleProduct, $configurableProduct)
	{
		/*
		 * Replace the price box product for the simple product html with the
		 * configurable products id so that the javascript optionprice selectors work
		 */
		$html = $this->_updatePriceBoxProductId($html, $simpleProduct, $configurableProduct);

		/*
		 * Replace the simple products id with the configurable products id for
		 * the product tagging form
		 */
		//$prefix = '/tag/index/save/product/';
		//$html = str_replace($prefix . $simpleProduct->getId(), $prefix . $configurableProduct->getId(), $html);

		/*
		 * Replace all uenc params to the original configurable product page, so
		 * the referer redirect for tagging works
		 */
		if (
			Mage::app()->getRequest()->getActionName() === 'update' &&
			$referer = Mage::app()->getRequest()->getServer('HTTP_REFERER')
		)
		{
			/*
			 * Add the backlink to the selected simple product
			 */
			$varName = DerModPro_BCP_Model_Observer::SELECTION_VAR_NAME;
			$regex = '#(\?.*' . $varName . '=)\d+#';
			$count = 0;
			$referer = preg_replace($regex, '${1}' . $simpleProduct->getId(), $referer, $limit = -1, $count);
			if ($count == 0)
			{
				$referer .= strpos($referer, '?') === false ? '?' : '&';
				$referer .= $varName . '=' . $simpleProduct->getId();
			}

			$uenc = Mage::helper('core/url')->getEncodedUrl($referer);
			$html = preg_replace('#/uenc/[^/"]+#', '/uenc/' . $uenc, $html);
		}

		/*
		 * Make opConfig global scope by removing the 'var' in front of it.
		 * This is needed so the cpo price updates work in the case that the configurable
		 * product has no cpo, but the simple product has.
		 */
		$html = str_replace('var opConfig', 'opConfig', $html);

		return $html;
	}

	/**
	 * Replace the price box product for the simple product html with the
	 * configurable products id so that the javascript optionprice selectors work
	 *
	 * @param string $html
	 * @return string
	 */
	protected function _updatePriceBoxProductId($html, $simpleProduct, $configurableProduct)
	{
		if ($this->_updateDocumentPart('price', $configurableProduct))
		{
			foreach ($this->_getPriceBoxPartSelectors() as $priceSelector)
			{
				$search = $priceSelector . $simpleProduct->getId();
				$replace = $priceSelector . $configurableProduct->getId();
				$html = preg_replace("/{$search}/", $replace, $html);
			}
		}
		return $html;
	}

	protected function _getPriceBoxPartSelectors()
	{
		$selectors = array();
		foreach ((array)Mage::getConfig()->getNode('global/bcp/price_selector_list') as $node)
		{
			$selectors[] = (string) $node;
		}
		return $selectors;
	}
}