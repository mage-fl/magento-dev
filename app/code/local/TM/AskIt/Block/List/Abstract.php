<?php
abstract class TM_AskIt_Block_List_Abstract extends Mage_Core_Block_Template
{
    /**
     *
     * @var TM_AskIt_Model_Mysql4_Item_Collection
     */
    protected $_collection;

    public function getCount()
    {
        return count($this->getItems());
    }

    public function getItems()
    {
        return $this->_getCollection()->getItems();
    }

    protected function _getCollection()
    {
        if (!$this->_collection) {
            $this->_collection = Mage::getModel('askit/item')
                ->getCollection()
                ->addStatusFilter(array(
                    TM_AskIt_Model_Status::STATUS_APROVED/*aprowed*/,
                    TM_AskIt_Model_Status::STATUS_CLOSE/*closed*/
                ))
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addPrivateFilter()
                ->addQuestionFilter()
                ->addQuestionAnswersData()
                ->setorder('created_time','DESC');

            $this->_prepareCollection();

            $limit = (int) $this->getQuestionLimit();
            if ($limit) {
                $this->_collection->getSelect()->limit($limit);
            }
            $this->_collection->load();
        }
        return $this->_collection;
    }

    /**
     *
     * @return bool
     */
    public function isProductViewPage()
    {
        $handles = $this->getLayout()->getUpdate()->getHandles();
        return in_array('catalog_product_view', $handles);
    }

    protected function _prepareCollection()
    {

    }

    public function getQuestionLimit()
    {
        return 10;
    }

    /**
     *
     * @param TM_AskIt_Model_Item $item
     * @return string
     */
    public function getItemViewLink(TM_AskIt_Model_Item $item)
    {
        $item = Mage::helper('askit')->getItem($item);
        return "{$item->getPrefix()} <a href=\"{$item->getFrontendItemUrl()}\">{$item->getName()}</a>";
    }

    public function getHintAction($item, $add = true)
    {
        $params = array(
            'item' => $item,
            'add' => $add ? 1 : -1,
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED =>
                Mage::helper('core/url')->getEncodedUrl()
        );
        return Mage::getUrl('askit/index/saveHint', $params);
    }

    public function getAnswersCountTitle($count)
    {
        return $count < 1 ? Mage::helper('askit')->__('Not Answerred') :
            $count . ' ' . Mage::helper('askit')->__($count > 1 ? 'Answers' : 'Answer');
    }

    public function canVoted($itemId)
    {
        if(!Mage::getSingleton('customer/session')->isLoggedIn()) {
            return false;
        }
        $customerId = (int) Mage::getSingleton('customer/session')->getCustomerId();

        $model = Mage::getModel('askit/vote');
        if ($model->isVoted($itemId, $customerId)) {
            return false;
        }
        return true;
    }
}