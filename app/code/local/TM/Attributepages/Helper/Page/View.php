<?php

class TM_Attributepages_Helper_Page_View extends Mage_Core_Helper_Abstract
{
    public function initCollectionFilters(TM_Attributepages_Model_Entity $page, $controller)
    {
        $layout = $controller->getLayout();
        $layer  = Mage::getSingleton('catalog/layer');
        if ($productList = $layout->getBlock('product_list')) {
            $productCollection = $productList->getLoadedProductCollection();
        }

        // filter by category
        $categoryId = (int) $controller->getRequest()->getParam('cat', false);
        $category = false;
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($categoryId);

            if (!Mage::helper('catalog/category')->canShow($category)) {
                $category = false;
            }
        }

        if (!$category) {
            $category = $layer->getCurrentCategory();
        }
        /**
         * Hack to call for unset($this->_productLimitationFilters['category_is_anchor']);
         * in Mage/Catalog/Model/Resource/Product/Collection.php::addCategoryFilter
         * to remove cat_index.is_parent filter
         */
        $category->setIsAnchor(1);
        $productCollection->addCategoryFilter($category);

        // remove page attribute from filters
        $layerBlockNames = Mage::getStoreConfig('attributepages/product_list/layer_block_name');
        foreach (explode(',', $layerBlockNames) as $layerBlockName) {
            $layerBlock = $layout->getBlock($layerBlockName);
            if (!$layerBlock) {
                continue;
            }
            $filterableAttributes = $layer->getFilterableAttributes();
            if ($filterableAttributes) {
                /**
                 * Previous hack causes filterable attribute recalculation, so
                 * we need to create dummy blocks for new filters to
                 * prevent error in layer/view.phtml
                 */
                foreach ($filterableAttributes as $attribute) {
                    if (!$layerBlock->getChild($attribute->getAttributeCode() . '_filter')) {
                        $layerBlock->setChild(
                            $attribute->getAttributeCode() . '_filter',
                            $layout->createBlock('core/template')
                        );
                    }
                }
                $filterableAttributes->removeItemByKey($page->getAttribute()->getAttributeId());
            }
            $layerBlock->setData('_filterable_attributes', $filterableAttributes);
        }

        /**
         * @todo get class types with reflection: php_version >= 5.3
         *  $reflectedClass = new ReflectionClass($layerBlock);
         *  $property = $reflectedClass->getProperty('_attributeFilterBlockName');
         *  $property->setAccessible(true);
         *  $property->getValue($class);
         */
        $filterType = 'catalog/layer_filter_attribute';
        $filter = Mage::getModel($filterType)
            ->setAttributeModel($page->getAttribute())
            ->setLayer($layer);
        Mage::getResourceModel($filterType)
            ->applyFilterToCollection($filter, $page->getOption()->getOptionId());
    }
}
