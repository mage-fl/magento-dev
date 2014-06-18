<?php

class TM_AskIt_Model_Observer
{
    public function sendAdminNotification(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('askit/email/enableAdminNotification')) {
            return $this;
        }
        $question = $observer->getEvent()->getDataObject();
        $storeId = $question->getStoreId();
//        $parentId = $question->getParentId();
//        $_answer = $question->getData('new_answer_text');
//        if (!empty($parentId) || $ques    tion->getText() != $_answer) {
//            return $this;
//        }
        $data = new Varien_Object();
        $questionHref = Mage::getSingleton('adminhtml/url')->getUrl(
            'askit_admin/adminhtml_askIt/edit',
            array('id' => $question->getId())
        );
        $item = Mage::helper('askit')->getItem($question);

        if (null == $question->getParentId()) {
            $subject = 'New %ss question was posted : %s';
        } else {
            $subject = '%ss question was updated : %s';
        }
        $subject = Mage::helper('askit')->__(
            $subject, $item->getPrefix(), $item->getName()
        );

        $data
            ->setSubject($subject)
            ->setQuestionhref($questionHref)
            ->setItemhref($item->getBackendItemUrl())
            ->setCustomerName($question->getCustomerName())
            ->setEmail($question->getEmail())
            ->setQuestion($question->getText())
            ->setItemname($item->getName())
            ->setStoreId($storeId)
        ;
        $adminEmail = Mage::getStoreConfig('askit/email/admin_email', $storeId);
        if (empty($adminEmail)) {
            throw new Mage_Exception(
                '\'Send admin notification to\' store config must be not empty'
            );
        }
        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->setReplyTo($data->getEmail())
            ->sendTransactional(
                Mage::getStoreConfig('askit/email/admin_template', $storeId),
                Mage::getStoreConfig('askit/email/sender', $storeId),
                $adminEmail,
                null,
                array('data' => $data)
        );
    }

    public function sendCustomerNotification(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('askit/email/enableCustomerNotification')) {
            return $this;
        }
        $answer = $observer->getEvent()->getDataObject();

        $parentId = $answer->getParentId();
        if (empty($parentId)) {
            return $this;
        }
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        if (!$adminUser) {
            return $this;
        }
        $data = new Varien_Object();
        $question = Mage::getModel('askit/item')->load($answer->getParentId());
        $storeId = $question->getStoreId();

        $item = Mage::helper('askit')->getItem($answer);

        $data->setName($question->getCustomerName())
            ->setItemName($item->getName())
            ->setItemUrl($item->getFrontendItemUrl())
            ->setQuestion($question->getText())
            ->setAnswer($answer->getText())
            ->setAdminUserEmail($adminUser->getEmail())
            ->setCustomerEmail($question->getEmail())
            ->setStoreId($storeId);

        $mailTemplate = Mage::getModel('core/email_template');

        $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
            ->setReplyTo($data->getAdminUserEmail())
            ->sendTransactional(
                    Mage::getStoreConfig('askit/email/customer_template', $storeId),
                    Mage::getStoreConfig('askit/email/sender', $storeId),
                    $data->getCustomerEmail(),
                    //Mage::getStoreConfig('askit/email/admin_email'),
                    null,
                    array('data' => $data)
            );
//        $mailTemplate->getSentSuccess();
    }
}