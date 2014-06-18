<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_ProLabels_Block_Adminhtml_System_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('prolabelsSystemGrid');
        $this->setDefaultSort('rules_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('pro_system_rules_filter');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('prolabels/system')->getCollection();

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }
    
    protected function _afterLoadCollection() {
        $this->getCollection()->walk('afterLoad'); parent::_afterLoadCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('system_id', array(
            'header'    => Mage::helper('prolabels')->__('ID'),
            'align'     =>'right',
            'width'     => '20px',
            'index'     => 'system_id',
            'type'      => 'number'
        ));
        
        $this->addColumn('system_label_name', array(
            'header'    => Mage::helper('prolabels')->__('Name'),
            'align'     =>'left',
            'width'     => '450px',
            'index'     => 'system_label_name'
        ));
        
        $this->addColumn('rules_id', array(
            'header'    => Mage::helper('prolabels')->__('System Label'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'rules_id',
            'type'      => 'options',
            'options'   => array(
                '1' => Mage::helper('prolabels')->__('On Sale'),
                '2' => Mage::helper('prolabels')->__('In Stock'),
                '3' => Mage::helper('prolabels')->__('Is New')
            )
        ));
        
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                            'header'        => Mage::helper('cms')->__('Store View'),
                            'index'         => 'store_id',
                            'type'          => 'store',
                            'store_all'     => true,
                            'store_view'    => true,
                            'sortable'      => false,
                            'filter_condition_callback'
                            => array($this, '_filterStoreCondition'),
            ));
        }
        
        $this->addColumn('l_status', array(
            'header'    => Mage::helper('prolabels')->__('Enabled'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'l_status',
            'type'      => 'options',
            'options'   => array(
                1 => 'Enabled',
                0 => 'Disabled'
            )
        ));
        
        return parent::_prepareColumns();
    }
    
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
    
        $this->getCollection()->addStoreFilter($value);
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getSystemId()));
    }
}