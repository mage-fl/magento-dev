<?php
class TM_AskIt_Model_Item extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('askit/item');
    }

    /**
     *
     * @return mixed
     */
    public function getItem()
    {
        $_item = Mage::helper('askit')->getItem($this->getData());
        return $_item->getRawItem();
    }
}