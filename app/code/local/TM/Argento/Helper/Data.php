<?php

class TM_Argento_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function isEnterprise()
    {
        return (bool)Mage::getConfig()->getModuleConfig('Enterprise_Enterprise');
    }

    public function isEnterpriseUsed()
    {
        if (!$this->isEnterprise()) {
            return false;
        }

        $package = Mage::getSingleton('core/design_package');
        $themes  = $package->getTheme('after_default');
        if ($themes && $themes !== $package->getTheme('default')) {
            $themes = explode(',', $themes);
            return in_array('enterprise/default', $themes);
        }
        return false;
    }
}
