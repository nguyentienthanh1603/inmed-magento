<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Block_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpc_block_form');
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'block'     => $this->getData('block'),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('bubble_fpc')->__('Block Information')
        ));

        $block = Mage::registry('fpc_block');

        if ($block && $block->getId()) {
            $fieldset->addField('block_id', 'hidden', array(
                'name' => 'block_id',
            ));
        }

        $fieldset->addField('model', 'text',
            array(
                'name'      => 'model',
                'note'      => Mage::helper('bubble_fpc')->__('Specify the model that should be used for handling this dynamic block. Mage::getSingleton() will be used.'),
                'label'     => Mage::helper('bubble_fpc')->__('Model'),
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

        if ($block) {
            $form->addValues($block->getData());
        }
        $form->setAction($this->getUrl('*/*/saveBlock'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}