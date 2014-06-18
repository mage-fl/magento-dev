<?php

class TM_AjaxPro_Model_Observer {

    /**
     *
     * @var Mage_Core_Controller_Varien_Action
     */
    protected $_controllerAction;

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->_controllerAction->getRequest();
    }

    /**
     * Retrieve current layout object
     *
     * @return Mage_Core_Model_Layout
     */
    public function getLayout()
    {
        return $this->_controllerAction->getLayout();
    }

    /**
     *
     * @return Mage_Core_Controller_Response_Http
     */
    public function getResponse()
    {
        return $this->_controllerAction->getResponse();
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return boolean
     */
    protected function _prepareObserver(Varien_Event_Observer $observer)
    {
        $controllerAction = $observer->getEvent()->getControllerAction();
        if (!$controllerAction instanceof Mage_Core_Controller_Varien_Action) {
            return false;
        }
        $this->_controllerAction = $controllerAction;
        return true;
    }

    /**
     *
     * @param array $data
     * @return void
     */
    protected function _prepareResponse($data = array())
    {
        if (empty($data)) {
            return;
        }

        // clear uenc
        $previousEncodedUrl = $this->getRequest()->getParam(
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED, false
        );
        if ($previousEncodedUrl) {
            $currentEncodedUrl = Mage::helper('core')->urlEncode(
                Mage::helper('core/url')->getCurrentUrl()
            );

            foreach (array('layout', 'custom') as $namespace) {
                if (!isset($data[$namespace])) {
                    continue;
                }
                foreach ($data[$namespace] as $block => $content) {
                    $data[$namespace][$block] = str_replace(
                        $currentEncodedUrl,
                        $previousEncodedUrl,
                        $content
                    );
                }
            }
        }

        $response = $this->getResponse();

        $messageBlock = $this->getLayout()->getMessagesBlock();
        $data['status'] = !($messageBlock &&  false === $messageBlock->getStatus());

        $onlyBlocks =  $this->getRequest()->getParam('onlyblocks', false);
        if ($onlyBlocks){
            $data['status'] = true;
        }

        if ($response->isRedirect()) {
            //$redirectUrl = $response->getHeader('Location');
            $headers = $response->getHeaders();
            $redirectUrl = false;
            foreach($headers as $header) {
                if ('Location' === $header['name']) {
                    $redirectUrl = $header['value'];
                }
            }
            $response->clearHeader('Location');
            if (!$data['status']) {
                $data['redirectUrl'] = $redirectUrl;
            }
        }
//        if ($url = Mage::getSingleton('checkout/session')->getViewCartUrl(true)) {
//            $data['viewCartUrl'] = $url;
//        }
//        Zend_Debug::dump($response->getHeaders());
//        $response->clearHeaders();
        $response
            ->setHttpResponseCode(200)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(Mage::helper('core')->jsonEncode($data))
        ;
    }

    /**
     *
     * @param type $observer
     * @param array $handles
     * @param array $blockNames
     * @return array
     */
    protected function _prepareLayout($observer, $handles = array(), $blockNames = array())
    {
        $object = new Varien_Object(array(
            'handles'     => $handles,
            'block_names' => $blockNames
        ));
        Mage::dispatchEvent('ajaxpro_load_layout_before', array(
            'observer' => $observer,
            'object'   => $object
        ));
        $data = Mage::getModel('ajaxpro/layout')
            ->setControllerAction($this->_controllerAction)
            ->loadLayout($object->getHandles(), $object->getBlockNames())
            ->renderLayout()
        ;

        return $data;
    }

    /**
     *
     * @param string $path
     * @return bool
     */
    public function isAjaxProRequest($path = null)
    {
        $request = $this->getRequest();
        return
            $request
            && $request->isXmlHttpRequest()
            && $request->getParam('ajaxpro', false)
            && Mage::getStoreConfig('ajax_pro/general/enabled')
            && (null == $path || Mage::getStoreConfig($path))
        ;
    }

    /**
     *
     * @param mixed $paths
     * @return array
     */
    protected function _getBlockNamesFromConfig($paths)
    {
        if (!is_array($paths)) {
           $paths = array($paths);
        }
        $blocks = array();
        foreach ($paths as $path) {
            $blocks = array_merge($blocks,
                explode(',', str_replace(' ', '', Mage::getStoreConfig($path)))
            );
        }

        return array_filter($blocks);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function toHtmlAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('ajax_pro/general/enabled')) {
            return;
        }
        //core_block_abstract_to_html_after
        $block  = $observer->getBlock();
        $transport = $observer->getTransport();

        $blockName = $block->getNameInLayout();
        if (empty($blockName)) {
            return;
        }

        $allowedBlockNames = $this->_getBlockNamesFromConfig(array(
            'ajax_pro/general/blocks',
            'ajax_pro/checkoutCart/blocks',
            'ajax_pro/wishlistIndex/blocks',
            'ajax_pro/catalogProductCompare/blocks',
//            'ajax_pro/catalogCategoryView/blocks'
            'ajax_pro/catalogProductView/blocks'
        ));
        $allowedBlockNames[] = 'ajaxpro_message';
        $request = Mage::app()->getRequest();
        if (in_array('suggestpage_index_index', $request->getParam('handles', array()))
            || 'suggest' === $request->getModuleName()) {

            $allowedBlockNames[] = 'content';
        }

        if (in_array($blockName, $allowedBlockNames)) {
            $html = $transport->getHtml();
            if (empty($html)) {
                $html = '<span></span>'; // IE9 bugfix
            }
            $transport->setHtml(
                '<!-- ajaxpro_' .  $blockName . '_start -->' .
                  $html .
                '<!-- ajaxpro_' .  $blockName . '_end -->'
            );
        }
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutCartAddAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/checkoutCart/enabled')) {

            return;
        }

        $blockNames = $this->_getBlockNamesFromConfig(array(
            'ajax_pro/general/blocks',
            'ajax_pro/checkoutCart/blocks'
        ));
        $handles = $this->getRequest()->getParam('handles', array());

        if (Mage::getStoreConfig('ajax_pro/checkoutCart/enabledForm')
            && !in_array('checkout_cart_index', $handles)) {
            $blockNames[] = 'ajaxpro_message';

            $handle = Mage::getStoreConfig('ajax_pro/checkoutCart/messageHandle');
//            if (in_array('checkout_cart_index', $handles)) {
//                $handle = 'tm_ajaxpro_checkout_cart_add_simple';
//            }
            if (TM_AjaxPro_Model_UserAgent::isMobile()) {
                $handle = 'tm_ajaxpro_checkout_cart_add_simple';
            }
            $handles[] = $handle;
        }
        $data = $this->_prepareLayout($observer, $handles, $blockNames);

        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutCartUpdateItemOptionsComplete(Varien_Event_Observer $observer)
    {
        Mage::getModel('checkout/session')->addNotice(
            'Your will be redirected to shoping cart now'
        );
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function checkoutCartQuoteTotalsRecalculate(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/checkoutCart/enabled')) {

            return;
        }
        Mage::getSingleton('checkout/cart')->getQuote()
            ->setTotalsCollectedFlag(0)
            ->collectTotals()
            ->save();
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function clearQuoteMessages(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/checkoutCart/enabled')) {

            return;
        }
        $collection = Mage::getSingleton('checkout/cart')->getQuote()
            ->getItemsCollection();
        foreach ($collection as $quoteItem) {
            $quoteItem->clearMessage();
        }
    }


    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function wishlistIndexAddAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/wishlistIndex/enabled')) {

            return;
        }
        Mage::unregister('shared_wishlist');
        Mage::unregister('wishlist');

        $blockNames = $this->_getBlockNamesFromConfig(array(
            'ajax_pro/general/blocks',
            'ajax_pro/wishlistIndex/blocks'
        ));
        $handles = $this->getRequest()->getParam('handles', array());
        if (Mage::getStoreConfig('ajax_pro/wishlistIndex/enabledForm')
            && Mage::helper('customer')->isLoggedIn()) {

            $blockNames[] = 'ajaxpro_message';
            $handles[] = 'tm_ajaxpro_wishlist_index';
        }
        $data = $this->_prepareLayout($observer, $handles, $blockNames);
        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function wishlistIndexCartAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/wishlistIndex/enabled')) {

            return;
        }

        $blockNames = $this->_getBlockNamesFromConfig(array(
            'ajax_pro/general/blocks',
            'ajax_pro/checkoutCart/blocks',
            'ajax_pro/wishlistIndex/blocks'
        ));
        $handles = $this->getRequest()->getParam('handles', array());
        if ('fromcart' === $this->getRequest()->getActionName()
            && in_array('checkout_cart_index', $handles)) {

            // no action here
        } elseif (Mage::getStoreConfig('ajax_pro/wishlistIndex/enabledForm')
            && Mage::helper('customer')->isLoggedIn()) {

            $blockNames[] = 'ajaxpro_message';
            $handle = Mage::getStoreConfig('ajax_pro/checkoutCart/messageHandle');
            $handles[] = $handle;
        }
        $data = $this->_prepareLayout($observer, $handles, $blockNames);
        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductCompareAddAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/catalogProductCompare/enabled')) {

            return;
        }
        $blockNames = $this->_getBlockNamesFromConfig(array(
            'ajax_pro/general/blocks',
            'ajax_pro/catalogProductCompare/blocks'
        ));
        $handles = $this->getRequest()->getParam('handles', array());
        if (Mage::getStoreConfig('ajax_pro/catalogProductCompare/enabledForm')
            && Mage::getSingleton('log/visitor')->getId()) {

            $blockNames[] = 'ajaxpro_message';
            $handles[] = 'tm_ajaxpro_catalog_product_compare_add';
        }
        $data = $this->_prepareLayout($observer, $handles, $blockNames);

        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogCategoryViewAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/catalogCategoryView/enabled')) {

            return;
        }
//      $blocks = $this->_getBlocksFromConfig('ajax_pro/catalogCategoryView/blocks');

        $layout = $this->getLayout();
//        $update = $layout->getUpdate();
//        $update->merge('catalog_category_layered');
//        $layout->generateXml();
//        $layout->generateBlocks();

        $block = $layout->getBlock('product_list');
        if (!$block) {
            $block = $layout->getBlock('search_result_list');
        }
        $content = $block->toHtml();

        $toolbarHtml = $block->getToolBarHtml();
        $content = str_replace($toolbarHtml, '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = str_replace(array("\n", "\t",
            '<div class="category-products">',
            '<div class="toolbar-bottom"> </div> </div>'),
        '', $content);

        $data = array(
            'custom' => array(
                'product_list' => $content
            )
        );

        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function catalogProductViewAction(Varien_Event_Observer $observer)
    {
        if (!$this->_prepareObserver($observer)
            || !$this->isAjaxProRequest('ajax_pro/catalogProductView/enabled')) {

            return;
        }
        $layout = $this->getLayout();
        $block = $layout->getBlock('product.info');
        $block->setTemplate('tm/ajaxpro/catalog/product/view/form.phtml');

        $content = $block->toHtml();

        // move local js variable to global scope
        $varscripts = array(
            'optionsPrice',
            'spConfig',
            'optionFileUpload',
            'optionTextCounter',
            'opConfig',
            'DateOption',
            'productAddToCartForm',
            'addTagFormJs',
            'bundle'
        );
        foreach ($varscripts as $varscript) {
            $content = str_replace('var ' . $varscript, $varscript, $content);
        }
        //validateOptionsCallback to global js scope
        $content = str_replace('function validateOptionsCallback(elmId, result){',
            'window.validateOptionsCallback = function (elmId, result){',
            $content
        );

        //fix configurable trabl (remove from form url bad suffix)
        $content = str_replace('?___SID=U', '', $content);

        $blockName = 'ajaxpro_message';
        $content = '<!-- ajaxpro_' .  $blockName . '_start -->' .
                $content .
            '<!-- ajaxpro_' .  $blockName . '_end -->';

        $data = array(
            'layout' => array(
                $blockName => $content
            )
        );

        $this->_prepareResponse($data);
    }

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function refreshToolbarVars(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('ajax_pro/general/enabled')) {
            return;
        }
        if (!Mage::getStoreConfig('ajax_pro/catalogCategoryView/enabled')) {
            return;
        }

        $moduleName = $observer->getBlock()->getRequest()->getModuleName();
        $supportedModules = array('catalog', 'catalogsearch');
        if (!in_array($moduleName, $supportedModules)) {
            return;
        }

        //core_block_abstract_to_html_after
        $block  = $observer->getBlock();
        $transport = $observer->getTransport();

        $blockName = $block->getNameInLayout();
        if (empty($blockName)) {
            return;
        }

        $allowedBlockNames = array('product_list', 'search_result_list');
        if (!in_array($blockName, $allowedBlockNames)) {
            return;
        }

        $refreshBlock = $block->getLayout()->createBlock('core/template')
            ->setTemplate('tm/ajaxpro/catalog/category/init.phtml')
        ;
        $transport->setHtml(
            $transport->getHtml() .
            $refreshBlock->toHtml()
        );

    }
}
