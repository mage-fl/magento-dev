<?php

class TM_AjaxSearch_Model_Mysql4_Product_Collection extends Mage_CatalogSearch_Model_Resource_Search_Collection
{
    /**
     * Retrieve collection of all attributes
     *
     * @return Varien_Data_Collection_Db
     */
    protected function _getAttributesCollection()
    {
        $attributeCodes = array('name');
        $attributes = Mage::getStoreConfig('tm_ajaxsearch/general/attributes');
        if ($attributes != '') {
            $attributeCodes = explode(',', $attributes);
        }

        if (!$this->_attributesCollection) {
            $collection = Mage::getResourceModel('catalog/product_attribute_collection');
            if (!empty($attributes)) {
                $collection->addFieldToFilter('attribute_code', array('in' => $attributeCodes));
            }
            $this->_attributesCollection = $collection;
            // commented in previous version
            try {
                $entity = $this->getEntity();
                if ($entity instanceof Mage_Eav_Model_Entity_Abstract) {
                    foreach ($this->_attributesCollection as $attribute) {
                        $attribute->setEntity($entity);
                    }
                }
            } catch (Mage_Exception $e) {
                Mage::logException($e);
            }
        }
        return $this->_attributesCollection;
    }
}