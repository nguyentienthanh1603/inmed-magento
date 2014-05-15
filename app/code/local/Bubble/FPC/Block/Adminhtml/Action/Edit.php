<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Action_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'bubble_fpc';
        $this->_controller = 'adminhtml_action';

        $action = Mage::registry('fpc_action');

        $this->_updateButton('save', 'label', Mage::helper('bubble_fpc')->__('Save Action'));

        if ($action) {
            if ($action->getIsDeletable()) {
                $this->_updateButton('delete', 'label', Mage::helper('bubble_fpc')->__('Delete Action'));
            } else {
                $this->_removeButton('delete');
            }
        }
    }

    public function getHeaderText()
    {
        $action = Mage::registry('fpc_action');
        if ($action && $action->getId() ) {
            return Mage::helper('bubble_fpc')->__("Edit Action '%s'", $this->escapeHtml($action->getName()));
        } else {
            return Mage::helper('bubble_fpc')->__('New Action');
        }
    }

    public function getHeaderCssClass()
    {
        return '';
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/saveAction');
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/deleteAction', array($this->_objectId => $this->getRequest()->getParam($this->_objectId)));
    }
}
