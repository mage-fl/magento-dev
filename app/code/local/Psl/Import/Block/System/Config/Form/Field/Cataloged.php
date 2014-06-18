<?php
/**
 * Created by PhpStorm.
 * User: felipe
 * Date: 9/06/14
 * Time: 05:07 PM
 */

class Psl_Import_Block_System_Config_Form_Field_Cataloged extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $magento_block = Mage::getSingleton('core/layout');
        $attributes = $magento_block->createBlock('psl_import/system_renderer_attributes');
        $attributes->setOptions($this->getAllAttributesOptions(false));

        $valueOptions = $magento_block->createBlock('psl_import/system_renderer_values');
        $valueOptions->setOptions(array(array('label'=>'Id','value'=>'id'),array('label'=>'Label','value'=>'label')));
        
        $this->addColumn('remote_attribute', array(
            'label' => Mage::helper('adminhtml')->__('Remote Attribute'),
            'style' => 'width:100px',
        ));

        $this->addColumn('local_attribute', array(
            'label' => Mage::helper('adminhtml')->__('Local Attribute'),
            'style' => 'width:100px',
            'class' => '',
            'renderer' => $attributes
        ));
        
        $this->addColumn('use_value', array(
            'label' => Mage::helper('adminhtml')->__('Use value'),
            'style' => 'width:50px',
            'class' => '',
            'renderer' => $valueOptions
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Option');
        parent::__construct();
    }

    public function getAllAttributesOptions($hasNA = true) {        

        $attributes = Mage::getModel('catalog/product')->getAttributes();
        $attributeArray = array();

        foreach($attributes as $a){

            foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {
                if(!isset($attributeArray[$attributeName])){
                    $attributeArray[$attributeName] = array(
                        'label' => $attributeName,
                        'value' => $attributeName
                    );
                }
            }
        }

        if (!$hasNA) {
            $_options = array_reverse($attributeArray);
            array_pop($_options);
            $_options = array_reverse($attributeArray);
        }

        return $attributeArray;
    }
    
    /**
     * Obtain existing data from form element
     *
     * Each row will be instance of Varien_Object
     *
     * @return array
     */
    public function getArrayRows()
    {		
        $result = array();
        /** @var Varien_Data_Form_Element_Abstract */
        $element = $this->getElement();
        if ($element->getValue() && is_array($element->getValue())) {                        
            foreach ($element->getValue() as $rowId => $row) {
                foreach ($row as $key => $value) {
                    $row[$key] = $this->htmlEscape($value);                                                
                    if ($key == 'local_attribute' || $key == 'use_value') {
                            $row['option_selected_' . $value] = 'selected="selected"';
                    }
                }
                $row['_id'] = $rowId;
                $result[$rowId] = new Varien_Object($row);
                $this->_prepareArrayRow($result[$rowId]);
            }
        }

        $this->_arrayRowsCache = $result;
        return $this->_arrayRowsCache;
    }
}