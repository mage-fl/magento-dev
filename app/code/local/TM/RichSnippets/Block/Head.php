<?php

class TM_RichSnippets_Block_Head extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        $head = $this->getLayout()->getBlock('head');
        if ($head){
            $head->addLinkRel(
                'author',
                Mage::getStoreConfig('richsnippets/general/author')
            );
        }
    }
}
