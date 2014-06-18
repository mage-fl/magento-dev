<?php

class TM_SoldTogether_Block_Order extends TM_SoldTogether_Block_Abstract
{
    protected $_cachePrefix = 'TM_SOLD_TOGETHER_ORDER';
    protected $_configGroup = 'order';

    protected function _beforeToHtml()
    {
        if (!Mage::getStoreConfigFlag('soldtogether/general/enabled')
            || !Mage::getStoreConfigFlag('soldtogether/order/enabled')) {

            return parent::_beforeToHtml();
        }

        $product = Mage::registry('product');
        if (!$product) {
            return parent::_beforeToHtml();
        }

        /**
         * @var Mage_Catalog_Model_Resource_Product_Collection
         */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection->setVisibility(
            Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()
        );
        $this->_addProductAttributesAndPrices($collection)
            ->addStoreFilter()
            ->setPageSize($this->getProductsCount())
            ->setCurPage(1);

        $collection->getSelect()
            ->join(
                array('so' => Mage::getResourceModel('soldtogether/order')->getMainTable()),
                'e.entity_id = so.related_product_id',
                array()
            )
            ->where('so.product_id = ?', $product->getId())
            ->order('so.weight DESC');

        if (Mage::getStoreConfig('soldtogether/order/amazonestyle')
            || Mage::getStoreConfig('soldtogether/order/addtocartcheckbox')) {

            $collection->getSelect()
                ->where('e.type_id IN (?)', array('simple', 'virtual'));
        }
        if (!Mage::getStoreConfig('soldtogether/general/out_of_stock')) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }
        if (Mage::getStoreConfig('soldtogether/general/random') && !$collection->count()) {
            $collection = $this->_getRandomProductCollection($product);
        }

        $this->setProductCollection($collection);

        return parent::_beforeToHtml();
    }

    protected function _getRandomProductCollection($product)
    {
        if (!$category = Mage::registry('current_category')) {
            $category = Mage::getModel('catalog/category');
            $category->load($product->getCategoryId());
        }

        $category->setIsAnchor(1); // @see Mage_Catalog_Model_Resource_Product_Collection::addCategoryFilter
        $collection = $category->getProductCollection();
        $collection->setVisibility(
            Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()
        );
        $this->_addProductAttributesAndPrices($collection)
            ->setPageSize($this->getProductsCount())
            ->setCurPage(1)
            ->addAttributeToFilter('entity_id', array('nin' => array($product->getId())));
        if (Mage::getStoreConfig('soldtogether/order/amazonestyle')
            || Mage::getStoreConfig('soldtogether/order/addtocartcheckbox')) {

            $collection->getSelect()
                ->where('e.type_id IN (?)', array('simple', 'virtual'));
        }
        $collection->getSelect()->order(new Zend_Db_Expr('RAND()'));
        if (!Mage::getStoreConfig('soldtogether/general/out_of_stock')) {
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }
        if (!$collection->count()) {
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->setVisibility(
                Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()
            );
            $this->_addProductAttributesAndPrices($collection)
                ->addStoreFilter()
                ->setPageSize($this->getProductsCount())
                ->setCurPage(1)
                ->addAttributeToFilter('entity_id', array('nin' => array($product->getId())));

            if (Mage::getStoreConfig('soldtogether/order/amazonestyle')
                || Mage::getStoreConfig('soldtogether/order/addtocartcheckbox')) {

                $collection->getSelect()
                    ->where('e.type_id IN (?)', array('simple', 'virtual'));
            }
            $collection->getSelect()->order(new Zend_Db_Expr('RAND()'));
            if (!Mage::getStoreConfig('soldtogether/general/out_of_stock')) {
                Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
            }
        }

        return $collection;
    }
}
