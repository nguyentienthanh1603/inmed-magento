<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Store_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpc_store_form');
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'store'     => $this->getData('store'),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend'    => Mage::helper('bubble_fpc')->__('Store View Information')
        ));

        $store = Mage::registry('fpc_store');

        if ($store && $store->getId()) {
            $fieldset->addField('store_id', 'hidden', array(
                'name' => 'store_id',
            ));
        }

        $fieldset->addField('is_active', 'select',
            array(
                'name'      => 'is_active',
                'label'     => Mage::helper('bubble_fpc')->__('Cache Status'),
                'class'     => 'required-entry',
                'required'  => true,
                'values'    => array(
                    '1' => Mage::helper('adminhtml')->__('Yes'),
                    '0' => Mage::helper('adminhtml')->__('No'),
                ),
            )
        );

        if ($store) {
            $form->addValues(array(
                'store_id'  => $store->getId(),
                'is_active' => Mage::helper('bubble_fpc')->isStoreCacheable($store->getId()),
            ));
        }

        $form->setAction($this->getUrl('*/*/saveStore'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}