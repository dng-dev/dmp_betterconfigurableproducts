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

=== ABOUT ===
This extension enables you to update the configurable product display depending
on the selected options for the simple products.


=== FEATURES ===
- Display the images of the selected simple product
- Display the attributes of the selected simple product
- Display the description of the selected simple product
- Display the total price of the selected options in the option drop-downs
- The price of the selected simple product is used instead of the options price
  updates
- Optional display of an ajax spinner during the update
- Lots of options to fine-tune the extension to your needs
- Supports custom product options on the configurable products AND on simple products
- No template modifications needed (as long as your template is valid xhtml)

Its easy - just create the simple products you want to sell within the
configurable product and thats it.


=== INSTALLATION ===
Please unpack the BCP ZIP archives in the magento directory. Then clear the magento cache, and rebuild
rebuild the flat catalog tables.


=== USAGE ===
Configure the extension as needed (see below for more information on the options) and then theme the template as needed.
Even if you only display configurable products in your store you also need to adjust the simple product template,
otherwise the view updates will display the wrong template.


=== CONFIGURATION ===
You can find the configuration under System > Configuration > Better Configurable Product > Settings

All BCP Configuration options:

- Select the original theme your theme is based on
  The BCP extension uses Xpath and CSS selectors to fetch the sections to update. That is why you need to
  configure the theme your websites look and feel is based on. If you do not know which theme is the right one,
  just select one after the other and check the results in the front-end for a setting that works.
  If your theme is very different from the available options, you may need to specify the selector yourself
  in the Advanced Settings section.

- Update the display of the products media section
  If you choose "No" the media section of the configurable product will be visible regardless of the option selection.
  Otherwise the image and the media gallery of the selected simple product will display.

- Only update the main image
  Set this option to "Yes" if you only want to update the main product image, and keep the media gallery of the
  configurable product.

- Update the display of the products short description
  Set to "Yes" to update the short description to reflect the currently selected simple product.
  
- Update the display of the products collateral data
  The products collateral information includes the long description and the additional attributes. Set the option
  to yes if you want to show the selected simple products data.

- Keep the long description of the configurable product
  Set to "Yes" if you want to keep the long description of the configurable product and only update the rest of the
  collateral information.

- Update the price block
  Show the price information of the selected simple product, including tier price information if available.

- Preload simple product images
  If set to yes the main images of all simple products are preloaded. This gives a cleaner transition when a
  simple product is selected. The option has no effect if the image section updates are disabled.

- Show spinner while ajax update is in progress
  You may display a spinner image while the ajax update is in progress. You may customize it using the
  template dermodpro/bcp/catalog/product/view/type/configurable.phtml and the image dermodpro/bcp/images/spinner.gif
  in your skin directory (as usual, copy the original templates into your own theme before modifying them so your
  changes will not be affected by extension upgrades).

- Use the cheapest child product as the default product
  If you set this option to "Yes" the cheapest simple product will be selected from the start and displayed
  as the default when the product page is viewed.

- Option Label Price Prefix
  Prefix for the price in the option drop-down.
  
- Option Label Price Suffix
  Suffix for the price in the option drop-down.

- Show the option price label if it is the same as the currently displayed product price
  Set the configuration option to "Yes" if you want to display the price on product options with the same price as
  the current selection.

- Unknown Price Option Label
  Label to append to the option if further options have to be selected before the price for the variant is known.

If you want the image of the selected configurable product to be displayed in the
shopping cart, you can find the setting at System > Configuration > Checkout > Shopping Cart
(this setting is part of the Magento core, not part of the BCP extension).


=== ADVANCED CONFIGURATION SETTINGS ===
If you develop more advanced and customized themes and the default BCP theme selector isn't working anymore,
you can specify the DOM and CSS selector for the display page sections that should be updated.
For some advanced setting samples, please refer to the section below "SAMPLE ADVANCED SETTINGS" or have a look
in the file app/code/community/DerModPro/BCP/etc/config.xml in the <default> configuration node at the bottom.

You can also specify a javascript callback to re-initialize the media section zoom.
Any entries in the "Advanced" settings override the preconfigured settings from
the theme selection drop-down.

Most settings can be overridden on a per-product basis within the "Design" tab of
the configurable product. Additionally you can select an associated default simple
product to display instead of the configurable product when the page is loaded.


=== SAMPLE ADVANCED SETTING ===
If you install the jquery image zoom extension from satrun77 found at
http://www.magentocommerce.com/extension/1492/magento-jqzoom, you need to specify
the javascript callbacks for it to work. In the Field

"Media Section JavaScript callback to prepare update"
enter this:

	jQuery('.jqzoom')=null;

In the Field "Media Section JavaScript callback to reinitialize image functionality"
enter this:

	jQuery('.jqzoom').jqueryzoom({ xzoom:300, yzoom:300, anim_scale:0 });

You need to adjust the width, height and scale settings so they
match your theme. This is just an example.


=== CACHING ===
In the advanced settings you can specify the response cache time (in seconds) and the page cache lifetime.
Server side ajax response caching is enabled if a value larger then zero is specified, and HTML Block caching
is enabled, too (in the magento cache management interface).
Client side ajax response caching (in javascript) will also be disabled if you set the configuration value to 0,
regardless of the Magento HTML Block caching setting (you may find this useful during development).
The page cache lifetime does not affect client side caching, only the page parts
specific to configurable products are cached server side to speed up the site. The biggest
performance gain is for configurable products made up out of lots of simple products.

=== Uninstall ===

To uninstall the module remove all BCP-files:

- app/code/community/DerModPro/BCP
- app/etc/modules/DerModPro_BCP.xml
- app/design/adminhtml/default/default/template/bcp
- app/design/frontend/base/default/layout/bcp.xml
- app/design/frontend/default/default/layout/bcp.xml
- app/design/frontend/base/default/template/dermodpro/bcp
- app/design/frontend/default/default/template/dermodpro/bcp
- skin/frontend/base/default/dermodpro/bcp
- skin/frontend/default/default/dermodpro/bcp
- js/dermodpro/bcp
- app/locale/de_DE/DerModPro_BCP.csv

and execute the following SQL-queries:

DELETE FROM `eav_attribute` WHERE `attribute_code`='bcp_update_images' LIMIT 1;
DELETE FROM `eav_attribute` WHERE `attribute_code`='bcp_update_short_desc' LIMIT 1;
DELETE FROM `eav_attribute` WHERE `attribute_code`='bcp_update_collateral' LIMIT 1;
DELETE FROM `eav_attribute` WHERE `attribute_code`='bcp_default_product' LIMIT 1;
DELETE FROM `eav_attribute` WHERE `attribute_code`='bcp_default_override' LIMIT 1;

Rebuild all indexes and clear all caches after.

=== SUPPORT ===
Deutsch: http://www.der-modulprogrammierer.de/hilfeseiten/hilfe/bcp-de.html
English: http://www.der-modulprogrammierer.de/hilfeseiten/hilfe/bcp-en.html


=== BUGS ===
If you have ideas for improvements or find bugs, please send them to info@der-modulprogrammierer.de,
with DerModPro_BCP as part of the subject.

If you receive the error "Warning: domdocument::domdocument() expects at least 1
parameter, 0 given...". This is related to an old version of the DomXML extension
being loaded from the php.ini. You need "php_dom.dll" and NOT "php_domxml.dll"
(see http://bonsai.php.net/bug.php?id=32743&edit=1)
