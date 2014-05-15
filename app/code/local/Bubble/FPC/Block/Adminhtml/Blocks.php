<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Blocks extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_block';
        $this->_blockGroup = 'bubble_fpc';
        $this->_headerText = Mage::helper('bubble_fpc')->__('Manage Dynamic Blocks');
        $this->_addButtonLabel = Mage::helper('bubble_fpc')->__('Add New Block');
        parent::__construct();
        $this->setTemplate('bubble/fpc/widget/grid/container.phtml');
    }

    public function getCreateUrl()
    {
        return $this->getUrl('*/*/newBlock');
    }

    public function getHeaderCssClass()
    {
        return '';
    }
}