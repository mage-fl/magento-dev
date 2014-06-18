<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_EasyBanner_Model_Banner extends Mage_Rule_Model_Rule
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('easybanner/banner');
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('easybanner/rule_condition_combine');
    }

    /**
     * Return true if banner status = 1
     * and banner linked to active placeholder
     *
     * @return boolean
     */
    public function isActive()
    {
        if ($this->getStatus()/* && count($this->getPlaceholderIds(true))*/) {
            return true;
        }
        return false;
    }

    public function getPlaceholderIds($isActive = false)
    {
        $key = $isActive ? 'placeholder_ids_active' : 'placeholder_ids';
        $ids = $this->_getData($key);
        if (null === $ids) {
            $this->_getResource()->loadPlaceholderIds($this, $isActive);
            $ids = $this->_getData($key);
        }
        return $ids;
    }

    public function getStoreIds()
    {
        $ids = $this->_getData('store_ids');
        if (null === $ids) {
            $this->_getResource()->loadStoreIds($this);
            $ids = $this->_getData('store_ids');
        }
        return $ids;
    }

    public function getClicksCount()
    {
        return $this->getStatistics('clicks_count');
    }

    public function getDisplayCount()
    {
        return $this->getStatistics('display_count');
    }

    public function getStatistics($key)
    {
        $stat = $this->_getData($key);
        if (null === $stat) {
            $this->_getResource()->loadStatistics($this);
            $stat = $this->_getData($key);
        }
        return $stat;
    }

    /**
     * Checks is banner is active for requested store
     * Used to check is it possible to click on banner
     *
     * @param int $store
     * @return mixed int|boolean
     */
    public function check($store)
    {
        return $this->isActive() && (in_array($store, $this->getStoreIds()) || in_array(0, $this->getStoreIds()));
    }

    public function duplicate()
    {
        $newBanner = Mage::getModel('easybanner/banner')->setData($this->getData())
            ->setIsDuplicate(true)
            // ->setOriginalId($this->getId())
            ->setIdentifier($this->getIdentifier() . '_duplicate')
            ->setId(null)
            ->setStoreIds($this->getStoreIds())
            ->setPlaceholderIds($this->getPlaceholderIds())
            ->setConditions($this->getConditions());

        $newBanner->save();
        return $newBanner;
    }

    /**
     * @param string $name
     * @return TM_EasyBanner_Model_Mysql4_Banner_Collection
     */
    public function getCollectionByPlaceholderName($name)
    {
        /**
         * @var TM_EasyBanner_Model_Mysql4_Banner_Collection
         */
        $collection = $this->getCollection();
        $collection->addStatistics()
            ->joinLeft(
                'banner_placeholder',
                'banner_placeholder.banner_id = main_table.banner_id',
                ''
            )
            ->joinLeft(
                'placeholder',
                'placeholder.placeholder_id = banner_placeholder.placeholder_id',
                ''
            )
//            ->joinLeft(
//                'banner_store',
//                'banner_store.banner_id = main_table.banner_id',
//                ''
//            )
//            ->addFieldToFilter('banner_store.store_id', array(
//                'in' => array(0, (int)Mage::app()->getStore()->getId())
//            ))
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('placeholder', $name);

        return $collection;
    }

    /**
     * Checks all conditions of the banner
     *
     * @return bool
     */
    public function isVisible()
    {
        if (!$this->getStatus()
            || (!in_array(Mage::app()->getStore()->getId(), $this->getStoreIds())
                && !in_array(0, $this->getStoreIds()))) { // all stores

            return false;
        }

        $conditions = array(unserialize($this->getConditionsSerialized()));
        if (!$this->_validateConditions($conditions)) {
            return false;
        }

        return true;
    }

    protected function _validateConditions($filters, $aggregator = null, $value = null)
    {
        $result = true;
        foreach ($filters as $filter) {
            if (isset($filter['aggregator'])) {
                if (empty($filter['conditions'])) {
                    break;
                }
                foreach ($filter['conditions'] as $condition) {
                    $result = $this->_validateConditions(array($condition), $filter['aggregator'], $filter['value']);

                    if (($filter['aggregator'] == 'all' && $filter['value'] == '1' && !$result)
                        || ($filter['aggregator'] == 'any' && $filter['value'] == '1' && $result)) {

                        break 2;
                    } elseif (($filter['aggregator'] == 'all' && $filter['value'] == '0' && $result)
                        || ($filter['aggregator'] == 'any' && $filter['value'] == '0' && !$result)) {

                        $result = !$result;
                        break 2;
                    }
                }
            } else {
                switch($filter['attribute']) {
                    case 'category_ids':
                        if ($category = Mage::registry('current_category')) {
                            $comparator = $category->getId();
                        } else {
                            $comparator = null;
                        }
                        break;
                    case 'product_ids':
                        if ($product = Mage::registry('current_product')) {
                            $comparator = $product->getId();
                        } else {
                            $comparator = null;
                        }
                        break;
                    case 'date': case 'time':
                        $filter['value'] = strtotime($filter['value']);
                        $date = Mage::app()->getLocale()->date(time());
                        $date->setHour(0)
                            ->setMinute(0)
                            ->setSecond(0)
                            ->setMilliSecond(0);
                        $comparator = $date->get(Zend_Date::TIMESTAMP) + $date->get(Zend_Date::TIMEZONE_SECS);
                        unset($date);
                        break;
                    case 'handle':
                        $comparator = Mage::getSingleton('core/layout')->getUpdate()->getHandles();
                        break;
                    case 'clicks_count':
                        $comparator = $this->getClicksCount();
                        break;
                    case 'display_count':
                        $comparator = $this->getDisplayCount();
                        break;
                    case 'customer_group':
                        $comparator = Mage::getSingleton('customer/session')->getCustomerGroupId();
                        break;
                    default:
                        return false;
                }
                $result = $this->_compareCondition($filter['value'], $comparator, $filter['operator']);
            }
        }
        return $result;
    }

    protected function _compareCondition($v1, $v2, $op)
    {
        if ($op=='()' || $op=='!()' || $op=='!=' || $op=='==' || $op=='{}' || $op=='!{}') {
            $v1 = explode(',', $v1);
            foreach ($v1 as &$v) {
                $v = trim($v);
            }
            if (!is_array($v2)) {
                $v2 = array($v2);
            }
        }

        $result = false;

        switch ($op) {
            case '==': case '!=':
                if (is_array($v1)) {
                    if (is_array($v2)) {
                        $result = array_diff($v2, $v1);
                        $result = empty($result) && (sizeof($v2) == sizeof($v1));
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($v2)) {
                        $result = in_array($v1, $v2);
                    } else {
                        $result = $v2==$v1;
                    }
                }
                break;

            case '<=': case '>':
                if (is_array($v2)) {
                    $result = false;
                } else {
                    $result = $v2<=$v1;
                }
                break;

            case '>=': case '<':
                if (is_array($v2)) {
                    $result = false;
                } else {
                    $result = $v2>=$v1;
                }
                break;

            case '{}': case '!{}':
                if (is_array($v1)) {
                    if (is_array($v2)) {
                        $result = array_diff($v1, $v2);
                        $result = empty($result);
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($v2)) {
                        $result = false;
                    } else {
                        $result = stripos((string)$v2, (string)$v1)!==false;
                    }
                }
                break;

            case '()': case '!()':
                if (is_array($v2)) {
                    $result = count(array_intersect($v2, (array)$v1)) > 0;
                } else {
                    $result = in_array($v2, (array)$v1);
                }
                break;
        }

        if ('!='==$op || '>'==$op || '<'==$op || '!{}'==$op || '!()'==$op) {
            $result = !$result;
        }

        return $result;
    }
}
