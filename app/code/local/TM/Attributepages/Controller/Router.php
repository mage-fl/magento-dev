<?php

class TM_Attributepages_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    /**
     * Initialize Controller Router
     *
     * @param Varien_Event_Observer $observer
     */
    public function initControllerRouters($observer)
    {
        /* @var $front Mage_Core_Controller_Varien_Front */
        $front = $observer->getEvent()->getFront();
        $front->addRouter('attributepages', $this);
    }

    /**
     * Validate and Match Page and modify request
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $pathInfo = trim($request->getPathInfo(), '/');
        $pathParts = explode('/', $pathInfo);
        $identifiers = array();
        foreach ($pathParts as $i => $param) {
            // see the app/code/core/Mage/Core/Model/Url.php::escape method
            $param = str_replace('%22', '"', $param);
            $param = str_replace('%27', "'", $param);
            $param = str_replace('%3E', '>', $param);
            $param = str_replace('%3C', '<', $param);

            $identifiers[] = $param;
            if ($i >= 1) {
                break;
            }
        }

        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getResourceModel('attributepages/entity_collection')
            ->addFieldToFilter('identifier', array('in' => $identifiers))
            ->addStoreFilter($storeId);

        // fix for the same identifiers for different options/pages
        $uniquePages = array();
        foreach ($collection as $page) {
            if (!$page->getUseForAttributePage()) {
                continue;
            }
            if (!empty($uniquePages[$page->getIdentifier()])) {
                if ($page->getStoreId() !== $storeId) {
                    continue;
                }
            }
            $uniquePages[$page->getIdentifier()] = $page;
        }

        $size = count($uniquePages);
        if (!$size) {
            return false;
        }

        $index = $size - 1;
        foreach ($uniquePages as $page) {
            if ($page->getIdentifier() === $identifiers[$index]) {
                $key = 'attributepages_current_page';
            } else {
                $key = 'attributepages_parent_page';
            }
            Mage::register($key, $page);
        }

        $current = Mage::registry('attributepages_current_page');
        if ($parent = Mage::registry('attributepages_parent_page')) {
            // disallow links like brands/color or black/white or black/htc
            if ($parent->isOptionBasedPage() || $current->isAttributeBasedPage()) {
                return false;
            }
            // disallow links like color/htc or brands/white
            if ($parent->getAttributeId() !== $current->getAttributeId()) {
                return false;
            }
        }

        // disallow direct link to option page: example.com/htc
        if ($current->isOptionBasedPage()
            && !$parent
            && !Mage::getStoreConfigFlag('attributepages/seo/allow_direct_option_link')) {

            return false;
        }

        // root category is always registered as current_category
        $categoryId = Mage::app()->getStore()->getRootCategoryId();
        if ($categoryId && !Mage::registry('current_category')) {
            $category = Mage::getModel('catalog/category')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($categoryId);

            Mage::register('current_category', $category);
        }

        $request->setModuleName('attributepages')
            ->setControllerName('page')
            ->setActionName('view')
            ->setParam('id', $page->getId());
        $request->setAlias(
            Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
            $pathInfo
        );

        return true;
    }
}
