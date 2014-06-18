<?php

class TM_Argento_Block_Html_Head extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime' => 86400
        ));
    }

    public function getCacheKeyInfo()
    {
        $design = Mage::getDesign();
        return array(
            'TM_ARGENTO',
            Mage::app()->getStore()->getId(),
            Mage::app()->getStore()->isCurrentlySecure(),
            $design->getPackageName(),
            $design->getTheme('template')
        );
    }

    protected function _prepareLayout()
    {
        $head = $this->getLayout()->getBlock('head');
        if (!$head) {
            return $this;
        }

        // add custom.css from argento skin folder
        $design    = Mage::getDesign();
        $customCss = 'css/custom.css';
        $customCssUrl = $design->getSkinUrl($customCss);

        $argentoSkin = $design->getSkinBaseUrl(array(
            '_theme'   => 'default',
            '_package' => 'argento'
        ));
        $argentoSkin = trim($argentoSkin, '/ ');
        $argentoSkin = str_replace('/argento/default', '/argento', $argentoSkin);

        // include custom.css if it was found inside skin/argento folder
        if (strstr($customCssUrl, $argentoSkin)) {
            $head->addCss($customCss);
        }
    }

    public function getLinks()
    {
        $design = Mage::getDesign();
        $theme  = $design->getPackageName() . '_' . $design->getTheme('template');
        return Mage::getStoreConfig($theme . '/head/link') . Mage::getStoreConfig($theme . '/font/head_link');
    }

    /**
     * Finds backend.css to use for current argento theme
     *
     * Fallback rules are used to support Magento's configuration descending:
     *  media/[package]/[theme]/[website_store]_backend.css
     *  media/[package]/[theme]/[website]_backend.css
     *  media/[package]/[theme]/0_backend.css
     *
     * @return string of false
     */
    public function getBackendCss()
    {
        $design      = Mage::getDesign();
        $theme       = $design->getPackageName() . '_' . $design->getTheme('template');
        $storeCode   = Mage::app()->getStore()->getCode();
        $websiteCode = Mage::app()->getWebsite()->getCode();
        $args = array(
            array($theme, $storeCode, $websiteCode),
            array($theme, null, $websiteCode),
            array($theme, null, null)
        );
        $mediaDir = Mage::getBaseDir('media');
        $css      = Mage::getModel('argento/css');
        foreach ($args as $_args) {
            $filePath = $css->getFilePath($_args[0], $_args[1], $_args[2]);
            if (file_exists($mediaDir . DS . $filePath)) {
                return Mage::getBaseUrl('media') . '/' . str_replace(DS, '/', $filePath);
            }
        }
        return false;
    }
}
