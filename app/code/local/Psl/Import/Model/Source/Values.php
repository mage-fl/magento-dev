<?php

class Psl_Import_Model_Source_Values{
    
    public function toOptionArray()
    {
        return array(
            array('value' => 'id', 'label' => 'Ids'),
            array('value' => 'label', 'label' => 'Labels')
        );
    }
}
?>
