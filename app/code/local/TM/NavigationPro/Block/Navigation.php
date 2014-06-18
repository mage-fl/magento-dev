<?php

class TM_NavigationPro_Block_Navigation extends Mage_Page_Block_Html_Topmenu
{
    /**
     * isCurrentlySecure flag added
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $shortCacheId = array(
            'TOPMENU',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
            $this->getCurrentEntityKey(),
            (int)Mage::app()->getStore()->isCurrentlySecure()
        );
        $cacheId = $shortCacheId;

        $shortCacheId = array_values($shortCacheId);
        $shortCacheId = implode('|', $shortCacheId);
        $shortCacheId = md5($shortCacheId);

        $cacheId['entity_key'] = $this->getCurrentEntityKey();
        $cacheId['short_cache_id'] = $shortCacheId;

        return $cacheId;
    }

    /**
     * Retrieve current entity key
     *
     * @return int|string
     */
    public function getCurrentEntityKey()
    {
        if (null === $this->_currentEntityKey) {
            if (Mage::registry('current_entity_key')) {
                $this->_currentEntityKey = Mage::registry('current_entity_key');
            } elseif (Mage::registry('current_category')) {
                $this->_currentEntityKey = Mage::registry('current_category')->getPath();
            } else {
                $this->_currentEntityKey = Mage::app()->getStore()->getRootCategoryId();
            }
        }
        return $this->_currentEntityKey;
    }

    /**
     * Process cached form_key and uenc params
     *
     * @param   string $html
     * @return  string
     */
    protected function _loadCache()
    {
        $cacheData = parent::_loadCache();
        if ($cacheData) {
            $search = array(
                '{{tm_navigationpro uenc}}'
            );
            $replace = array(
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED
                    . '/' . Mage::helper('core/url')->getEncodedUrl()
            );

            if (defined('Mage_Core_Model_Url::FORM_KEY')) {
                $formKey = Mage::getSingleton('core/session')->getFormKey();
                $search = array_merge($search, array(
                    '{{tm_navigationpro form_key_url}}',
                    '{{tm_navigationpro form_key_hidden}}'
                ));
                $replace = array_merge($replace, array(
                    Mage_Core_Model_Url::FORM_KEY . '/' . $formKey,
                    'value="' . $formKey . '"'
                ));
            }

            $cacheData = str_replace($search, $replace, $cacheData);
        }
        return $cacheData;
    }

    /**
     * Replace form_key and uenc with placeholders
     *
     * @param string $data
     * @return Mage_Core_Block_Abstract
     */
    protected function _saveCache($data)
    {
        if (is_null($this->getCacheLifetime())
            || !$this->getMageApp()->useCache(self::CACHE_GROUP)) {

            return false;
        }

        $search = array(
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED
                . '/' . Mage::helper('core/url')->getEncodedUrl()
        );
        $replace = array(
            '{{tm_navigationpro uenc}}'
        );

        if (defined('Mage_Core_Model_Url::FORM_KEY')) {
            $formKey = Mage::getSingleton('core/session')->getFormKey();
            $search = array_merge($search, array(
                Mage_Core_Model_Url::FORM_KEY . '/' . $formKey,
                'value="' . $formKey . '"'
            ));
            $replace = array_merge($replace, array(
                '{{tm_navigationpro form_key_url}}',
                '{{tm_navigationpro form_key_hidden}}'
            ));
        }

        $data = str_replace($search, $replace, $data);
        return parent::_saveCache($data);
    }

    /**
     * EE compatibility
     *
     * @return Mage_Core_Model_App
     */
    public function getMageApp()
    {
        if (method_exists($this, '_getApp')) {
            return $this->_getApp();
        }
        return Mage::app();
    }

    /**
     * Fill the block data with coniguration values
     *
     * @param string $path 'navigationpro/top'
     */
    public function addDataFromConfig($path)
    {
        foreach (Mage::getStoreConfig($path) as $key => $value) {
            $this->setData($key, $value);
        }
        return $this;
    }

    /**
     * Set data using the Magento's configuration
     *
     * @param string $key
     * @param string $path
     * @return TM_NavigationPro_Block_Navigation
     */
    public function setDataFromConfig($key, $path)
    {
        return $this->setData($key, Mage::getStoreConfig($path));
    }

    /**
     * Get top menu html
     * Overriden to check for enabled flag
     *
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @return string
     */
    public function getHtml($outermostClass = '', $childrenWrapClass = '')
    {
        if (!$this->getEnabled() || !$this->getNavigationproMenu()->getId()) {
            return '';
        }

        return parent::getHtml($outermostClass, $childrenWrapClass);
    }

    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Varien_Data_Tree_Node $menuTree
     * @param string $childrenWrapClass
     * @param TM_NavigationPro_Model_Column $columns Used for subcategory rendering
     * @return string
     */
    protected function _getHtml(Varien_Data_Tree_Node $menuTree, $childrenWrapClass, TM_NavigationPro_Model_Column $column = null)
    {
        $html = '';

        $children    = $menuTree->getChildren();
        $parentLevel = $menuTree->getLevel();
        $childLevel  = is_null($parentLevel) ? 0 : $parentLevel + 1;

        $counter = 1;
        $childrenCount = $children->count();

        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass . '-' : 'nav-';

        // @todo render root menu if display_in_navigation

        if ($column) {
            if (!$column->getCurrentColumn()) {
                $column->setCurrentColumn(0);
            }
        }
        foreach ($children as $child) {
            $child->setLevel($childLevel);
            $child->setIsFirst($counter == 1);
            $child->setIsLast($counter == $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix . $counter);

            $aCssClass = 'nav-a';
            $outermostClass = $menuTree->getOutermostClass();

            $menu = $this->getNavigationproMenu($this->_getCategoryId($child));
            if (!$menu->getIsActive()) {
                continue;
            }

            $child->setHasFirstSiblings($this->_hasFirstSibling($menu));
            $child->setHasLastSiblings($this->_hasLastSibling($menu));

            // wrap category items into rows
            $child->setWidth('auto');
            $currentLevel = 1;
            if ($column) {
                $currentLevel  = $column->getCurrentLevel();
                $currentColumn = $column->getCurrentColumn(); // could be changed in siblings
                if ($currentColumn++ % $column->getColumnsCount() == 0) {
                    $html .= "\n" . '<ul class="level' . $childLevel . ' nav-ul nav-row">';
                }
                $child->setClass('nav-li-column');
                $child->setWidth($column->getWidth());
                $column->setCurrentColumn($currentColumn);
                $column->setRowOpened(true);
            }

            if ($childLevel == 0 && $outermostClass) {
                $aCssClass .= ' relative-level' . $currentLevel . ' ' . $outermostClass;
                $child->setClass($outermostClass);
                $menu->setOutermostClass($outermostClass);
            } else {
                $aCssClass .= ' relative-level' . $currentLevel;
            }

            if (!$column) {
                $style = $this->getNavigationproMenu()->getStyle();
            } else {
                $style = $column->getStyle();
            }

            $menu->setChildLevel($childLevel)
                ->setChildrenWrapClass($childrenWrapClass)
                ->setIsLastCategory($child->getIsLast())
                ->setIsFirstCategory($child->getIsFirst())
                ->setSiblingStyle($style);

            // add first siblings
            $html .= $this->_getRenderSiblings(true, $menu, $column);

            // remove subcategory column if no children are found
            // remove layered column if no filters are found
            $this->_prepareMenuColumns($menu, $child);

            $hasDropdown = $menu->getColumns() || $menu->getTop() || $menu->getBottom();
            $drawDropdownWrapper = false;
            if ($hasDropdown) {
                $child->setHasDropdown(true);
                if (!$column) { // top-level
                    $child->setNavigationStyle($style);
                    $levelsPerDropdown = $this->getNavigationproMenu()->getLevelsPerDropdown();
                } else {
                    $child->setNavigationStyle($style);
                    $levelsPerDropdown = $column->getLevelsPerDropdown();
                }

                if ($currentLevel >= $levelsPerDropdown) {
                    $drawDropdownWrapper = true;
                    $currentLevel = 0;
                    $aCssClass .= ' nav-a-with-toggler';
                }
            }

            $html .= "\n" . '<li' . $this->_getRenderedMenuItemAttributes($child) . ' style="'
                . 'width:' . $child->getWidth() . ';'
                . '">';
            $html .= '<a href="' . $child->getUrl() . '" class="'
                . $aCssClass
                . ' nav-a-level' . $child->getLevel()
                . '"><span class="nav-span">'
                . $this->escapeHtml($child->getName()) . '</span></a>';

            // draw dropdown
            if ($hasDropdown) {
                if ($drawDropdownWrapper) {
                    $toggleClassName = 'nav-toggler nav-toggler-level' . $child->getLevel();
                    $toggleClassName .= ' nav-' . $child->getNavigationStyle() . '-toggler';

                    $html .= '<span class="' . $toggleClassName . '">.</span>';
                    $html .= '<div class="nav-dropdown level' . $child->getLevel() . '" style="'
                        . 'width:' . $this->_getDropdownWidth($menu, false, $child) . '; '
                        . '">';
                }

                $processor = Mage::helper('cms')->getBlockTemplateProcessor();
                if ($menu->getTop()) {
                    $html .= '<div class="nav-dropdown-top">';
                    $html .= $processor->filter($menu->getTop());
                    $html .= '</div>';
                }

                if ($drawDropdownWrapper) {
                    $html .= '<div class="nav-dropdown-inner level' . $child->getLevel() . '">';
                }

                $html .= '<div class="nav-column-wrapper nav' . count($menu->getColumns()) . '-cols">';

                foreach ($menu->getColumns() as $_column) {
                    $_column->setCurrentLevel($currentLevel + 1);
                    $_column->setCurrentColumn(0);

                    if (!$drawDropdownWrapper) {
                        // update levels per dropdown, to match parent el settings
                        $_column->setLevelsPerDropdown($levelsPerDropdown);
                        if ($column) {
                            // column can't be wider than parent el, if they are inside one dropdown
                            $_column->setWidth($column->getWidth());
                        }
                    }

                    $methodName = 'get' . ucfirst($_column->getType()) . 'ColumnHtml';
                    $html .= $this->{$methodName}($_column, $child, $childrenWrapClass);
                }

                $html .= '</div>';

                if ($drawDropdownWrapper) {
                    $html .= '</div>'; // end of nav-dropdown-inner
                }

                if ($menu->getBottom()) {
                    $html .= '<div class="nav-dropdown-bottom">';
                    $html .= $processor->filter($menu->getBottom());
                    $html .= '</div>';
                }

                if ($drawDropdownWrapper) {
                    $html .= '</div>'; // end of nav-dropdown
                }
            }
            $html .= '</li>';

            // add last siblings
            $html .= $this->_getRenderSiblings(false, $menu, $column);

            // wrap category items into rows
            if ($column && $column->getRowOpened()
                && (($column->getCurrentColumn() % $column->getColumnsCount() === 0)
                    || $child->getIsLast())) {

                $html .= "\n" . '</ul>';
                $column->setCurrentColumn(0);
                $column->setRowOpened(false);
            }

            $counter++;
        }

        return $html;
    }

    public function getSubcategoryColumnHtml(
        TM_NavigationPro_Model_Column $column,
        Varien_Data_Tree_Node $child,
        $childrenWrapClass)
    {
        $cssId = $column->getCssId();
        $html = '<div class="nav-column ' . $column->getCssClass() .  '" '
            . (!empty($cssId) ? 'id="' . $cssId . '" ' : '')
            . 'style="'
            . 'width: ' . $this->_getColumnWidth($column, false, $child) . '; '
            . $column->getCssStyles()
            . '">';

        if ($child->hasChildren()) {
            if (!empty($childrenWrapClass)) {
                $html .= '<div class="' . $childrenWrapClass
                . ' nav-div">';
            }

            if ($column->getTitle()) {
                $html .= '<div class="nav-column-title">';
                $html .= $column->getTitle();
                $html .= '</div>';
            }

            $html .= $this->_getHtml($child, $childrenWrapClass, $column);

            if (!empty($childrenWrapClass)) {
                $html .= '</div>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    public function getHtmlColumnHtml(TM_NavigationPro_Model_Column $column)
    {
        $html = '<div class="nav-column" '
            . 'style="width: ' . $this->_getColumnWidth($column, false) . '; '
            . $column->getCssStyles()
            . '">';
        $processor = Mage::helper('cms')->getBlockTemplateProcessor();
        $html .= $processor->filter($column->getContent());
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns array of menu item's classes
     * Overloaded to check for siblings, style and additional dropdown availability
     *
     * @param Varien_Data_Tree_Node $item
     * @return array
     */
    protected function _getMenuItemClasses(Varien_Data_Tree_Node $item)
    {
        $classes = array();

        $classes[] = 'level' . $item->getLevel();
        $classes[] = $item->getPositionClass();
        $classes[] = 'nav-li';

        if ($item->getHasDropdown()) {
            $classes[] = 'nav-style-' . $item->getNavigationStyle();
        }

        if ($item->getIsFirst() && !$item->getHasFirstSiblings()) {
            $classes[] = 'first';
        }

        if ($item->getIsActive()) {
            $classes[] = 'active';
        }

        if ($item->getIsLast() && !$item->getHasLastSiblings()) {
            $classes[] = 'last';
        }

        if ($item->getClass()) {
            $classes[] = $item->getClass();
        }

        if ($item->hasChildren() || $item->getHasDropdown()) {
            $classes[] = 'parent';
        }

        return $classes;
    }

    protected function _prepareMenuColumns($menu, Varien_Data_Tree_Node $item)
    {
        $columns = $menu->getColumns();
        foreach ($columns as $i => $column) {
            if (TM_NavigationPro_Model_Column::TYPE_SUBCATEGORY === $column->getType()
                && !$item->hasChildren()) {

                unset($columns[$i]);
            }
        }
        $menu->setColumns($columns);
    }

    /**
     * If categoryId is null - root menu will be returned
     *
     * @param int $categoryId
     * @return TM_NavigationPro_Model_Menu
     */
    public function getNavigationproMenu($categoryId = null)
    {
        if (null === $this->getData('navigationpro_tree')) {
            $tree = Mage::helper('navigationpro')
                ->loadCachedMenuTree($this->getMenuName());

            $this->setData('navigationpro_tree', $tree);
        } else {
            $tree = $this->getData('navigationpro_tree');
        }

        if (null !== $categoryId) {
            $menu = $tree->getItemByColumnValue('category_id', $categoryId);
        } else {
            $menu = $tree->getItemByColumnValue('name', $this->getMenuName());
        }

        if (!$menu) {
            $menu = $this->_getDummyMenu(array('category_id' => $categoryId));
        } else {
            if (!$menu->getSiblings()) {
                $menu->setSiblings(array());
            }
            if (!$menu->getColumns()) {
                $menu->setColumns(array());
            }
        }

        if ($categoryId) {
            $useParentColumns = false;
            if ($menu->getIsMenuColumnsMode() || !$menu->getCategoryId()) {
                $parentMenu = $this->getNavigationproMenu();
                $useParentColumns = true;
            } elseif ($menu->getIsParentColumnsMode()) {
                $parentCategoryId = $this->_getParentCategoryId($menu);
                $parentMenu       = $this->getNavigationproMenu($parentCategoryId);
                $useParentColumns = true;
            }
            if ($useParentColumns) {
                $columns = array();
                foreach ($parentMenu->getColumns() as $column) {
                    $columns[] = clone $column; // need to clone, because of recursive using in _getHtml
                }
                $menu->setColumns($columns);
            }
        }

        return $menu;
    }

    /**
     * Get the dummy menu data, if it's not available from the database
     *
     * @return TM_NavigatopnPro_Model_Menu
     */
    protected function _getDummyMenu($default = array())
    {
        $menu = Mage::getModel('navigationpro/menu');
        $menu->addData(array_merge(
            array(
                'is_active'    => 1,
                'columns_mode' => TM_NavigationPro_Model_Menu::COLUMNS_MODE_DEFAULT,
                'levels_per_dropdown' => 1,
                'siblings'     => array(),
                'columns'      => array()
            ),
            $default
        ));
        return $menu;
    }

    /**
     * Retrieve parent menu and returns its category_id
     *
     * @param TM_NavigationPro_Model_Menu $menu
     * @return int|null
     */
    protected function _getParentCategoryId(TM_NavigationPro_Model_Menu $menu)
    {
        $category = Mage::getModel('catalog/category')->load($menu->getCategoryId());
        $tree = $this->getData('navigationpro_tree');
        $menuCategoryIds = $tree->getColumnValues('category_id');
        $categoryParentIds = $category->getParentIds();
        $parentMenu = false;

        foreach (array_reverse($categoryParentIds) as $categoryId) {
            if (!in_array($categoryId, $menuCategoryIds)) {
                continue;
            }

            $parentMenu = $tree->getItemByColumnValue('category_id', $categoryId);
            if ($parentMenu->getIsCustomColumnsMode()) {
                break;
            }
            if ($parentMenu->getIsMenuColumnsMode()) {
                $parentMenu = $tree->getItemByColumnValue('name', $this->getMenuName());
                break;
            }
        }

        if (!$parentMenu) {
            return null;
        }

        return $parentMenu->getCategoryId();
    }

    public function getStyle()
    {
        return $this->getNavigationproMenu()->getStyle();
    }

    public function getDropdownSide()
    {
        if (null === $this->getData('dropdown_side')) {
            $this->setData('dropdown_side', 'right');
        }
        return $this->getData('dropdown_side');
    }

    public function getHtmlId()
    {
        return str_replace(array('.', ' '), '-', $this->getNameInLayout());
    }

    /**
     * Retrieve valid category_id from Varien_Data_Tree_Node
     *
     * @param Varien_Data_Tree_Node $item
     * @return int
     */
    protected function _getCategoryId(Varien_Data_Tree_Node $item)
    {
        // @see Mage_Catalog_Model_Observer::_addCategoriesToMenu
        return str_replace('category-node-', '', $item->getId());
    }

    /**
     * Get calculated dropdown width
     *
     * @param TM_NavigationPro_Model_Menu $menu
     * @param bool $numeric
     * @param Varien_Data_Tree_Node $menuTree       Used for subcategory column type
     * @return mixed
     */
    protected function _getDropdownWidth(
        TM_NavigationPro_Model_Menu $menu,
        $numeric = false,
        Varien_Data_Tree_Node $menuTree = null)
    {
        $width  = 0;
        $column = null;
        foreach ($menu->getColumns() as $column) {
            $width += $this->_getColumnWidth($column, true, $menuTree);
        }
        if (!$numeric) {
            // use units of the last column
            $colWidth = $this->_getColumnWidth($column, false, $menuTree);
            if (is_numeric($colWidth)) {
                $units = 'px';
            } elseif (strstr($colWidth, '%')) {
                $units = '%';
            } else {
                $units = substr($colWidth, -2);
            }
            $width .= $units;
        }
        return $width;
    }

    /**
     * Get calculated column width
     *
     * @param TM_NavigationPro_Model_Column $column
     * @param bool $numeric
     * @param Varien_Data_Tree_Node $menuTree       Used for subcategory column type
     * @return mixed
     */
    protected function _getColumnWidth(
        TM_NavigationPro_Model_Column $column = null,
        $numeric = false,
        Varien_Data_Tree_Node $menuTree = null)
    {
        if (!$column) {
            return 'auto';
        }
        $width = $column->getData('width');
        if ('auto' === $width || empty($width)) {
            return 'auto';
        }
        if (is_numeric($width)) {
            $width .= 'px';
        }

        if (TM_NavigationPro_Model_Column::TYPE_SUBCATEGORY === $column->getType()) {
            if (strstr($width, '%')) {
                $units = '%';
                $width = substr($width, 0, -1);
            } else {
                $units = substr($width, -2);
                $width = substr($width, 0, -2);
            }

            $columnsCount = $column->getColumnsCount();
            if ($menuTree) {
                $columnsCount = min($columnsCount, $menuTree->getChildren()->count());
            }
            $width = (float)$width * $columnsCount;
            $width .= $units;
        }

        if ($numeric) {
            $width = (float)$width;
        }

        return $width;
    }

    /**
     * @param bool $first First or last siblings?
     * @param TM_NavigationPro_Model_Menu $menu
     */
    protected function _getRenderSiblings(
        $first = true,
        TM_NavigationPro_Model_Menu $menu,
        TM_NavigationPro_Model_Column $column = null)
    {
        $html              = '';
        $childrenWrapClass = $menu->getChildrenWrapClass();
        $childLevel        = $menu->getChildLevel();
        $isLast            = $menu->getIsLastCategory();
        $isFirst           = $menu->getIsFirstCategory();
        $processor         = Mage::helper('cms')->getBlockTemplateProcessor();

        $i = 0;
        $siblings = $menu->getSiblings();
        $siblingsCount = count($siblings);
        if ($column) {
            if (!$first
                && $siblingsCount
                && $column->getRowOpened()
                && $currentColumn % $column->getColumnsCount() == 0) {

                $html .= "\n" . '</ul>' . "\n";
                $column->setRowOpened(false);
                $column->setCurrentColumn(0);
            }
            $currentColumn = $column->getCurrentColumn();
        }
        foreach ($siblings as $sibling) {
            $sortOrder = $sibling->getSortOrder();
            if ($first && $sortOrder >= 0) {
                break;
            } elseif (!$first && $sortOrder < 0) {
                $i++;
                continue;
            }

            // wrap category items into rows
            $siblingWidth = 'auto';
            $siblingClass = 'nav-li nav-li-sibling ' . $menu->getOutermostClass();
            if ($first && $isFirst && $i === 0) {
                $siblingClass .= ' first';
            } elseif (!$first && $isLast && $i + 1 === $siblingsCount) {
                $siblingClass .= ' last';
            }
            $currentLevel = 1;
            if ($column) {
                if (!$first || $i) {
                    $currentColumn++;
                }

                if (!$column->getRowOpened()
                    && (($currentColumn - 1) % $column->getColumnsCount() == 0)) {

                    $html .= "\n" . '<ul class="level' . $childLevel . ' nav-ul nav-row">';
                    $column->setRowOpened(true);
                }
                $siblingClass .= ' nav-li-column';
                $siblingWidth = $column->getWidth();
                $currentLevel = $column->getCurrentLevel();
                $column->setCurrentColumn($currentColumn);
            }

            $titleClass = 'nav-sibling-title ' . $menu->getOutermostClass()
                . ' relative-level' . $currentLevel;
            if ($sibling->getDropdownContent()) {
                $titleClass   .= ' nav-sibling-title-with-toggler';
                $siblingClass .= ' parent';
                $siblingClass .= ' nav-style-' . $menu->getSiblingStyle();
            }

            $rawTitle       = $sibling->getContent();
            $processedTitle = $processor->filter($rawTitle);
            $strippedTitle  = strip_tags($processedTitle, '<span><i><b><s><strong>');
            if (0 === strcmp($processedTitle, $strippedTitle)) {
                $processedTitle = '<a href="javascript:void(0)">' . $processedTitle . '</a>';
            }

            preg_match('/href=["|\'](.+)["|\']/U', $processedTitle, $matches);
            if ($matches && !empty($matches[1]) && $this->_isActiveUrl($matches[1])) {
                $siblingClass .= ' active';
            }

            $html .= "\n" . '<li class="' . $siblingClass .'" style="width:' . $siblingWidth . ';">';
            $html .= '<div class="' . $titleClass . '">' . $processedTitle . '</div>';

            if ($sibling->getDropdownContent()) {
                $toggleClassName = 'nav-toggler nav-toggler-level' . $childLevel;
                $toggleClassName .= ' nav-' . $menu->getSiblingStyle() . '-toggler';
                $html .= '<span class="' . $toggleClassName . '">.</span>';

                // if (!empty($childrenWrapClass)) {
                //     $html .= '<div class="' . $childrenWrapClass . ' nav-div">';
                // }

                $content = $processor->filter($sibling->getDropdownContent());
                $html .= '<div class="level' . $childLevel . ' nav-dropdown" style="'
                    . $sibling->getDropdownStyles() . ';'
                    . '"><div class="nav-dropdown-inner ' . 'level' . $childLevel . '">';
                $html .= $content;
                $html .= '</div></div>';

                // if (!empty($childrenWrapClass)) {
                //     $html .= '</div>';
                // }
            }
            $html .= '</li>';

            // wrap category items into rows
            $i++;
            if ($column && $column->getRowOpened()
                && $currentColumn % $column->getColumnsCount() === 0) {

                $html .= "\n" . '</ul>';
                $column->setRowOpened(false);
                $column->setCurrentColumn(0);
            }
        }
        return $html;
    }

    /**
     * @param bool $first First or last siblings?
     * @param TM_NavigationPro_Model_Menu $menu
     * @return boolean
     */
    protected function _hasSibling(
        $first = true,
        TM_NavigationPro_Model_Menu $menu)
    {
        foreach ($menu->getSiblings() as $sibling) {
            $sortOrder = $sibling->getSortOrder();
            if ($first) {
                // siblings are ordered by sort order
                // if first element is above 0 - all next siblings will be above 0 too
                return $sortOrder < 0;
            } elseif ($sortOrder >= 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check is the first sibling is available
     *
     * @param TM_NavigationPro_Model_Menu $menu
     * @return boolean
     */
    protected function _hasFirstSibling(TM_NavigationPro_Model_Menu $menu)
    {
        return $this->_hasSibling(true, $menu);
    }

    /**
     * Check is the last sibling is available
     *
     * @param TM_NavigationPro_Model_Menu $menu
     * @return boolean
     */
    protected function _hasLastSibling(TM_NavigationPro_Model_Menu $menu)
    {
        return $this->_hasSibling(false, $menu);
    }

    /**
     * Detects, is the supplied url is currenlty active
     *
     * @param string $url
     * @return boolean
     */
    protected function _isActiveUrl($url)
    {
        $url = trim($url, '/');
        $currentUrl = trim(Mage::helper('core/url')->getCurrentUrl(), '/');

        if (0 === strcmp($currentUrl, $url)) {
            return true;
        }
        return false;
    }
}
