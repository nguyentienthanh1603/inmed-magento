<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Action_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpc_action_form');
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getData('action'),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('bubble_fpc')->__('Action Information')
        ));

        $action = Mage::registry('fpc_action');

        if ($action && $action->getId()) {
            $fieldset->addField('action_id', 'hidden', array(
                'name' => 'action_id',
            ));
        }

        $fieldset->addField('name', 'text',
            array(
                'name'      => 'name',
                'label'     => Mage::helper('bubble_fpc')->__('Action Name'),
                'note'      => $this->escapeHtml(Mage::helper('bubble_fpc')->__('Specify the page as <module>/<controller>/<action>. For example: catalog/product/view')),
                'class'     => 'required-entry',
                'required'  => true,
            )
        );

        $fieldset->addField('is_active', 'select',
            array(
                'name'      => 'is_active',
                'label'     => Mage::helper('bubble_fpc')->__('Is Active'),
                'class'     => 'required-entry',
                'required'  => true,
                'values'    => array(
                    '1' => Mage::helper('adminhtml')->__('Yes'),
                    '0' => Mage::helper('adminhtml')->__('No'),
                ),
            )
        );

        if ($action) {
            $form->addValues($action->getData());
        }
        $form->setAction($this->getUrl('*/*/saveAction'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}