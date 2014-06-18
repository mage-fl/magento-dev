<?php

class TM_NavigationPro_Model_Adminhtml_System_Config_Source_Direction
{
    public function toOptionArray()
    {
        return array(
            array('value'=>'horizontal', 'label'=>Mage::helper('navigationpro')->__('Horizontal')),
            array('value'=>'vertical', 'label'=>Mage::helper('navigationpro')->__('Vertical'))
        );
    }
}
