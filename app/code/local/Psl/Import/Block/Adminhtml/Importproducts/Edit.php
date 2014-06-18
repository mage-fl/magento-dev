<?php

class Psl_Import_Block_Adminhtml_Importproducts_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'psl_import';
        $this->_controller = 'adminhtml_importproducts';

        $this->_updateButton('save','label',Mage::helper('adminhtml')->__('Start process'));
        $this->_updateButton('save','onclick','importproducts_form.submit()');
        $this->removeButton('reset');
        $this->removeButton('back');

        $this->_formScripts[] = "
        ";
    }

    public function getHeaderText()
    {
        return Mage::helper('psl_import')->__('Products File');
    }
    
    protected function _prepareLayout() {
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        $this->setChild('form', $this->getLayout()->createBlock('psl_import/adminhtml_importproducts_form'));
    }
}