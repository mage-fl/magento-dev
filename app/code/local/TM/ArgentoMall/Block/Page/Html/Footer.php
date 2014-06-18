<?php

class TM_ArgentoMall_Block_Page_Html_Footer extends TM_Argento_Block_Page_Html_Footer
{
    /**
     * Disable cache because of recentrly viewed and top 10 searc blocks
     */
    protected function _construct()
    {
        parent::_construct();
        $this->addData(array('cache_lifetime' => null));
    }
}
