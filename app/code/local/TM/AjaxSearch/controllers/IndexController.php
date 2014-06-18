<?php
class TM_AjaxSearch_IndexController  extends Mage_Core_Controller_Front_Action
{
    private function _sendJson(array $data = array())
    {
        $json = Zend_Json::encode($data);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($json);
//        @header('Content-type: application/json');
//        echo json_encode($data);
//        exit();
    }

    private function _trim($text, $len, $delim = '...')
    {
        if (function_exists("mb_strstr")) {
            $strlen = 'mb_strlen';
            $strpos = 'mb_strpos';
            $substr = 'mb_substr';
        } else {
            $strlen = 'strlen';
            $strpos = 'strpos';
            $substr = 'substr';
        }

        if ($strlen($text) > $len) {
            $whitespaceposition = $strpos($text, " ", $len) - 1;
            if($whitespaceposition > 0) {
                $text = $substr($text, 0, ($whitespaceposition + 1));
            }
            return $text . $delim;
        }
        return $text;
    }

    protected function _sortProductCollectionByMostViewed($collection, $sort)
    {
        /**
         * Getting event type id for catalog_product_view event
         */
        foreach (Mage::getModel('reports/event_type')->getCollection() as $eventType) {
            if ($eventType->getEventName() == 'catalog_product_view') {
                $productViewEvent = (int)$eventType->getId();
                break;
            }
        }

        $select = $collection->getSelect();
        $select
            ->join(array(
                'report_table_views' => $collection->getTable('reports/event')),
                "e.entity_id = report_table_views.object_id",
                array('views' => 'COUNT(report_table_views.event_id)')
            )
            ->where('report_table_views.event_type_id = ?', $productViewEvent)
            ->group('e.entity_id')
            ->order('views ' . $sort)
        ;
//        echo((string)$select);
        return $collection;
    }


    protected function _sortProductCollectionByCategoryProducts($collection, $sort)
    {

        $select = $collection->getSelect();
        $select
            ->join(array(
                'catalog_category_product' => $collection->getTable('catalog/category_product')),
                "e.entity_id = catalog_category_product.product_id",
                array('position')
            )
            ->group('e.entity_id')
            ->order('position ' . $sort)
        ;

        return $collection;
    }

    protected function _getProductCollection($query, $store, Mage_Catalog_Model_Category $category = null)
    {
        if (Mage::getStoreConfig('tm_ajaxsearch/general/use_catalogsearch_collection')
            && class_exists('Mage_CatalogSearch_Model_Resource_Search_Collection')) {
            $collection = Mage::getResourceModel('ajaxsearch/product_collection');
            /* @var $collection TM_AjaxSearch_Model_Mysql4_Product_Collection */
            $collection->addSearchFilter($query);
        } else {
            $collection = Mage::getResourceModel('ajaxsearch/collection')
                ->getProductCollection($query);
            /* @var $collection TM_AjaxSearch_Model_Mysql4_Collection */
        }

        $attributeToSort = Mage::getStoreConfig('tm_ajaxsearch/general/sortby');
        $attributeSortOrder = Mage::getStoreConfig('tm_ajaxsearch/general/sortorder');

        if ('most_viewed' === $attributeToSort) {
            $collection = $this->_sortProductCollectionByMostViewed(
                $collection, $attributeSortOrder
            );
        } elseif (null !== $category && 'category_products' === $attributeToSort) {
            $collection = $this->_sortProductCollectionByCategoryProducts(
                $collection, $attributeSortOrder, $category
            );
        } else {
            $collection->addAttributeToSort($attributeToSort, $attributeSortOrder);
        }

        $collection->addStoreFilter($store)
            ->addUrlRewrite()
//            ->addAttributeToSort($attributeToSort, $attributeSortOrder)
            ->setPageSize(Mage::getStoreConfig('tm_ajaxsearch/general/productstoshow'))
        ;

        if (null !== $category) {
            $collection->addCategoryFilter($category);
        }

        Mage::getSingleton('catalog/product_status')
            ->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')
            ->addVisibleInSearchFilterToCollection($collection);

        $collection->load();
        return $collection;
    }

    protected function _getCategoryCollection($query, $store)
    {
        return Mage::getResourceModel('ajaxsearch/collection')
            ->getCategoryCollection($query, $store);
    }

    protected function _getCmsCollection($query, $store)
    {
        return Mage::getResourceModel('ajaxsearch/collection')
                ->getCmsCollection($query, $store);
    }

    public function indexAction()
    {
        $query = $this->getRequest()->getParam('query', '');
        $helper = Mage::helper('core');
        if (method_exists($helper, 'removeTags')) {
            $_query = Mage::helper('core')->removeTags($query);
        } else {
            $_query = strip_tags($query);
        }
        $store = (int)Mage::app()->getStore()->getStoreId();

        $searchURL = Mage::helper('catalogsearch/data')->getResultUrl($query);

        $suggestions = array();

        $suggestions[] = array('html' =>
            '<p class="headerajaxsearchwindow">' .
                Mage::getStoreConfig('tm_ajaxsearch/general/headertext') .
                " <a href='{$searchURL}'>{$_query}</a>" .
            '</p>'
        );

        $isEnabledImage = Mage::getStoreConfig('tm_ajaxsearch/general/enableimage');
        $imageHeight    = Mage::getStoreConfig('tm_ajaxsearch/general/imageheight');
        $imageWidth     = Mage::getStoreConfig('tm_ajaxsearch/general/imagewidth');

        $isEnabledDescription = Mage::getStoreConfig('tm_ajaxsearch/general/enabledescription');
        $lengthDescription = (int) Mage::getStoreConfig('tm_ajaxsearch/general/descriptionchars');

        $category   = null;
        $categoryId = $this->getRequest()->getParam('category', '');
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            if (!$category->getId()) {
                $category = null;
            }
        }

        $collection = $this->_getProductCollection($query, $store, $category);
        if (count($collection)) {
            $suggestions[] = array('html' =>
                '<p class="headercategorysearch">' . $this->__("Products") . '</p>'
            );
        }
        foreach($collection as $_row) {

            $_product = Mage::getModel('catalog/product')->load($_row->getId());

            $_image = $_description = '';

            if($isEnabledImage) {
                $_image = Mage::helper('catalog/image')->init($_product, 'thumbnail')
                        ->resize($imageWidth, $imageHeight)
                        ->__toString();
            }
            if($isEnabledDescription) {
                $_description = strip_tags($this->_trim(
                    $_product->getShortDescription(), $lengthDescription
                ));
            }

            $suggestions[] = array(
                'name'        => $_product->getName(),
                'url'         => $_product->getProductUrl(),
                'image'       => $_image,
                'description' => $_description
            );
        }

        /*
         *     category search
         */
        if (Mage::getStoreConfig('tm_ajaxsearch/general/enablecatalog')) {
            $collection = $this->_getCategoryCollection($query, $store);
            if (count($collection)) {
                $suggestions[] = array('html' => '<p class="headercategorysearch">'
                    . $this->__("Categories")
                    . '</p><span class="hr"></span>'
                );
            }
            foreach ($collection as $_row) {
                $category = Mage::getModel("catalog/category")->load($_row['entity_id']);
                $suggestions[] = array(
                    'name' => $_row['name'],
                    'url'  => $category->getUrl()
                );
            }
        }
        /*
         * end category search
         */

        /*
         *     cms search
         */
        if (Mage::getStoreConfig('tm_ajaxsearch/general/enablecms')) {

            $collection = $this->_getCmsCollection($query, $store);
            if (count($collection)) {
                $suggestions[] = array('html' => '<p class="headercategorysearch">'
                    . $this->__("Info Pages")
                    . '</p><span class="hr"></span>'
                );
            }
            foreach ($collection as $_page) {

                $page = Mage::getModel('cms/page')
                    ->setStoreId($store)
                    ->load($_page->getId());

                if (!$page || !$page->getId()) {
                    continue;
                }

                $suggestions[] = array(
                    'name' => $page->getTitle(),
                    'url'  => Mage::helper('cms/page')->getPageUrl($page->getId())
                );
            }
        }
        /*
         * end cms search
         */
        if (1 < count($suggestions)) {
            $suggestions[] = array('html' =>
                '<p class="headerajaxsearchwindow">' .
                    Mage::getStoreConfig('tm_ajaxsearch/general/footertext') .
                " <a href='{$searchURL}'>{$_query}</a>" .
                '</p>'
            );
        }

        $this->_sendJson(array(
            'query'       => $query,
            'category'    => $categoryId ? $categoryId : '',
            'suggestions' => $suggestions
        ));
    }
}