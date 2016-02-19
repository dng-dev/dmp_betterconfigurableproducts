.. footer::
   .. class:: footertable
   
   +-------------------------+-------------------------+
   | Stand: 15.04.2013       | .. class:: rightalign   |
   |                         |                         |
   |                         | ###Page###/###Total###  |
   +-------------------------+-------------------------+

.. sectnum::

==================================
BCP - Better Configurable Products
==================================

ChangeLog
=========

.. list-table::
   :header-rows: 1
   :widths: 1 1 6

   * - **Revision**
     - **Datum**
     - **Beschreibung**
   
   
   * - 13.04.15
     - 15.04.2013
     - Bugfixes:

       * editing an order in the backend results in wrong price
       * fatal error on backend reorder when assoziated simple products have custom price options
       * images for "more views" were not show immediately for default simple products
       * Wrong Index-Type "int(11)" for new attribute bcp_default_product_sku

   * - 13.02.07
     - 07.02.2013
     - Bugfixes:

       * no pre-select of option when editing cart item
       * wrong cheapest simple with expired special prices

       Other:

       * updatet documentation about BCP Tools section

   * - 12.12.03
     - 11.12.2012
     - Feature:
     
       * Add "Price From:" to price box if "use simple as default" is selected and a simple product has another price
         then the configurable product
       * Changed attribute "Default Simple Product" from id-based to sku-based. IMPORTANT: 
         Run System->Tools->Better Configurable Products->Standard Simple Product -> "Migrate cheapest Products" after the update.
       * Allow out of stock products
       * Show information block in configuration area
      
       Bugfixes:
     
       * Wrong product prices with custom options in CE 1.7

   * - 12.10.12
     - 12.10.2012
     - Feature:
     
       * add option to disable showing of price in dropdown's completly
     
   
   * - 12.10.12
     - 12.10.2012
     - Bugfixes:

	  * Fix configurable options while caching enable
	  * Fix displayed price value if there is a priced custom option on a simple product

  
   * - 12.10.12
     - 12.10.2012
     - Changes:
	   
	  * add LICENSE.txt and updated License-Header
	  * changed versions number to new convention DD.MM.YY

   * - 0.5.3
     - 23.08.2012
     - Bugfixes:
     
       * Allow 0.000 price value at the 'Associated Products' tab, section 'Super product attributes configuration'
       * Update the original price on the product page when special prices of child products do not differ
       * Fix price update on custom options selection in CE 1.7
       
       Known Issues:
     
       * Fix BCP caching when enabled via BCP advanced settings

   * - 0.5.2
     - 13.06.2012
     - Bugfixes:

       * Fixed FPC-Problem for EE with Tiered Prices
