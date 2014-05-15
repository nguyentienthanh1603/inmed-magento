<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Stores extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_store';
        $this->_blockGroup = 'bubble_fpc';
        $this->_headerText = Mage::helper('bubble_fpc')->__('Manage Store Views');
        parent::__construct();
        $this->_removeButton('add');
        $this->setTemplate('bubble/fpc/widget/grid/container.phtml');
    }

    public function getCreateUrl()
    {
        return '';
    }

    public function getHeaderCssClass()
    {
        return '';
    }
}