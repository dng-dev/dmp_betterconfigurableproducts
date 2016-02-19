#!/usr/bin/php
<?php

// see Packr
require_once dirname(__FILE__) . ProductCreator::MAGEDIR . '/app/Mage.php';

class ProductCreator
{
    const MAGEDIR = '/../..';

    /**
     * Simple Product #1
     * @var Mage_Catalog_Model_Product
     */
    protected $_spOne;

    /**
     * Simple Product #2
     * @var Mage_Catalog_Model_Product
     */
    protected $_spTwo;

    /**
     * Configurable Product
     * @var Mage_Catalog_Model_Product
     */
    protected $_cp;

    /**
     * Directory where images are imported from
     * @var string
     */
    protected $_imgDir;
    
    /**
     * The ID of the category (/apparel/men). Must be detected during runtime.
     * @var int
     */
    protected $_catId = null;

    /**
     * The ID of the attribute (Shirt Size). Must be detected during runtime.
     * @var int
     */
    protected $_attributeId = null;

    /**
     * The ID of the attribute set (Shirts T). Must be detected during runtime.
     * @var int
     */
    protected $_attributeSetId = null;
    
    protected $_attributeGroupId = null;
    
    /**
     * The IDs of the attribute values. Must be detected during runtime.
     * @var array
     */
    protected $_attributeValueIds = array(
        'Small' => null,
        'Medium' => null,
        'Large' => null
    );
    
    protected $_imgSizeAttributes = array(
        'thumbnail', 'small_image', 'image'
    );

    
    public function __construct(Mage_Catalog_Model_Product $sp_one,
        Mage_Catalog_Model_Product $sp_two, Mage_Catalog_Model_Product $cp)
    {
        $this->_spOne = $sp_one;
        $this->_spOne->setTypeId('simple');

        $this->_spTwo = $sp_two;
        $this->_spTwo->setTypeId('simple');

        $this->_cp = $cp;
        $this->_cp->setTypeId('configurable');
        
        $this->_imgDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
    }
    
    
    /**
     * Magento uses a cache directory to display frontend images.
     * The parent directory has to be writable.
     * 
     * @param string $catalog_dir
     */
    public function prepareCatalogDirectory($catalog_dir)
    {
        $success = chmod($catalog_dir, 0777);
        print("changing mode of '$catalog_dir': " . json_encode($success) . "\n");
    }

    
    /**
     * Rebuild indexes, so that programmatically created attributes are known
     * to the products.
     * 
     * @param Mage_Index_Model_Process $process
     */
    public function rebuildCatalog(Mage_Index_Model_Process $process)
    {
        // 4, 7
        $processIds = $process->getResourceCollection()->getAllIds();
        foreach ($processIds as $processId) {
            $process->load($processId)->reindexEverything();
        }
    }
    
    
    /**
     * Create attribtutes, attribute set and attribute group that are used to
     * distinguish between the simple and configurable products.
     * 
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param Mage_Eav_Model_Entity_Attribute_Set $attributeSet
     * @param Mage_Eav_Model_Entity_Attribute_Group $attributeGroup
     */
    public function prepareDependencies(Mage_Catalog_Model_Resource_Eav_Attribute $attribute,
        Mage_Eav_Model_Entity_Attribute_Set $attributeSet,
        Mage_Eav_Model_Entity_Attribute_Group $attributeGroup)
    {
        $entity_type_product = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

        
        // ---------------------- CREATE ATTRIBUTE SET ---------------------- //
        $attribute_set_name = 'Shirts T';
        
        // check if an attribute set with given name already exists
        $attributeSet->load($attribute_set_name, 'attribute_set_name');
        if (!$attributeSet->getId() || ($attributeSet->getEntityType() != $entity_type_product)) {
            // does not exist yet: create attribute set
            $attributeSet->setEntityTypeId($entity_type_product);
            $attributeSet->setAttributeSetName($attribute_set_name);
            $attributeSet->save();
            // TODO: depict DEFAULT attribute set id
            $attributeSet->initFromSkeleton(4)->save();
            print("Attribute set '$attribute_set_name' created (ID: " . $attributeSet->getId() . ").\n");
        }
        $this->_attributeSetId = $attributeSet->getId();
        
        
        // --------------------- CREATE ATTRIBUTE GROUP --------------------- //
        $attribute_group_name = 'T-shirts Attributes';
        
        // check if an attribute group with given name already exists
        $attributeGroup->load($attribute_group_name, 'attribute_group_name');
        if (!$attributeGroup->getId()) {
            // does not exist yet: create group
            $attributeGroup->setAttributeGroupName($attribute_group_name);
            $attributeGroup->setAttributeSetId($this->_attributeSetId);
            $attributeGroup->save();
            print("Attribute group '$attribute_group_name' created (ID: " . $attributeGroup->getId() . ").\n");
        }
        $this->_attributeGroupId = $attributeGroup->getId();
        
        
        // ----------------- CREATE ATTRIBUTE 'shirt_size' ------------------ //
        $attribute_code = 'shirt_size';
        
        // check if an attribute with given code already exists
        $attribute->loadByCode($entity_type_product, $attribute_code);
        if (!$attribute->getId()) {
            // does not exist yet: create attribute
            $attribute->setData(array(
                'apply_to' => array('simple', 'grouped', 'configurable'),
                'attribute_code' => $attribute_code,
                'default_value_yesno' => 0,
                'frontend_input' => 'select',
                'frontend_label' => array('Shirt Size'),
            	'is_comparable' => 0,
                'is_configurable' => 1,
                'is_filterable' => 0,
                'is_filterable_in_search' => 0,
                'is_global' => 1,
                'is_html_allowed_on_front' => 0,
                'is_required' => 1,
                'is_searchable' =>  1,
                'is_unique' => 0,
                'is_used_for_promo_rules' =>  1,
                'is_visible_in_advanced_search' => 0,
                'is_visible_on_front' => 0,
                'option' => array(
                    'order' => array(
                        'option_2' => 1,
                        'option_1' => 2,
                        'option_0' => 3
                    ),
                    'value' => array(
                        'option_2' => array('Small'),
                        'option_1' => array('Medium'),
                        'option_0' => array('Large')
                    )
                ),
                'used_for_sort_by' => 0,
                'used_in_product_listing' => 0
            ));
            $attribute->setAttributeGroupId($this->_attributeGroupId);
            $attribute->setAttributeSetId($this->_attributeSetId);
            $attribute->setEntityTypeId($entity_type_product);
            $attribute->setIsUserDefined(1);
            $attribute->setBackendType('int');
            
            $attribute->save();
            print("Attribute '$attribute_code' created (ID: " . $attribute->getId() . ").\n");
        }
        $this->_attributeId = $attribute->getId();
        
        // store option value IDs (Small, Medium, Large)
        $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($this->_attributeId)
            ->setPositionOrder('desc', true)
            ->load();

        foreach ($optionCollection as $item) {
            $this->_attributeValueIds[$item->getValue()] = $item->getId();
        }
        
        
        // -------------------- CREATE ATTRIBUTE 'model' -------------------- //
        $attribute = Mage::getModel('catalog/resource_eav_attribute');
        $attribute_code = 'model';
        
        // check if an attribute with given code already exists
        $attribute->loadByCode($entity_type_product, $attribute_code);
        if (!$attribute->getId()) {
            // does not exist yet: create attribute
            $attribute->setData(array(
            	'apply_to' => array('simple', 'grouped', 'configurable'),
                'attribute_code' => $attribute_code,
                'default_value_yesno' => 0,
                'frontend_input' => 'text',
                'frontend_label' => array('Model'),
            	'is_comparable' => 1,
                'is_configurable' => 0,
                'is_global' => 1,
                'is_html_allowed_on_front' => 0,
                'is_required' => 1,
                'is_searchable' => 1,
                'is_unique' => 0,
                'is_used_for_promo_rules' => 1,
                'is_visible_in_advanced_search' => 0,
                'is_visible_on_front' => 1,
                'used_for_sort_by' => 0,
                'used_in_product_listing' => 0
            ));
            $attribute->setAttributeGroupId($this->_attributeGroupId);
            $attribute->setAttributeSetId($this->_attributeSetId);
            $attribute->setEntityTypeId($entity_type_product);
            $attribute->setIsUserDefined(1);
            $attribute->setBackendType('varchar');
            
            $attribute->save();
            print("Attribute '$attribute_code' created (ID: " . $attribute->getId() . ").\n");
        }
    }
    
    
    /**
     * Set the category ID the products should be attached to
     * @param integer $cat_id
     * @throws InvalidArgumentException
     * @return ProductCreator
     */
    public function setProductCategory($cat_id)
    {
        if (!is_numeric($cat_id)) {
            throw new InvalidArgumentException("'$cat_id' is not a valid category ID.");
        }
        
        $this->_catId = $cat_id;
        return $this;
    }
    
    
    /**
     * Create a category for the products, if it does not exist yet.
     * Root -> Apparel -> Men
     *  
     * @param Mage_Catalog_Model_Category $root_category
     */
    public function createCategories(Mage_Catalog_Model_Category $root, array $categories)
    {
        $category_name = array_shift($categories);
        $currentChild = null;
        
        $children = $root->getChildrenCategories();
        foreach ($children as $child) {
            /* @var $child Mage_Catalog_Model_Category */
            if ($child->getName() == $category_name) {
                // load category
                $currentChild = $child;
                break;
            }
        }
        
        if (null === $currentChild) {
            // create category
            $currentChild = Mage::getModel('catalog/category');
            $currentChild->setData(array(
                'store_id' => Mage_Core_Model_App::ADMIN_STORE_ID,
                'path' => $root->getPath(),
                'name' => $category_name,
                'is_active' => "1",
            	'include_in_menu' => "1",
                'display_mode' => Mage_Catalog_Model_Category::DM_PRODUCT,
            	'attribute_set_id' => $root->getDefaultAttributeSetId()
            ));
            $currentChild->save();
            print("Category '$category_name' created (ID: " . $currentChild->getId() . ").\n");
        }
        
        if (count($categories)) {
            return $this->createCategories($currentChild, $categories);
        }
        
        return $currentChild->getId();
    }
    
    
    /**
     * @see http://www.magentocommerce.com/boards/v/viewthread/100211/
     * @see http://www.magentocommerce.com/boards/viewthread/46844/
     */
    public function createSimpleProducts()
    {
        // Build the product
        $this->_spOne->setAttributeSetId($this->_attributeSetId);
        
        $this->_spOne->setName('Netresearch Simple Product Alpha');
        $this->_spOne->setModel('606');
        $this->_spOne->setSku('bcp-sp-1');
        $this->_spOne->setStatus(1);
        $this->_spOne->setTaxClassId(2); // Taxable Goods
        $this->_spOne->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE); // Not Visible Individually
        $this->_spOne->setPrice(39.99); // Set some price
        $this->_spOne->setShortDescription('This is the short description for Netresearch Simple Product Alpha.');
        $this->_spOne->setDescription('This is the full description for the Netresearch Simple Product Alpha. An alpha-graded product from company!');
        $this->_spOne->setGender(36); // Men
        $this->_spOne->setWeight(1.2000);
        $this->_spOne->setShirtSize($this->_attributeValueIds['Small']); // Small
        $this->_spOne->setCategoryIds(array($this->_catId)); // apparel, men
        $this->_spOne->setStockData(array(
            'is_in_stock' => 1,
            'qty' => 99999
        ));
        
        $this->_spOne->setWebsiteIDs(array(1)); // Website id, 1 is default
        
        $this->_spOne->addImageToMediaGallery(
            $this->_imgDir . 'logo_nr_alpha_invertiert.png', // absolute path to image
            $this->_imgSizeAttributes, // thumbnail | small_image | image (default=null)
            false, // move destination file (default=false)
            false // disabled in product view?
        );
        
        $this->_spOne->addImageToMediaGallery(
            $this->_imgDir . 'logo_nr_alpha.png', // absolute path to image
          	$this->_imgSizeAttributes, // thumbnail | small_image | image (default=null)
            false, // move destination file (default=false)
            false // disabled in product view?
        );
        
        $this->_spOne->setCreatedAt(strtotime('now'));
        


        // Build the product
        $this->_spTwo->setAttributeSetId($this->_attributeSetId);
        
        $this->_spTwo->setName('Netresearch Simple Product Beta');
        $this->_spTwo->setModel('909');
        $this->_spTwo->setSku('bcp-sp-2');
        $this->_spTwo->setStatus(1);
        $this->_spTwo->setTaxClassId(2); // Taxable Goods
        $this->_spTwo->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE); // Not Visible Individually
        $this->_spTwo->setPrice(29.99); // Set some price
        $this->_spTwo->setShortDescription('This is the short description for Netresearch Simple Product Beta.');
        $this->_spTwo->setDescription('This is the full description for the Netresearch Simple Product Beta. A beta-graded product from company!');
        $this->_spTwo->setGender(36); // Mens
        $this->_spTwo->setWeight(1.2000);
        $this->_spTwo->setShirtSize($this->_attributeValueIds['Medium']); // Medium
        $this->_spTwo->setCategoryIds(array($this->_catId)); // apparel, men
        $this->_spTwo->setStockData(array(
            'is_in_stock' => 1,
            'qty' => 99999
        ));
        $this->_spTwo->setWebsiteIDs(array(1)); // Website id, 1 is default
        
        $this->_spTwo->addImageToMediaGallery(
            $this->_imgDir . 'logo_nr_beta_invertiert.png', // absolute path to image
            $this->_imgSizeAttributes, // thumbnail | small_image | image (default=null)
            false, // move destination file (default=false)
            false // disabled in product view?
        );

        $this->_spTwo->addImageToMediaGallery(
            $this->_imgDir . 'logo_nr_beta.png', // absolute path to image
            $this->_imgSizeAttributes, // thumbnail | small_image | image (default=null)
            false, // move destination file (default=false)
            false // disabled in product view?
        );

        $this->_spTwo->setCreatedAt(strtotime('now'));

        
        
        $this->_spOne->save();
        $this->_spTwo->save();
        
        
        Mage::getModel('catalog/product_flat_indexer')
            ->updateAttribute('shirt_size', null, array($this->_spOne->getId(), $this->_spTwo->getId()))
            ->updateAttribute('model', null, array($this->_spOne->getId(), $this->_spTwo->getId()))
            ->rebuild();
    }

    
    /**
     * @see http://stackoverflow.com/questions/7672391/creating-configurable-product-programmatically
     * @see http://www.magentocommerce.com/boards/viewthread/46844/
     */
    public function createConfigurableProduct()
    {
        $id_sp_one = $this->_spOne->getId();
        $id_sp_two = $this->_spTwo->getId();

        $this->_cp->setAttributeSetId($this->_attributeSetId);

        // product[name]	Netresearch Configurable Product
        $this->_cp->setName('Netresearch Configurable Product');
        // product[model]	303
        $this->_cp->setModel('303');
        // product[sku]	bcp-cp
        $this->_cp->setSku('bcp-cp');
        // product[status]	1
        $this->_cp->setStatus(1); //enabled
        // product[tax_class_id]	2
        $this->_cp->setTaxClassId(2); // Taxable Goods
        // product[visibility]	4
        $this->_cp->setVisibilty(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH); //catalog and search
        // product[price]	109.99
        $this->_cp->setPrice(109.99);
        // product[short_description]	This is the full description for the Netresearch Configurable Product. An alphabeta product from company!
        $this->_cp->setShortDescription('This is the short description for Netresearch Configurable Product.');
        // product[description]	This is the short description for Netresearch Configurable Product.
        $this->_cp->setDescription('This is the full description for the Netresearch Configurable Product. An alphabeta product from company!');
        // product[gender]	36
        $this->_cp->setGender(36); // Mens 
        // product[website_ids][]	1
        $this->_cp->setWebsiteIds(array(1));  // store id
        // category_ids	,37,37
        $this->_cp->setCategoryIds(array($this->_catId)); // apparel, men
        // needs to be set, otherwise product is displayed as out of stock
        $this->_cp->setStockData(array(
            'is_in_stock' => 1,
            'qty' => 0,
            'manage_stock' => 0
        ));
        
        $data = array('0' => array(
        	'id' => NULL,
            'label' => 'Shirt Size',
        	'use_default' => null,
        	'position' => null,
            'values' => array(
            	'0' => array('label' => 'Small', 'attribute_id' => $this->_attributeId, 'value_index' => $this->_attributeValueIds['Small'], 'is_percent' => 0, 'pricing_value' => ''),
                '1' => array('label' => 'Medium', 'attribute_id' => $this->_attributeId, 'value_index' => $this->_attributeValueIds['Medium'], 'is_percent' => 0, 'pricing_value' => ''),
            ),
			'attribute_id' => $this->_attributeId,
            'attribute_code' => 'shirt_size',
            'frontend_label' => 'Shirt Size',
            'store_label' => 'Shirt Size',
        	'html_id' => 'configurable__attribute_0'
        ));
        $this->_cp->setConfigurableAttributesData($data);
        $this->_cp->setCanSaveConfigurableAttributes(1);

        $data = array(
            $id_sp_one => array('0' => array('label'=>'Small', 'attribute_id' => $this->_attributeId, 'value_index' => $this->_attributeValueIds['Small'], 'is_percent' => 0, 'pricing_value' => '')),
            $id_sp_two => array('0' => array('label'=>'Medium', 'attribute_id' => $this->_attributeId, 'value_index' => $this->_attributeValueIds['Medium'], 'is_percent' => 0, 'pricing_value' => ''))
        );
        $this->_cp->setConfigurableProductsData($data);
        
        $this->_cp->addImageToMediaGallery(
            $this->_imgDir . 'logo_nr.png', // absolute path to image
            $this->_imgSizeAttributes, // thumbnail | small_image | image (default=null)
            false, // move destination file (default=false)
            false // disabled in product view?
        );

        $this->_cp->setCreatedAt(strtotime('now'));
        
        
        
        $this->_cp->save();
    }
}


$app = Mage::app();
$app->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

Mage_Core_Model_Resource_Setup::applyAllUpdates();

/* STEP ONE: CHECK IF CUSTOM CONFIGURABLE PRODUCT ALREADY EXISTS */
$configurableProduct = Mage::getModel('catalog/product');
$id = $configurableProduct->getIdBySku('bcp-cp');

if (false !== $id) {
    print("Product with SKU 'bcp-cp' already exists (ID: $id).\n");
} else {
    $prodCreator = new ProductCreator(
        Mage::getModel('catalog/product'), // simple product one
        Mage::getModel('catalog/product'), // simple product two
        Mage::getModel('catalog/product') // configurable product
    );

    try {
        /* STEP TWO: CHECK AND CREATE ATTRIBUTES */
        $prodCreator->prepareDependencies(
            Mage::getModel('catalog/resource_eav_attribute'), // attribute
            Mage::getModel('eav/entity_attribute_set'), // attribute set
            Mage::getModel('eav/entity_attribute_group') // attribute group
        );
        
        /* STEP THREE: CHECK AND CREATE CATEGORY */
        $cat_id = $prodCreator->createCategories(
            Mage::getModel('catalog/category')->load($app->getStore(Mage_Core_Model_App::DISTRO_STORE_ID)->getRootCategoryId()),
            array('Apparel', 'Men')
        );
        $prodCreator->setProductCategory($cat_id);

        // rebuild database, so that products can use the created attributes
        $prodCreator->rebuildCatalog(Mage::getModel('index/process'));

        /* STEP FOUR: FILL AND SAVE SIMPLE PRODUCTS */
        $prodCreator->createSimpleProducts();

    	/* STEP FIVE: FILL AND SAVE CONFIGURABLE PRODUCT */
        $prodCreator->createConfigurableProduct();
    	
    	/* STEP SIX: SET MODE OF MEDIA DIRECTORY */
        $catalog_dir = dirname(__FILE__) . ProductCreator::MAGEDIR . '/media/catalog/product';
        $prodCreator->prepareCatalogDirectory($catalog_dir);
        
        print("Products successfully created.\n");
    } catch (Exception $ex) {
        $msg = "An error occured while creating product:\n";
        $msg.= $ex->getMessage() . ' (' . $ex->getFile() . ' l. ' . $ex->getLine() . ")\n";
        print($msg);
    }
}

?>
