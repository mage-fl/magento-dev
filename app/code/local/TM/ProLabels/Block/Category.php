<?php

class TM_ProLabels_Block_Category extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('tm/prolabels/category/category.phtml');
    }

    public function getContentLabels()
    {
        $contentLabels = array();
        $mode = 'category';
        $manualModel = Mage::getModel('prolabels/label');
        $systemModel = Mage::getModel('prolabels/system');

        $labelsData = $manualModel->getContentLabelsData($this->getProduct()->getId(), $mode);

        $systemData = $systemModel->getSystemContentLabels();
        foreach ($systemData as $systemLabel) {
            if ($this->validateContentLabel($systemLabel)) {
                $contentLabels[] = $systemLabel;
            }
        }
        foreach ($labelsData as $manualLabel) {
            $contentLabels[] = $manualLabel;
        }
        return $contentLabels;
    }

    public function getLabelText($label, $mode)
    {
        $product = $this->getProduct();
        $helper = Mage::helper('prolabels');

        return $helper->_getText($product, $mode, $label);
    }

    public function validateContentLabel($labelData)
    {
        $helper = Mage::helper('prolabels');
        if ('1' == $labelData['rules_id']) {
            return $helper->_isOnSale($this->getProduct(), 'category', $labelData);
        } else if ('2' == $labelData['rules_id']) {
            return $helper->_canShowQuantity($this->getProduct(), 'category', $labelData);
        } else if ('3' == $labelData['rules_id']) {
            return $helper->checkNewDate($this->getProduct());
        }
    }
}