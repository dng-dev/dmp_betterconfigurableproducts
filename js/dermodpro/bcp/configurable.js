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

if(typeof BCP == 'undefined') {
    var BCP = {};
}

BCP.Config = Class.create(Product.Config, {
    fillSelect: function(element) {
        var attributeId = element.id.replace(/[a-z]*/, '');
        var options = this.getAttributeOptions(attributeId);
        this.clearSelect(element);
        element.options[0] = new Option(this.config.chooseText, '');

        /*
         * Update allowed products for each option
         */
        var allowedProducts = [];
        var prevOption = false;
        var product;
        var index = 1;
        if (element.prevSetting && element.prevSetting.selectedIndex > 0) {
            prevOption = element.prevSetting.options[element.prevSetting.selectedIndex].config;
        }
        for (var i = 0; i < options.length; i++) {
            if (! prevOption) allowedProducts = options[i].products.clone();
            else {
                allowedProducts = [];
                for (var j = 0; j < options[i].products.length; j++) {
                    product = options[i].products[j];
                    if (prevOption.allowedProducts.indexOf(product) != -1) allowedProducts.push(product);
                }
            }
            options[i].allowedProducts = allowedProducts.clone();
            if (allowedProducts.length > 0) {
                element.options[index] = new Option(this.getOptionLabel(options[i]), options[i].id);
                element.options[index].config = options[i];
                //Check if option has to be disabled because of out of stock reasons
                element.options[index].disabled = this.checkForOutOfStockDisable(options[i].products)
                index++;
            }
        }
        if (element.nextSetting) {
            this.disableAllChildren(element.nextSetting);
        }
    },
    reloadAllOptionLabels: function() {
        this.settings.each(function(element) {
            this.reloadOptionLabels(element);
        }.bind(this));
    },
    reloadOptionLabels: function(element) {
        for (var i = 0; i < element.options.length; i++) {
            if (element.options[i].config) {
                //Check if option has to be disabled because of out of stock reasons
                element.options[i].disabled = this.checkForOutOfStockDisable(element.options[i].config.products)
                element.options[i].text = this.getOptionLabel(element.options[i].config);
            }
        }
    },
    configureElement: function($super, element) {
        if (! this.config.bcp) return;
        
        if (element.value) {
            this.state[element.config.id] = element.value;
            element.nextSetting.disabled = false;
            if (element.nextSetting) {
                this.fillSelect(element.nextSetting);
            }
        } else {
            this.resetChildren(element);
        }

        /*
         * Set the product id currently being processed.
         * This can also be used by external scripts (e.g. basepricepro)
         */
        if (element.selectedIndex > 0 && element.options[element.selectedIndex].config.allowedProducts.length == 1) {
            this.setBcpProcessingProductId(element.options[element.selectedIndex].config.allowedProducts[0]);
        } else {
            this.setBcpProcessingProductId(this.getBcpCurrentProduct());
        }

        /*
         * Set the currently selected price
         */
        if (element.selectedIndex > 0) {
            var price = this.getOptionPrice(element.options[element.selectedIndex].config);
            var oldPrice = price;
            if (element.options[element.selectedIndex].config.allowedProducts.length == 1) {
                oldPrice = this.config.bcp.spOldPrices[this.getBcpProcessingProductId()];
            } else if (element.options[element.selectedIndex].config.allowedProducts.length) {
                oldPrice = this.config.bcp.spOldPrices[element.options[element.selectedIndex].config.allowedProducts[0]];
            }
            this.updatePrice(price, oldPrice);
        }

        /*
         * If a specific product is selected, update the product view
         */
        if (element.selectedIndex > 0) {
            if (element.options[element.selectedIndex].config.allowedProducts.length == 1) {
                this.updateProductView(this.getBcpProcessingProductId());
            }
        }
    },
    disableAllChildren: function(element) {
        element.selectedIndex = 0;
        element.disabled = true;
        if (element.nextSetting) {
            this.disableAllChildren(element.nextSetting);
        }
    },
    updatePrice: function(price, oldPrice) {
        if (price === false) return;
        
        this.setBcpCurrentPrice(price);
        this.reloadAllOptionLabels();
        
        /*
         * Reset custom prices, otherwise additional custom price accumulate after every option selection
         * For all versions >= CE 1.7
         */
        if (typeof optionsPrice.customPrices != 'undefined') {
            optionsPrice.customPrices = {};
        }
        
        /*
         * Use direct property access here so we don't need to rewrite the Product.OptionsPrice class
         */
        optionsPrice.productPrice = price;
        optionsPrice.productOldPrice = oldPrice;
        optionsPrice.reload();
    },
    updateProductView: function(productId) {
        if (this.getBcpCurrentProduct() == productId) {
            return;
        }
        this.setBcpCurrentProduct(productId);

        if (this.disableAjax) return;

        if (
            this.config.bcp.updateSections.price ||
            this.config.bcp.updateSections.media ||
            this.config.bcp.updateSections.shortDesc ||
            this.config.bcp.updateSections.collateral ||
            this.config.bcp.updateSections.cpo
            ) {
            var cacheData = this.getCache(productId);
            if (cacheData) {
                this.processResponse(cacheData);
                return;
            }

            /*
             * Update price for custom product options while we wait for ajax response
             */
            if (typeof opConfig != 'undefined') {
                opConfig.reloadPrice();
            }
            
            this.showSpinner();

            var request = new Ajax.Request(
                this.config.bcp.updateProductUrl,
                {
                    method: 'post',
                    onFailure: this.ajaxFailure.bind(this),
                    onSuccess: this.ajaxSuccess.bind(this),
                    parameters: {
                        id: productId,
                        parent_id: this.config.bcp.cpId
                    }
                }
                );
        }
    },
    ajaxFailure: function() {
        this.hideSpinner();
        console.log('dmp/bcp Error fetching product update!');
    },
    ajaxSuccess: function(transport) {
        this.hideSpinner();
        var response;
        if (transport && transport.responseText){
            try {
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }
        this.saveCache(response);
        this.processResponse(response);
    },
    processResponse: function(response) {
        if (response.error){
            if ((typeof response.message) == 'string') {
                alert(response.message);
            } else {
                alert(response.message.join("\n"));
            }
            return false;
        }

        if (response.updates) {
            for (var i = 0; i < response.updates.length; i++) {
                if (response.updates[i].dom_selector && response.updates[i].html) {
                    var elements = $$(response.updates[i].dom_selector);
                    if (response.updates[i].prepare) try {
                        eval(response.updates[i].prepare);
                    } catch (e) {}
                    if (elements) elements.each(function(element) {
                        /*
                         * Only update the first match
                         */
                        Element.replace(element, response.updates[i].html);
                        throw $break;
                    }.bind(this));
                    if (response.updates[i].callback) try {
                        eval(response.updates[i].callback);
                    } catch (e) { /*console.log(e)*/}
                }
            }
            this.clonePriceSection();
            
            /*
             * First reload the price display for the chosen simple product, then
             * reload the custom product options price adjustments.
             */
            if (typeof opConfig != 'undefined') {
                opConfig.reloadPrice();
            }
            else optionsPrice.reload();
        }
        ProductMediaManager.wireThumbnails();
    },
    getCache: function(productId) {
        if (! this.config.bcp.useCache) return false;
        if (typeof this.config.bcp.cacheData[productId] == 'undefined') return false;
        return this.config.bcp.cacheData[productId];
    },
    saveCache: function(response) {
        if (typeof response.product_id != 'undefined' && this.config.bcp.useCache) {
            this.config.bcp.cacheData[response.product_id] = response;
        }
    },
    clonePriceSection: function() {
        /*
         * Selektor to match the first two priceboxes in the content area
         */
        var selektor = this.config.bcp.priceCloneSelektor ? this.config.bcp.priceCloneSelektor : '.main .price-box';
        var boxes = $$(selektor);
        if (boxes && boxes.length > 1) {
            var html = boxes[0].innerHTML.replace(/(-price-\d+)"/g, '$1_clone"');
            boxes[1].update(html);
        }
    },
    showSpinner: function() {
        if ($('bcp-spinner') && this.config.bcp.showSpinner) $('bcp-spinner').show();
    },
    hideSpinner: function() {
        if ($('bcp-spinner') && this.config.bcp.showSpinner) $('bcp-spinner').hide();
    },
    getOptionLabel: function(option) {
        var tax;
        var excl;
        var incl;
        var price = this.getOptionPrice(option);

        if (price === false) {
            // no single price selected
            str = option.label;
            if (this.config.bcp && this.config.bcp.unknownPriceLabel) {
                str += ' ' + this.config.bcp.unknownPriceLabel;
            }
            return str;
        }
        
        //Set out of stock suffix in case that the product is disabled
        var outOfStockSufix = '';
        if (true === this.checkForOutOfStockDisable(option.products)
            && this.config.bcp) {
            outOfStockSufix = ' ' + this.config.bcp.format.outOfStockSufix;
        }
        
        //Get current label
        var str = option.label;
        
        //If option prices should not be shown
        if (this.config.bcp && this.config.bcp.showOptionPrice == false) {
            return str + outOfStockSufix;
        } 
        
        // respect config option: hide option prices if they are the same as the currently visible product price
        if (this.config.bcp &&
            this.config.bcp.showOptionPriceIfSame == 0 &&
            price == this.getBcpCurrentPrice()) {
            return option.label + outOfStockSufix;
        }
        
        //Start to calculate the incl/excl product price of the corresponding option
        price = parseFloat(price);
        
        if (this.taxConfig.includeTax) {
            tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            excl = price - tax;
            incl = excl * (1+(this.taxConfig.currentTax/100));
        } else {
            tax = price * (this.taxConfig.currentTax / 100);
            excl = price;
            incl = excl + tax;
        }
        
        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }
        
        var prefix = this.config.bcp ? this.config.bcp.format.price.prefix : ' ';
        var sufix = this.config.bcp ? this.config.bcp.format.price.sufix : ' ';
        
        //Build the option price label
        if (this.taxConfig.showBothPrices) {
            str += ' ' + prefix + this.formatPrice(excl, true) + ' (' + this.formatPrice(price, true) + ' ' + this.taxConfig.inclTaxTitle + ')' + sufix;
        } else {
            str += ' ' + prefix + this.formatPrice(price, true) + sufix;
        }
        
        return str + outOfStockSufix;
    },
    getOptionPrice: function(option) {
        var prices = this.getOptionPrices(option);
        if (prices.length == 1) return prices[0];
        return false;
    },
    getOptionPrices: function(option) {
        var products = [];
        var prices = [];
        var price;
        if (option.products && option.products.length == 1) products = option.products;
        else if (option.allowedProducts && option.allowedProducts) products = option.allowedProducts;
        else if (option.products) products = option.products;
        if (products.length) {
            for (var i = 0; i < products.length; i++) {
                price = this.getBcpProductPrice(products[i]);
                if (prices.indexOf(price)==-1) prices.push(price);
            }
        }
        return prices;
    },
    formatPrice: function($super, price, showSign) {
        price = $super(price, false); // force price to be absolute (no - or +)
        return price;
    },
    reloadPrice: function($super) {
        // price updates are handled differently with bcp
        return;
    },
    setBcpCurrentPrice: function(price) {
        this.config.bcp.currentPrice = price;
    },
    getBcpCurrentPrice: function() {
        var price = false;
        if (this.config.bcp) {
            if (this.config.bcp.currentPrice) price = this.config.bcp.currentPrice;
            else price = this.config.bcp.cpPrice;
        }
        return price;
    },
    getBcpProductPrice: function(productId) {
        if (this.config.bcp && this.config.bcp.spPrices[productId]) {
            return this.config.bcp.spPrices[productId]
        }
        return 0;
    },
    getBcpCurrentProduct: function() {
        if (this.config.bcp && this.config.bcp.currentProduct) {
            return this.config.bcp.currentProduct;
        }
        return 0;
    },
    setBcpCurrentProduct: function(productId) {
        this.config.bcp.currentProduct = productId;
        this.updateCustomProductOptions(productId);
    },
    updateCustomProductOptions: function(productId) {

        /*
         * Refresh custom product options config in case price updates with a percentage of the product price are configured
         */
        if (typeof opConfig != 'undefined' && typeof this.config.bcp.optionPrices[productId] != 'undefined') {

            /*
             * Update the custom product option labels price for the configurable product.
             * This is needed because if they the price updates specified as percentages of the
             * product price, they depend on the selected simple product.
             */
            if ($('product_addtocart_form')) {
                
                var select = '';
                var newPrice = 0;
                var oldPrice = 0;
                var options = [];
                var labels = [];
                var optionId = 0;
                var valueId = 0;

                $H(this.config.bcp.optionPrices[productId]).each(function (optionPair) {
                    optionId = optionPair.key;
                    $H(optionPair.value).each(function (valuePair) {
                        valueId = valuePair.key;
                        newPrice = this.formatPrice(valuePair.value.price);
                        if (typeof opConfig.config[optionId] != 'undefined') {
                            oldPrice = this.formatPrice(opConfig.config[optionId][valueId]);
                        } else {
                            oldPrice = newPrice;
                        }

                        if (oldPrice != newPrice) {
                            // Select Options
                            select = 'select[name="options[' + optionId + ']"] option[value="' + valueId + '"]';
                            options = $('product_addtocart_form').select(select);
                            if (options && options.length) {
                                options[0].innerHTML = options[0].innerHTML.replace(oldPrice, newPrice);
                            };

                            // Multiselect Options
                            select = 'select[name="options[' + optionId + '][]"] option[value="' + valueId + '"]';
                            options = $('product_addtocart_form').select(select);
                            if (options && options.length) {
                                options[0].innerHTML = options[0].innerHTML.replace(oldPrice, newPrice);
                            };

                            // Radio Options
                            select = 'input.product-custom-option[type="radio"][name="options[' + optionId + ']"][value="' + valueId + '"]';
                            options = $('product_addtocart_form').select(select);
                            if (options && options.length) {
                                select = 'label[for="' + options[0].id + '"]';
                                labels = $('product_addtocart_form').select(select);
                                if (labels && labels.length) {
                                    labels[0].innerHTML = labels[0].innerHTML.replace(oldPrice, newPrice);
                                }
                            };

                            // Checkbox Options
                            select = 'input.product-custom-option[type="checkbox"][name="options[' + optionId + '][]"][value="' + valueId + '"]';
                            options = $('product_addtocart_form').select(select);
                            if (options && options.length) {
                                select = 'label[for="' + options[0].id + '"]';
                                labels = $('product_addtocart_form').select(select);
                                if (labels && labels.length) {
                                    labels[0].innerHTML = labels[0].innerHTML.replace(oldPrice, newPrice);
                                }
                            };
                        }
                    }.bind(this));
                }.bind(this));
            }
            /*
             * Use direct property access here so we don't need to rewrite the Product.Options class,
             */
            opConfig.config = this.config.bcp.optionPrices[productId];
        }
    },
    setBcpProcessingProductId: function(productId) {
        this.config.bcp.processingProduct = productId;
    },
    getBcpProcessingProductId: function() {
        if (this.config.bcp && this.config.bcp.processingProduct) {
            return this.config.bcp.processingProduct;
        }
        return 0;
    },
    addBcpConfig: function(config) {
        this.config.bcp = config;
        this.config.bcp.cacheData = [];
        if (this.config.bcp.spDefault) {
            this.setSimpleProduct(this.config.bcp.spDefault);
        }
        this.reloadAllOptionLabels();
    },
    setSimpleProduct: function(productId) {
        productId = '' + productId;
        this.disableAjax = true;
        this.settings.each(function(element) {
            var attributeId = element.attributeId;
            var options = this.getAttributeOptions(attributeId);
            for (var i = 0; i < options.length; i++) {
                if (options[i].products.indexOf(productId) > -1) {
                    element.value = options[i].id;
                    this.configureElement(element);
                    return;
                }
            }
        }.bind(this));
        this.disableAjax = false;
    },
    checkForOutOfStockDisable: function(productId) {
        if (typeof productId != 'undefined'
            && typeof this.config.bcp != 'undefined'
            && typeof this.config.bcp.spIsSaleable[productId] != 'undefined'
            && this.config.bcp.showOutOfStockOptions == 0
            && this.config.bcp.spIsSaleable[productId] == 0) { //spIsSaleable is => (int) $product->isSaleable()
            return true;
        } else {
            return false;
        }
    }
});

/*
 * Overwrite the original Product.Config class
 */
Product.Config = BCP.Config;