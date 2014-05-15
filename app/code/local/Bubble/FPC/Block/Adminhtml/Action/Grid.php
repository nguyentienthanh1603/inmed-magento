<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Action_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpcActionGrid');
        $this->setDefaultSort('action_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('fpc_action_filter');
        $this->setUseAjax(true);
    }

    public function getId()
    {
        return 'fpc_tabs_actions_section_content';
    }

    public function canDisplayContainer()
    {
        return true;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('bubble_fpc/action')
            ->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('action_id', array(
            'header'         => Mage::helper('bubble_fpc')->__('Action Id'),
            'index'          => 'action_id',
            'type'           => 'number',
        ));

        $this->addColumn('name', array(
            'header'         => Mage::helper('bubble_fpc')->__('Name'),
            'index'          => 'name',
            'type'           => 'text',
        ));

        $this->addColumn('is_active', array(
            'header'         => Mage::helper('bubble_fpc')->__('Is Active'),
            'index'          => 'is_active',
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
                'width'      => '50px',
                'align'      => 'center',
                'type'       => 'action',
                'getter'     => 'getId',
                'actions'    => array(
                    array(
                        'caption' => Mage::helper('bubble_fpc')->__('Edit'),
                        'url'     => array(
                            'base' => '*/*/editAction',
                        ),
                        'field'   => 'id',
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
        $class = $row->getIsActive() ? 'grid-severity-notice' : 'grid-severity-critical';
        $value = $row->getIsActive() ? 'Enabled' : 'Disabled';

        return '<span class="' . $class . '"><span>' . $this->helper('bubble_fpc')->__($value) . '</span></span>';
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/editAction', array(
            'id' => $row->getId()
        ));
    }
}