<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_EasyBanner_Block_Placeholder extends Mage_Core_Block_Template
{
    protected $_filters = array();
    protected $_banners = array();

    public function getTemplate()
    {
        if (!$this->hasData('template')) {
            $this->setData('template', 'tm/easybanner/placeholder.phtml');
        }
        return $this->_getData('template');
    }

    /**
     * Adds banner object to array.
     * Before add, banner is checking with filters
     *
     * @param string $id
     * @param array $filters
     * @return TM_EasyBanner_Block_Placeholder Provides fluent interface
     */
    public function addBanner()
    {
        $args = func_get_args();
        if (count($args)) {
            $this->_filters[array_shift($args)] = $args;
        }
        return $this;
    }

    /**
     * Retrieve filtered and sorted banners
     *
     * @return array
     */
    public function getBanners()
    {
        if (($placeholderId = $this->getPlaceholderId()) && count($this->_filters)) { // from db
            $collection = Mage::getModel('easybanner/banner')->getCollection()
                ->addStatistics()
                ->addFieldToFilter('main_table.identifier', array(
                    'in' => array_keys($this->_filters)
                ));

            $placeholder = Mage::getModel('easybanner/placeholder')
                ->load($placeholderId);
        } else if ($name = $this->getPlaceholderName()) { // inline call for placeholder
            $collection = Mage::getModel('easybanner/banner')
                ->getCollectionByPlaceholderName($name);
            $placeholder = Mage::getModel('easybanner/placeholder')
                ->load($name, 'name');
        } else {
            return array(); // invalid arguments supplied
        }

        foreach ($collection->getItems() as $banner) {
            if (!$banner->isVisible()) {
                continue;
            }
            $this->_banners[$banner->getIdentifier()] = $banner->getData();
        }

        if (!count($this->_banners)) {
            return array();
        }

        uasort($this->_banners, array($this, '_sortBanners'));

        $max = count($this->_banners);
        if ($placeholder->getIsRandomSortMode()) {
            $i = rand(0, $max - 1);
        } else {
            // sort banners according to placeholder offset iterator
            $i = $placeholder->getBannerOffset();
            $i = ($max > $i ? $i : 0);
        }
        $head = array_splice($this->_banners, $i);

        $this->_banners = $head + $this->_banners;

        $placeholder->setDoNotUpdateLayout(true)
            ->setBannerOffset($i + $placeholder->getLimit())
            ->save();

        // limit by placeholder config
        array_splice($this->_banners, $placeholder->getLimit());

        return $this->_banners;
    }

    private function _sortBanners($a, $b)
    {
        if ($a['sort_order'] == $b['sort_order']) {
            return 0;
        }
        return $a['sort_order'] < $b['sort_order'] ? -1 : 1;
    }
}
