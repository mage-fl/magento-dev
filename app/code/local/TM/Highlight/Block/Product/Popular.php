<?php
/**
 * This is the part of 'Highlight' module for Magento,
 * which allows easy access to product collection
 * with flexible filters
 *
 * @author Templates-Master
 * @copyright Templates Master www.templates-master.com
 */

class TM_Highlight_Block_Product_Popular extends TM_Highlight_Block_Product_Abstract
{
    protected $_title       = 'Popular Products';
    protected $_priceSuffix = '-popular';
    protected $_className   = 'highlight-popular';

    protected function _beforeToHtml()
    {
        $collection = $this->getCollection('highlight/reports_product_collection')
            ->addViewsCount();

        return parent::_beforeToHtml();
    }
}
