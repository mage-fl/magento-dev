<?php

class TM_AjaxSearch_Model_Mysql4_Collection
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{

    public function getProductCollection($query)
    {
        $attributes = array('name');
        $searchAttributes = Mage::getStoreConfig('tm_ajaxsearch/general/attributes');
        if (!empty($searchAttributes)) {
            $attributes = explode(',', $searchAttributes);
        }
        $andWhere = array();
        foreach ($attributes as $attribute) {

            $this->addAttributeToSelect($attribute, true);
            foreach (explode(' ', trim($query)) as $word) {
                $andWhere[] = $this->_getAttributeConditionSql(
                    $attribute, array('like' => '%' . $word . '%')
                );
            }
            $this->getSelect()->orWhere(implode(' AND ', $andWhere));
            $andWhere = array();
        }

        return $this;
    }

    public function getCategoryCollection($query, $storeId)
    {
        /** @var $collection Mage_Catalog_Model_Resource_Category_Collection */
        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToSelect('is_active')
            ->setStoreId($storeId);

        if (method_exists($collection, 'addStoreFilter')) {
            $collection->addStoreFilter($storeId);
        } else {
            $rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
            $root   = Mage::getModel('catalog/category')->load($rootId);
            $collection->addFieldToFilter(
                'path', array('like' => "{$root->getPath()}/%")
            );
        }

        foreach (explode(' ', trim($query)) as $word) {
            $collection->addAttributeToFilter(
                'name', array('like' => "%{$word}%")
            );
        }

        $collection->addIsActiveFilter();
        foreach ($collection as $key => $item) {
            if (!$item->getIsActive()) {
                $collection->removeItemByKey($key);
            }
        }

        return $collection;
    }

    public function getCmsCollection($query, $storeId)
    {
        $collection = Mage::getModel('cms/page')->getCollection()
            ->addStoreFilter($storeId);

        $andWhere = array();
        foreach (explode(' ', trim($query)) as $word) {

            $collection->addFieldToFilter(
                'title', array('like'=> '%' . $word .'%')
            );

            $andWhere[] = $collection->_getConditionSql(
                'title', array('like' => '%' . $word . '%')
            );
        }
        $collection->getSelect()->orWhere(implode(' AND ', $andWhere));

        return $collection;
    }

}