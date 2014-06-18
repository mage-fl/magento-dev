<?php

class TM_Attributepages_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Load option page by product and attribute
     *
     * @param Mage_Catalog_Model_Product $product  Product or product id
     * @param string $attributeCode        Attribute code
     * @param string $parentPageIdentifier Parent page identifier
     *   Use this parameter, when the same option is linked to multiple attribute pages.
     *   For example, black is available at `color/black` and `tshirt-color/black`,
     *   and you would like to get the `tshirt-color/black` url
     * @return TM_Attributepages_Model_Entity
     */
    public function loadOptionByProductAndAttributeCode(
        Mage_Catalog_Model_Product $product, $attributeCode, $parentPageIdentifier = null)
    {
        $optionId = $product->getData($attributeCode);
        if (null === $optionId) {
            return false;
        }

        $attribute = Mage::getModel('eav/entity_attribute')
            ->loadByCode('catalog_product', $attributeCode);
        if (!$attribute) {
            return false;
        }

        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getResourceModel('attributepages/entity_collection')
            ->addFieldToFilter('attribute_id', $attribute->getAttributeId())
            ->addFieldToFilter('option_id', array(
                $optionId,
                'or' => array(
                    'null' => true // will look for parent page too without additional query
                )
            ))
            ->addUseForAttributePageFilter() // enabled flag
            ->addStoreFilter($storeId);

        if (!$option = $this->findOption($collection, $storeId)) {
            return false;
        }

        $option->setParentPageIdentifier($parentPageIdentifier);
        $option->setParentPage(
            $this->findParentPage(
                $option, $collection, $storeId, $parentPageIdentifier
            )
        );

        return $option;
    }

    /**
     * Find the most suitable option page from $collection
     *
     * @param  TM_Attributepages_Model_Resource_Entity_Collection $collection
     * @param  integer $storeId
     * @return TM_Attributepages_Model_Entity or false
     */
    public function findOption(
        TM_Attributepages_Model_Resource_Entity_Collection $collection,
        $storeId)
    {
        $option = false;
        foreach ($collection as $possibleOption) {
            if (!$possibleOption->getOptionId()) {
                continue;
            }

            if ($option) {
                if ($possibleOption->getStoreId() != $storeId) {
                    continue;
                }
            }
            $option = $possibleOption;
            if ($option->getStoreId() == $storeId) {
                break;
            }
        }
        return $option;
    }

    /**
     * Find parent page among $collection for $option
     *
     * @param  TM_Attributepages_Model_Entity $option
     * @param  TM_Attributepages_Model_Resource_Entity_Collection $collection
     * @param  string $identifier
     * @return mixed TM_Attributepages_Model_Entity or false
     */
    public function findParentPage(
        TM_Attributepages_Model_Entity $option,
        TM_Attributepages_Model_Resource_Entity_Collection $collection,
        $storeId,
        $identifier = null)
    {
        if ($identifier) {
            return $collection->getItemByColumnValue('identifier', $identifier);
        }

        $parentPage = false;
        $parentPages = $collection->getItemsByColumnValue('option_id', null);
        foreach ($parentPages as $page) {
            $excludedOptions = $page->getExcludedOptionIdsArray();
            if (in_array($option->getOptionId(), $excludedOptions)) {
                continue;
            }
            if ($parentPage) {
                if ($page->getStoreId() != $storeId) {
                    continue;
                }
            }
            $parentPage = $page;
            if ($parentPage->getStoreId() == $storeId) {
                break;
            }
        }
        return $parentPage;
    }

    public function canUseLayeredNavigation()
    {
        if (!Mage::getStoreConfigFlag('attributepages/product_list/use_layered_navigation')) {
            return false;
        }

        // for now we didn't test compatibility with third party extensions,
        // so just disable layer if it's not the magento standard navigation
        $layer = Mage::getModel('catalog/layer');
        if (get_class($layer) !== 'Mage_Catalog_Model_Layer') {
            return false;
        }

        $filter = Mage::getModel('catalog/layer_filter_attribute');
        if (get_class($filter) !== 'Mage_Catalog_Model_Layer_Filter_Attribute') {
            return false;
        }

        $helper = Mage::helper('core');
        $unsupportedModules = array('TM_AjaxLayeredNavigation');
        foreach ($unsupportedModules as $moduleName) {
            if ($helper->isModuleOutputEnabled($moduleName)) {
                return false;
            }
        }
        return true;
    }
}
