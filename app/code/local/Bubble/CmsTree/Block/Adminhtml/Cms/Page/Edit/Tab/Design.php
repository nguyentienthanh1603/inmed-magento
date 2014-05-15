<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Block_Adminhtml_Cms_Page_Edit_Tab_Design extends Mage_Adminhtml_Block_Cms_Page_Edit_Tab_Design
{
    protected function _prepareForm()
    {
        // Check if user have enough permissions to save information
        $isElementDisabled = true;
        if ($this->_isAllowedAction('save')) {
            $isElementDisabled = false;
        }

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('page_');

        $model = Mage::registry('cms_page');

        $layoutFieldset = $form->addFieldset('layout_fieldset', array(
            'legend' => Mage::helper('cms')->__('Page Layout'),
            'class'  => 'fieldset-wide',
            'disabled'  => $isElementDisabled
        ));

        $layoutFieldset->addField('root_template', 'select', array(
            'name'     => 'root_template',
            'label'    => Mage::helper('cms')->__('Layouts'),
            'values'   => Mage::getSingleton('catalog/category_attribute_source_layout')->getAllOptions(),
            'disabled' => $isElementDisabled
        ));

        if (!$model->getId()) {
            $model->setRootTemplate(Mage::getSingleton('page/source_layout')->getDefaultValue());
        }

        $layoutFieldset->addField('layout_update_xml', 'textarea', array(
            'name'      => 'layout_update_xml',
            'label'     => Mage::helper('cms')->__('Layout Update XML'),
            'style'     => 'height:24em;',
            'disabled'  => $isElementDisabled
        ));

        $designFieldset = $form->addFieldset('design_fieldset', array(
            'legend' => Mage::helper('cms')->__('Custom Design'),
            'class'  => 'fieldset-wide',
            'disabled'  => $isElementDisabled
        ));

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
        );

        $designFieldset->addField('custom_theme_from', 'date', array(
            'name'      => 'custom_theme_from',
            'label'     => Mage::helper('cms')->__('Custom Design From'),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => $dateFormatIso,
            'disabled'  => $isElementDisabled
        ));

        $designFieldset->addField('custom_theme_to', 'date', array(
            'name'      => 'custom_theme_to',
            'label'     => Mage::helper('cms')->__('Custom Design To'),
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'format'    => $dateFormatIso,
            'disabled'  => $isElementDisabled
        ));

        $designFieldset->addField('custom_theme', 'select', array(
            'name'      => 'custom_theme',
            'label'     => Mage::helper('cms')->__('Custom Theme'),
            'values'    => Mage::getModel('core/design_source_design')->getAllOptions(),
            'disabled'  => $isElementDisabled
        ));

        $designFieldset->addField('custom_root_template', 'select', array(
            'name'      => 'custom_root_template',
            'label'     => Mage::helper('cms')->__('Custom Layout'),
            'values'    => Mage::getSingleton('catalog/category_attribute_source_layout')->getAllOptions(),
            'disabled'  => $isElementDisabled
        ));

        $designFieldset->addField('custom_layout_update_xml', 'textarea', array(
            'name'      => 'custom_layout_update_xml',
            'label'     => Mage::helper('cms')->__('Custom Layout Update XML'),
            'style'     => 'height:24em;',
            'disabled'  => $isElementDisabled
        ));

        Mage::dispatchEvent('adminhtml_cms_page_edit_tab_design_prepare_form', array('form' => $form));

        $form->setValues($model->getData());
        $this->setForm($form);

        return $this;
    }
}
