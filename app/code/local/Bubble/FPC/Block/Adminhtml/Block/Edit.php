<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Block_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'bubble_fpc';
        $this->_controller = 'adminhtml_block';

        $block = Mage::registry('fpc_block');

        $this->_updateButton('save', 'label', Mage::helper('bubble_fpc')->__('Save Block'));

        if ($block) {
            if ($block->getIsDeletable()) {
                $this->_updateButton('delete', 'label', Mage::helper('bubble_fpc')->__('Delete Block'));
            } else {
                $this->_removeButton('delete');
            }
        }
    }

    public function getHeaderText()
    {
        $block = Mage::registry('fpc_block');
        if ($block && $block->getId() ) {
            return Mage::helper('bubble_fpc')->__("Edit Block '%s'", $this->escapeHtml($block->getModel()));
        } else {
            return Mage::helper('bubble_fpc')->__('New Block');
        }
    }

    public function getHeaderCssClass()
    {
        return '';
    }

    public function getFormBlockUrl()
    {
        return $this->getUrl('*/*/saveBlock');
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/deleteBlock', array($this->_objectId => $this->getRequest()->getParam($this->_objectId)));
    }
}
