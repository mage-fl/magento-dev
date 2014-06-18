<?php

class TM_AskIt_Helper_Data extends Mage_Core_Helper_Abstract
{
    //$item = Mage::helper('askit')->getItem($data);
    public function getItem($item)
    {
        $type = $item['item_type_id'];//$item->getItemTypeId();
        $id = $item['item_id'];//$item->getItemId();
        $_item = false;
        $_prefix = null;
        $return = new Varien_Object();

        switch ($type) {
            case TM_AskIt_Model_Item_Type::PRODUCT_ID:

                $_item    = Mage::getModel('catalog/product')->load($id);

                $_urlPath = $_item->getUrlPath();
                $_frontendItemUrl = $_item->getProductUrl();
                $_name    = $_item->getName();
                $_typeName = 'Product';

                $_backendItemUrl = Mage::helper("adminhtml")->getUrl(
                    'adminhtml/catalog_product/edit', array('id'=> $_item->getId())
                );
                $_prefix = 'product';
                break;
            case TM_AskIt_Model_Item_Type::PRODUCT_CATEGORY_ID:

                $_item    = Mage::getModel('catalog/category')->load($id);

                $_urlPath = $_item->getUrlPath();
                $_frontendItemUrl = $_item->getUrl();
                $_name    = $_item->getName();
                $_typeName = 'Catalog Category';

                $_backendItemUrl = Mage::helper("adminhtml")->getUrl(
                    'adminhtml/catalog_category/edit', array('id'=> $_item->getId())
                );
                $_prefix = 'category';

                break;
            case TM_AskIt_Model_Item_Type::CMS_PAGE_ID:

                $_item = Mage::getModel('cms/page')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($id);

                $_urlPath = $_item->getIdentifier();
                $_frontendItemUrl = Mage::helper('cms/page')->getPageUrl(
                    $_item->getId()
                );
                $_name    = $_item->getTitle();
                $_typeName = 'Cms Page';

                $_backendItemUrl = Mage::helper("adminhtml")->getUrl(
                    'adminhtml/cms_page/edit', array('page_id'=> $_item->getId())
                );
                $_prefix = 'page';
                break;

            default:
                $_urlPath = '';
                $_frontendItemUrl = '';
                $_name = 'unknown';
                $_typeName = 'unknow';
                $_backendItemUrl = '#';
                break;
        }
        if ($_item) {
            $return->setRawItem($_item);
        }
        $return->setUrlPath($_urlPath)
            ->setFrontendItemUrl($_frontendItemUrl)
            ->setName($_name)
            ->setTypeName($_typeName)
            ->setBackendItemUrl($_backendItemUrl)
            ->setPrefix($_prefix)
        ;
//        Zend_Debug::dump($return->getData());
        return $return;
    }

    public function getAskitActionHref($item)
    {

        $itemTypeId = $item['item_type_id'];
        $itemId = $item['item_id'];

        switch ($itemTypeId) {
            case TM_AskIt_Model_Item_Type::PRODUCT_ID:

                $_item = Mage::getModel('catalog/product')
                    ->load($itemId);

                $_url = $_item->getUrlPath();
                break;
            case TM_AskIt_Model_Item_Type::PRODUCT_CATEGORY_ID:

                $_item = Mage::getModel('catalog/category')
                    ->load($itemId);

                $_url = $_item->getUrlPath();

                break;
            case TM_AskIt_Model_Item_Type::CMS_PAGE_ID:

                $page = Mage::getModel('cms/page')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($itemId);

                $_url = $page->getIdentifier();
                break;

            default:
                return;
                break;
        }

        $url = Mage::getUrl(Mage::helper('askit')->getRouteUrlPrefix()) .  $_url;
//        $title = Mage::helper('askit')->__('View all related questions.');
        return $url;
    }

    public function getLinkHtml($item)
    {
        $_url = '';

        $collection = Mage::getModel('askit/item')->getCollection()
            ->addStatusFilter(array(
                TM_AskIt_Model_Status::STATUS_APROVED,
                TM_AskIt_Model_Status::STATUS_CLOSE
            ))
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->addQuestionFilter()
            ->addPrivateFilter()
        ;
        if ($item) {
            $itemId = $item->getId();

            $class = get_class($item);
            switch ($class) {
                 case 'Mage_Catalog_Model_Category':
                     $itemTypeId = TM_AskIt_Model_Item_Type::PRODUCT_CATEGORY_ID;
                     $_url = $item->getUrlPath();
                    break;
                 case 'Mage_Cms_Model_Page':
                     $itemTypeId = TM_AskIt_Model_Item_Type::CMS_PAGE_ID;
                     $_url = $item->getIdentifier();
                    break;
                case 'Mage_Catalog_Model_Product':
                default:
                    $itemTypeId = TM_AskIt_Model_Item_Type::PRODUCT_ID;
                    $_url = $item->getUrlPath();
                    break;
            }
            $collection = Mage::getModel('askit/item')->getCollection()
                ->addItemIdFilter($itemId)
                ->addItemTypeIdFilter($itemTypeId)
            ;
        }

        $href = Mage::getUrl($this->getRouteUrlPrefix()) . $_url;

        $count = count($collection->load());

        $title = Mage::helper('askit')->__(
            "Be the first to ask a question about this product"
        ) ;
        if($count) {
            $title = Mage::helper('askit')->__("Ask a question (%d)", $count);
        }
        return "<a href=\"{$href}\">{$title}</a><br/>";
    }

    public function trim($text, $len, $delim = '...')
    {
        if (@mb_strlen($text) > $len) {
            $whitespaceposition = mb_strpos($text, " ", $len) - 1;
                if( $whitespaceposition > 0 ) {
                    $chars = count_chars(mb_substr($text, 0, ($whitespaceposition + 1)), 1);
                    $text = mb_substr($text, 0, ($whitespaceposition + 1));
                }
            return $text . $delim;
        }
        return $text;
    }

    public function getRouteUrlPrefix()
    {
        return Mage::getStoreConfig('askit/general/urlPrefix');
    }
}