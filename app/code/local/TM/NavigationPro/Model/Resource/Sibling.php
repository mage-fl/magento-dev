<?php

class TM_NavigationPro_Model_Resource_Sibling extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('navigationpro/sibling', 'sibling_id');
    }

    /**
     * Assign sibling content to store views
     *
     * @param Mage_Core_Model_Abstract $object
     * @return TM_NavigationPro_Model_Resource_Sibling
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $table = $this->getTable('navigationpro/sibling_content');
        $where = array(
            'sibling_id = ?' => (int) $object->getId(),
            'store_id = ?'   => (int) $object->getStoreId()
        );
        $this->_getWriteAdapter()->delete($table, $where);

        if (null !== $object->getContent() || null !== $object->getDropdownContent()) {
            $this->_getWriteAdapter()->insert($table, array(
                'sibling_id'        => (int) $object->getId(),
                'store_id'         => (int) $object->getStoreId(),
                'content'          => $object->getContent(),
                'dropdown_content' => $object->getDropdownContent()
            ));
        }

        return parent::_afterSave($object);
    }
}
