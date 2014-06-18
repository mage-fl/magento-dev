<?php
/**
 * This is the part of 'Highlight' module for Magento,
 * which allows easy access to product collection
 * with flexible filters
 *
 * @author Templates-Master
 * @copyright Templates Master www.templates-master.com
 */

class TM_Highlight_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $type = $this->getRequest()->getParam('type');
        $type = trim($type, '/ ');
        $typeMapping = array(
            'new'         => 'highlight/product_new',
            'onsale'      => 'highlight/product_special',
            'featured'    => 'highlight/product_featured',
            'bestsellers' => 'highlight/product_bestseller',
            'popular'     => 'highlight/product_popular'
        );
        if (!isset($typeMapping[$type])) {
            return $this->getResponse()->setRedirect(Mage::getUrl());
        }

        $this->loadLayout();
        $layout = $this->getLayout();
        $list   = $layout->getBlock('product_list');
        $block  = $layout->createBlock($typeMapping[$type])
            ->setNameInLayout('highlight_collection');

        if (!$block || !$list) {
            return $this->getResponse()->setRedirect(Mage::getUrl());
        }

        $list->setCollectionBlock($block);

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__($list->getTitle()));
        }

        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
}
