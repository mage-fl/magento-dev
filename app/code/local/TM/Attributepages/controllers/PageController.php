<?php

class TM_Attributepages_PageController extends Mage_Core_Controller_Front_Action
{
    public function viewAction()
    {
        $page = Mage::registry('attributepages_current_page');

        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->addHandle('default')
            ->addHandle('ATTRIBUTEPAGES_PAGE_' . $page->getId());
        $this->addActionLayoutHandles();

        if ($page->isAttributeBasedPage()) {
            $update->addHandle('attributepages_attribute_page');
        } else {
            $update->addHandle('attributepages_option_page');

            if (Mage::helper('attributepages')->canUseLayeredNavigation()) {
                $update->addHandle('attributepages_option_page_layered');
            } else {
                $update->addHandle('attributepages_option_page_default');
            }
        }

        if ($handle = $page->getRootTemplate()) {
            $layout->helper('page/layout')->applyHandle($handle);
        }

        $this->loadLayoutUpdates();
        $update->addUpdate($page->getLayoutUpdateXml());
        $this->generateLayoutXml()->generateLayoutBlocks();

        if ($root = $layout->getBlock('root')) {
            if ($page->isAttributeBasedPage()) {
                $suffix = '-attribute-page';
            } else {
                $suffix = '-option-page';
            }
            $root->addBodyClass('attributepages-' . $suffix);
            $root->addBodyClass('attributepages-' . $page->getIdentifier());
        }

        if ($breadcrumbs = $layout->getBlock('breadcrumbs')) {
            $breadcrumbs->addCrumb('home', array(
                'label' => Mage::helper('cms')->__('Home'),
                'title' => Mage::helper('cms')->__('Go to Home Page'),
                'link'  => Mage::getBaseUrl()
            ));
            if ($parentPage = Mage::registry('attributepages_parent_page')) {
                $breadcrumbs->addCrumb('parent_page', array(
                    'label' => $parentPage->getTitle(),
                    'title' => $parentPage->getTitle(),
                    'link'  => Mage::getUrl($parentPage->getIdentifier())
                ));
            }
            $breadcrumbs->addCrumb('current_page', array(
                'label' => $page->getTitle(),
                'title' => $page->getTitle()
            ));
        }

        if ($headBlock = $layout->getBlock('head')) {
            if ($title = $page->getTitle()) {
                $headBlock->setTitle($title);
            }
            if ($description = $page->getMetaDescription()) {
                $headBlock->setDescription($description);
            }
            if ($keywords = $page->getMetaKeywords()) {
                $headBlock->setKeywords($keywords);
            }
        }

        if ($page->isOptionBasedPage()) {
            Mage::helper('attributepages/page_view')
                ->initCollectionFilters($page, $this);
        }

        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
}
