<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Dataflow
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Convert history
 *
 * @method Mage_Dataflow_Model_Resource_Profile_History _getResource()
 * @method Mage_Dataflow_Model_Resource_Profile_History getResource()
 * @method int getProfileId()
 * @method Mage_Dataflow_Model_Profile_History setProfileId(int $value)
 * @method string getActionCode()
 * @method Mage_Dataflow_Model_Profile_History setActionCode(string $value)
 * @method int getUserId()
 * @method Mage_Dataflow_Model_Profile_History setUserId(int $value)
 * @method string getPerformedAt()
 * @method Mage_Dataflow_Model_Profile_History setPerformedAt(string $value)
 *
 * @category    Mage
 * @package     Mage_Dataflow
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Dataflow_Model_Profile_History extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('dataflow/profile_history');
    }

    protected function _beforeSave()
    {
        if (!$this->getProfileId()) {
            $profile = Mage::registry('current_convert_profile');
            if ($profile) {
                $this->setProfileId($profile->getId());
            }
        }

        if(!$this->hasData('user_id')) {
            $this->setUserId(0);
        }

        parent::_beforeSave();
        return $this;
    }
}
