<?php
class TM_AskIt_Block_List extends TM_AskIt_Block_List_Abstract
{
    protected  $_actionsParams = array();

    protected function _beforeToHtml()
    {
        $this->_actionsParams['_secure'] = $this->getRequest()->isSecure();
        $this->_actionsParams[Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED] =
                Mage::helper('core/url')->getEncodedUrl();
        return parent::_beforeToHtml();
    }

    public function getQuestionLimit()
    {
//        return Mage::getStoreConfig('askit/general/countQuestionShowOnProductPage');
    }

    public function getNewQuestionFormAction()
    {
        if (!isset($this->_actionsParams['item_id'])
            || !isset($this->_actionsParams['item_type_id'])) {

            return;
        }
        return Mage::getUrl('askit/index/saveQuestion', $this->_actionsParams);
    }

    public function getNewAnswerFormAction($parentId)
    {
        if (empty($parentId)) {
            return;
        }
        $params = $this->_actionsParams;
        $params['parent_id'] = $parentId;
        return Mage::getUrl('askit/index/saveAnswer', $params);
    }
}