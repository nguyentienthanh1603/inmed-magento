<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Store_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpcStoreGrid');
        $this->setDefaultSort('store_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('fpc_store_filter');
        $this->setUseAjax(true);
    }

    public function getId()
    {
        return 'fpc_tabs_stores_section_content';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('core/store')
            ->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('store_id', array(
            'header'         => Mage::helper('bubble_fpc')->__('Store Id'),
            'index'          => 'store_id',
            'type'           => 'number',
        ));

        $this->addColumn('code', array(
            'header'         => Mage::helper('bubble_fpc')->__('Code'),
            'index'          => 'code',
            'type'           => 'text',
        ));

        $this->addColumn('name', array(
            'header'         => Mage::helper('bubble_fpc')->__('Name'),
            'index'          => 'name',
            'type'           => 'text',
        ));

        $this->addColumn('is_cache_active', array(
            'header'         => Mage::helper('bubble_fpc')->__('Cache Status'),
            'filter'         => false,
            'sortable'       => false,
            'width'          => '80px',
            'align'          => 'center',
            'type'           => 'options',
            'options'        => array(
                '1' => Mage::helper('adminhtml')->__('Yes'),
                '0' => Mage::helper('adminhtml')->__('No'),
            ),
            'frame_callback' => array($this, 'decorateStatus'),
        ));

        $this->addColumn('action',
            array(
                'header'     => Mage::helper('adminhtml')->__('Action'),
                'width'      => '100px',
                'align'      => 'center',
                'type'       => 'action',
                'getter'     => 'getId',
                'actions'    => array(
                    array(
                        'caption' => Mage::helper('bubble_fpc')->__('Edit'),
                        'url'     => array(
                            'base' => '*/*/editStore',
                        ),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => Mage::helper('bubble_fpc')->__('Generate'),
                        'url'     => array(
                            'base' => '*/*/generate',
                        ),
                        'field'   => 'store_id',
                        'target'=>	'_blank',
                    ),
                ),
                'filter'     => false,
                'sortable'   => false,
                'renderer'   => 'bubble_fpc/adminhtml_widget_grid_column_renderer_action',
            )
        );

        return parent::_prepareColumns();
    }

    public function decorateStatus($value, $row)
    {
        $isActive = Mage::helper('bubble_fpc')->isStoreCacheable($row->getId());
        $class = $isActive ? 'grid-severity-notice' : 'grid-severity-critical';
        $value = $isActive ? 'Enabled' : 'Disabled';

        return '<span class="' . $class . '"><span>' . $this->helper('bubble_fpc')->__($value) . '</span></span>';
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/editStore', array(
            'id' => $row->getId()
        ));
    }
}