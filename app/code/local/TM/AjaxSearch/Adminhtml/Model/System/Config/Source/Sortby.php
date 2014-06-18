<?php
/*AjaxSearch*/
class TM_AjaxSearch_Adminhtml_Model_System_Config_Source_Sortby
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'name', 'label'=>Mage::helper('catalog')->__('Product Name')),
            array('value'=>'price', 'label'=>Mage::helper('ajaxsearch')->__('Product base price')),
            array('value'=>'sku', 'label'=>Mage::helper('reports')->__('Product SKU')),
            array('value'=>'most_viewed', 'label'=>Mage::helper('adminhtml')->__('Most Viewed')),
            array('value'=>'category_products', 'label'=>Mage::helper('catalog')->__('Category Products')),
        );
    }
}