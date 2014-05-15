<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Block_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpcBlockGrid');
        $this->setDefaultSort('block_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('fpc_block_filter');
        $this->setUseAjax(true);
    }

    public function getId()
    {
        return 'fpc_tabs_blocks_section_content';
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('bubble_fpc/block')
            ->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('block_id', array(
            'header'         => Mage::helper('bubble_fpc')->__('Block Id'),
            'index'          => 'block_id',
            'type'           => 'number',
        ));

        $this->addColumn('model', array(
            'header'         => Mage::helper('bubble_fpc')->__('Model'),
            'index'          => 'model',
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

        $this->addColumn('is_valid', array(
            'header'         => Mage::helper('bubble_fpc')->__('Is Valid'),
            'filter'         => false,
            'sortable'       => false,
            'width'          => '80px',
            'align'          => 'center',
            'frame_callback' => array($this, 'decorateValid'),
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
                            'base' => '*/*/editBlock',
                        ),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => Mage::helper('bubble_fpc')->__('Clear'),
                        'url'     => array(
                            'base'   => '*/*/clearBlock',
                        ),
                        'field'   => 'id',
                        'confirm' => Mage::helper('bubble_fpc')->__('This will clear the cache of this block. Are you sure?')
                    )
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

    public function decorateValid($value, $row)
    {
        $model = Mage::helper('bubble_fpc')->getBlockModel($row->getModel());
        $class = $model ? 'grid-severity-notice' : 'grid-severity-critical';
        $value = $model ? 'Valid' : 'Invalid';

        return '<span class="' . $class . '"><span>' . $this->helper('bubble_fpc')->__($value) . '</span></span>';
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/editBlock', array(
            'id' => $row->getId()
        ));
    }
}