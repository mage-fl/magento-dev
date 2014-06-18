<?php
class TM_AskIt_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function initControllerRouters($observer)
    {
        $front = $observer->getEvent()->getFront();
        $front->addRouter($this->_getPrefixPart(), $this);
    }

    /**
     *
     * @return string
     */
    protected function _getPrefixPart()
    {
        return Mage::helper('askit')->getRouteUrlPrefix();
    }

    /**
     *
     * @param Zend_Controller_Request_Http $request
     * @return boolean
     */
    protected function _matchProduct(Zend_Controller_Request_Http $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        $_identifier = trim(
            str_replace($this->_getPrefixPart(), '', $request->getPathInfo()),
            '/'
        );
        
        $rewriteModel = Mage::getModel('core/url_rewrite')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->loadByRequestPath($_identifier);

        $productId = $rewriteModel->getProductId();
        $product = null;
        if ($productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
        }
        
        if (!$product) {
            $parts = explode('/', $_identifier);
            $productCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('url_path', $parts[0]);

            if (!$productCollection->count()) {
                return false;
            }
            $product = $productCollection->getFirstItem();
            $product->load($product->getId());
        }
        
        if (!$product) {
            return false;
        }
        $request->setModuleName('askit')
            ->setControllerName('index')
            ->setActionName('product')
            ->setParam('product_id', $product->getId())
            ->setAlias(
                Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                $identifier
            );
        Mage::register('current_product', $product);
        return true;
    }

    /**
     *
     * @param Zend_Controller_Request_Http $request
     * @return boolean
     */
    protected function _matchCategory(Zend_Controller_Request_Http $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        $_identifier = trim(
            str_replace($this->_getPrefixPart(), '', $request->getPathInfo()),
            '/'
        );

        $rewriteModel = Mage::getModel('core/url_rewrite');
        $rewriteModel->setStoreId(Mage::app()->getStore()->getId());
        $rewriteModel->loadByRequestPath($_identifier);

        if (!$rewriteModel->getId() || !$rewriteModel->getCategoryId()) {
            return false;
        }
        $categoryId = $rewriteModel->getCategoryId();

        $catogory = Mage::getModel('catalog/category')->load($categoryId);

        if (!$catogory) {
            return false;
        }
        $request->setModuleName('askit')
            ->setControllerName('index')
            ->setActionName('category')
            ->setParam('category_id', $categoryId)
            ->setAlias(
                Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                $identifier
            );
        Mage::register('current_category', $catogory);
        return true;
    }

    /**
     *
     * @param Zend_Controller_Request_Http $request
     * @return boolean
     */
    protected function _matchPage(Zend_Controller_Request_Http $request)
    {
        $identifier = trim($request->getPathInfo(), '/');

        $_identifier = trim(
            str_replace($this->_getPrefixPart(), '', $request->getPathInfo()),
            '/'
        );

        $parts = explode('/', $_identifier);
        $page   = Mage::getModel('cms/page');
        $pageId = $page->checkIdentifier(
            $parts[0], Mage::app()->getStore()->getId()
        );

        if (!$pageId) {
            return false;
        }
        $request->setModuleName('askit')
            ->setControllerName('index')
            ->setActionName('page')
            ->setParam('page_id', $pageId)
            ->setAlias(
                Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                $identifier
            );
        return true;
    }

    /**
     *
     * @param Zend_Controller_Request_Http $request
     * @return boolean
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            die;
        }

        $identifier = trim($request->getPathInfo(), '/');
        $parts = explode('/', $identifier);

        if ($this->_getPrefixPart() != $parts[0]) {
            return false;
        }
        if (empty($parts[1])) {
            $request->setModuleName('askit')
                ->setControllerName('index')
                ->setActionName('index')
                ->setAlias(
                    Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
                    $identifier
                );
            return true;
        }

        return $this->_matchProduct($request)
            || $this->_matchCategory($request)
            || $this->_matchPage($request);
    }
}