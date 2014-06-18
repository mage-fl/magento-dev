<?php

class Psl_Import_Block_Adminhtml_Importproducts_Form extends Mage_Adminhtml_Block_Widget_Form
{
      protected function _prepareForm()
      {
          $form = new Varien_Data_Form(array(
                'id' => 'importproducts_form',
                'action' => $this->getUrl('*/*/processproductsfile'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
                )
            );
          $form->setUseContainer(true);

          $this->setForm($form);
          $fieldset = $form->addFieldset('upload_form_field', array('legend'=>Mage::helper('psl_import')->__('Upload And Process file')));

          $fieldset->addField('filelist', 'file', array(
              'label'     => Mage::helper('psl_import')->__('File'),
              'value'     => 'Upload',
              'class'     => 'required-entry',
              'required'  => true,
              'name'      => 'file'
          ));
                              
          return parent::_prepareForm();
      }
}