<?php

class TM_Easyslide_Block_Slider extends Mage_Core_Block_Template
{
    public function getTemplate()
    {
        if (!$this->hasData('template')) {
            $this->setData('template', 'tm/easyslide/slider.phtml');
        }
        return $this->getData('template');
    }

    public function _toHtml()
    {
        if (!$this->_beforeToHtml() || !$sliderId = $this->getSliderId()) {
            return '';
        }
        $slider = Mage::getModel('easyslide/easyslide')->load($sliderId);
        if (!$slider->getStatus()) {
            return '';
        }
        $slider->loadSlides(true);
        $this->setSlider($slider);
        return parent::_toHtml();
    }

    public function filterDescription($description)
    {
        $processor = Mage::helper('cms')->getPageTemplateProcessor();
        return $processor->filter($description);
    }

    public function getDescriptionClassName($position)
    {
        switch ($position) {
            case 1:
                return "easyslide-description-top";
                break;
            case 2:
                return "easyslide-description-right";
                break;
            case 3:
                return "easyslide-description-bottom";
                break;
            case 4:
                return "easyslide-description-left";
                break;
        }
    }

    public function getBackgroundClassName($background)
    {
        switch ($background) {
            case 1:
                return "easyslide-background-light";
                break;
            case 2:
                return "easyslide-background-dark";
                break;
        }
    }
}
