<?php

class TM_ProLabels_Model_Observer
{
    public function autoReindexLabels($observer)
    {
        $model = Mage::getResourceModel('prolabels/label');
        $model->deleteAllLabelIndex();
        $model->applyAll();

        return $this;
    }
}