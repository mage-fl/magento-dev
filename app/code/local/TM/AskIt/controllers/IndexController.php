<?php
class TM_AskIt_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();

        $block = $this->getLayout()->getBlock('head');
        $title = $this->__('Store discussion');
        $block->setTitle($title)
            ->setKeywords("discussion")
            ->setDescription($title)
        ;
        $this->renderLayout();
    }

    public function productAction()
    {
        $product = Mage::registry('current_product');
        $this->loadLayout();

        if ($product) {
            $block = $this->getLayout()->getBlock('head');
            $title = $this->__('Product Questions – %s', $product->getName());
            $block->setTitle($title)
//                ->setKeywords("your, keywords, anything")
                ->setDescription($title)
            ;
        }
        $this->renderLayout();
    }

    public function categoryAction()
    {
        $category = Mage::registry('current_category');
        $this->loadLayout();

        if ($category) {
            $block = $this->getLayout()->getBlock('head');
            $title = $this->__('Category Questions – %s', $category->getName());
            $block->setTitle($title)
//                ->setKeywords("your, keywords, anything")
                ->setDescription($title)
            ;
        }
        $this->renderLayout();
    }

    public function pageAction()
    {
        $this->loadLayout();

        $pageId = $this->getRequest()->getParam('page_id');
        $page = Mage::getModel('cms/page')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($pageId);

        if ($page) {
            $block = $this->getLayout()->getBlock('head');
            $title = $this->__('Cms Page Questions – %s', $page->getTitle());
            $block->setTitle($title)
//                ->setKeywords("your, keywords, anything")
                ->setDescription($title)
            ;
        }
        $this->renderLayout();
    }

    public function saveQuestionAction()
    {
        $author = (string) $this->getRequest()->getParam('askitCustomer');
        $email = (string) $this->getRequest()->getParam('askitEmail');
        if (!$author || !$email) {
            Mage::getSingleton('core/session')->addError('Email and Name required');
            $this->_redirectReferer();
            return;
        }

        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        if (!$isLoggedIn && !Mage::getStoreConfig('askit/general/allowedGuestQuestion')) {
            Mage::getSingleton('core/session')->addError('Your must login');
            $this->_redirectReferer();
            return;
        }

        $text  = (string) $this->getRequest()->getParam('text');

        if (Mage::getStoreConfig('askit/general/enableAkismet') &&
            Mage::getModel('akismet/service') &&
            Mage::getModel('akismet/service')->isSpam($author, $email, $text)) {

            $this->_redirectReferer();
            return;
        }
        $itemId     = (int) $this->getRequest()->getParam('item_id');
        $itemTypeId = (int) $this->getRequest()->getParam('item_type_id');
        $isPrivate  = false;

        $model = Mage::getModel('askit/item');
        if($isLoggedIn) {
            $model->setCustomerId(
                Mage::getSingleton('customer/session')->getCustomerId()
            );
            $isPrivate = (bool) $this->getRequest()->getParam('askitPrivate', 0);
        }
        $defaultQuestionStatus = Mage::getStoreConfig('askit/general/defaultQuestionStatus');//pending
        $model
            ->setText($text)
            ->setItemTypeId($itemTypeId)
            ->setItemId($itemId)
            ->setStoreId(Mage::app()->getStore()->getId())
            ->setHint(0)
            ->setParentId(null)
            ->setCustomerName($author)
            ->setEmail($email)
            ->setCreatedTime(now())
            ->setUpdateTime(now())
            ->setStatus($defaultQuestionStatus)
            ->setPrivate($isPrivate)
            ->save()
            ;

        /* Now send email to admin about new question */
        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('askit')->__('Your question has been accepted for moderation')
        );
        Mage::dispatchEvent('askit_item_admin_notify_save_after', array(
            'data_object' => $model
        ));

        $this->_redirectReferer();
    }

    public function saveAnswerAction()
    {
        $isAllowedGuestAnswer = Mage::getStoreConfig('askit/general/allowedGuestAnswer');
        if (!$isAllowedGuestAnswer) {
            if(!Mage::getSingleton('customer/session')->authenticate($this)) {
                Mage::getSingleton('core/session')->addError(
                    Mage::helper('askit')->__(
                        'Sorry, only logged in customer can add self answer.'
                    )
                );
                $this->_redirectReferer();
                return;
            }
        }
        $customerName = (string) $this->getRequest()->getParam('askitCustomer');
        $email = (string) $this->getRequest()->getParam('askitEmail');
        if (!$customerName
            || !$email
            || !Mage::getStoreConfig('askit/general/allowedCustomerAnswer')) {

            $this->_redirectReferer();
            return;
        }

        $answer     = (string) $this->getRequest()->getParam('text');
        $questionId = (string) $this->getRequest()->getParam('parent_id');
        $question   = Mage::getModel('askit/item')->load($questionId);

        $itemId     = $question->getItemId();//(int) $this->getRequest()->getParam('product');
        $itemTypeId = $question->getItemTypeId();//TM_AskIt_Model_Item_Type::PRODUCT_ID;
        $customerId = (int) Mage::getSingleton('customer/session')->getCustomerId();
        if (0 === $customerId) {
            $customerId = null;
        }
        $model = Mage::getModel('askit/item');

        $storeId = $question->getStoreId();
        $defaultAnswerStatus = Mage::getStoreConfig('askit/general/defaultAnswerStatus');//pending
        $model
            ->setText(strip_tags($answer))
            ->setStoreId($storeId)
            ->setItemTypeId($itemTypeId)
            ->setItemId($itemId)
            ->setCustomerId($customerId)
            ->setHint(0)
            ->setParentId($questionId)
            ->setCustomerName($customerName)
            ->setEmail($email)
            ->setCreatedTime(now())
            ->setUpdateTime(now())
            ->setStatus($defaultAnswerStatus)
            ->save();

        Mage::dispatchEvent('askit_item_admin_notify_save_after', array(
            'data_object' => $model
        ));

        Mage::getSingleton('core/session')->addSuccess(
            Mage::helper('askit')->__('Your answer has been accepted')
        );
        $this->_redirectReferer();
    }

    public function saveHintAction()
    {
        if(!Mage::getSingleton('customer/session')->authenticate($this)) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('askit')->__('Sorry, only logged in customer can hint.')
            );
            $this->_redirectReferer();
        }

        $itemId = (string) $this->getRequest()->getParam('item');
        $customerId = (int) Mage::getSingleton('customer/session')->getCustomerId();

        $vote = Mage::getModel('askit/vote');

        if ($vote->isVoted($itemId, $customerId)) {
            Mage::getSingleton('core/session')->addError(
                 Mage::helper('askit')->__('Sorry, already voted')
            );
            $this->_redirectReferer();
        }
        $add = (int) $this->getRequest()->getParam('add');
        $add = $add > 0 ? 1 : -1;

        $item = Mage::getModel('askit/item')->load($itemId);
        $item->setHint($item->getHint() + $add);
        $item->save();

        $vote->setData(array(
            'item_id' => $itemId,
            'customer_id' => $customerId,
        ));
        $vote->save();
        $this->_redirectReferer();
    }

    public function rssAction()
    {
        $this->getResponse()->setHeader('Content-type', 'text/xml; charset=UTF-8');
        $this->loadLayout(false);
        $this->renderLayout();
    }
}