<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_ProLabels_Block_Adminhtml_Rules_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('prolabels_rules');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('rules_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('prolabels')->__('General Information'), 'class' => 'fieldset-wide'));
        $this->_addElementTypes($fieldset); //register own image element

        if ($model->getRulesId()) {
            $fieldset->addField('rules_id', 'hidden', array(
                'name' => 'rules_id',
            ));
        }

        $fieldset->addField('label_name', 'text', array(
            'name'      => 'label_name',
            'label'     => Mage::helper('prolabels')->__('Name'),
            'title'     => Mage::helper('prolabels')->__('Name'),
            'required'  => true
        ));

        $fieldset->addField('store_ids', 'multiselect', array(
            'name'      => 'store_ids',
            'label'     => Mage::helper('prolabels')->__('Store View'),
            'title'     => Mage::helper('prolabels')->__('Store View'),
            'required'  => true,
            'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            'value'     => $model->getStoreIds()
        ));
        
        $fieldset->addField('system_label', 'select', array(
            'label'     => Mage::helper('prolabels')->__('System'),
            'title'     => Mage::helper('prolabels')->__('System'),
            'name'      => 'system_label',
            'disabled'  => true,
            'required'  => true,
            'options'   => array(
                '0' => Mage::helper('prolabels')->__('No'),
                '1' => Mage::helper('prolabels')->__('Yes')
            )
        ));
        
        $fieldset->addField('label_status', 'select', array(
            'label'     => Mage::helper('prolabels')->__('Status'),
            'title'     => Mage::helper('prolabels')->__('Status'),
            'name'      => 'label_status',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('prolabels')->__('Enabled'),
                '0' => Mage::helper('prolabels')->__('Disabled')
            )
        ));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getAdditionalElementTypes()
    {
        return array(
            'image' => Mage::getConfig()->getBlockClassName('prolabels/adminhtml_rules_helper_image')
        );
    }

}
