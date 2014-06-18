<?php

class Psl_Import_Adminhtml_IndexController extends Mage_Adminhtml_Controller_action
{

    public function indexAction() {
        $this->loadLayout()
            ->_setActiveMenu('catalog/products');

        $block = $this->getLayout()
            ->createBlock('psl_import/adminhtml_importproducts_edit','importproducts');
        $this->_addContent($block);

        $this->renderLayout();
    }
    
    public function processproductsfileAction(){
        $mimes  = array('text/comma-separated-values',
                            'text/csv',
                            'application/csv'/*,
                            'application/vnd.ms-excel',
                            'application/vnd.msexcel',
                            'text/anytext'*/);
        
        $tmp = $_FILES['file'];
        if(!in_array( $tmp['type'] , $mimes )){
            Mage::getSingleton('adminhtml/session')->addError('This is not a supported format file '.$tmp['type']);
            $this->_redirect('*/*/index');
            return;
        }else{
            $path = $tmp['tmp_name'];            
            
            $messages = Mage::helper('psl_import/importproducts')->processFile($path,$_FILES);//$this->processList($path);
            $string = '';

            if(isset($messages['success'])){
                Mage::getSingleton('adminhtml/session')->addSuccess($messages['success']);
            }

            if(isset($messages['error'])){
                foreach($messages['error'] as $errors){
                    $string .= $errors."<br>";
                }
                Mage::getSingleton('adminhtml/session')->addError($string);
            }

            $this->_redirect('*/*/index');
            return;
        }
    }
}