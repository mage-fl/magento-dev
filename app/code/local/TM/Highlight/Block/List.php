<?php
/**
 * This is the part of 'Highlight' module for Magento,
 * which allows easy access to product collection
 * with flexible filters
 *
 * @author Templates-Master
 * @copyright Templates Master www.templates-master.com
 */

class TM_Highlight_Block_List extends Mage_Catalog_Block_Product_List
{
    protected $_collectionBlock = null;

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $this->_productCollection = $this->getCollectionBlock()
                ->getLoadedProductCollection();
        }

        return $this->_productCollection;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function getLoadedProductCollection()
    {
        return $this->_getProductCollection();
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * another block (was problem with search result)
     */
    protected function _beforeToHtml()
    {
        // higlight block applies collection filters in _beforeToHtml method
        $this->getCollectionBlock()->beforeToHtml();

        return parent::_beforeToHtml();
    }

    public function setCollectionBlock(Mage_Catalog_Block_Product_Abstract $block)
    {
        $this->_collectionBlock = $block;
        return $this;
    }

    public function getCollectionBlock()
    {
        if (!$this->_collectionBlock) {
            $type = $this->_data['collection_block_type'];
            $this->_collectionBlock = $this->getLayout()->createBlock($type);
        }
        return $this->_collectionBlock;
    }

    public function getTitle()
    {
        return $this->getCollectionBlock()->getTitle();
    }
}
