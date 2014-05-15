<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Model_Adminhtml_Cms_Page_Observer
{
    public function prepareForm($observer)
    {
        $page = Mage::registry('cms_page');
        $form = $observer->getEvent()->getForm();
        $includeInMenuDisabled = $urlKeyDisabled = false;

        // add our 'url_key' field so that users can set a URL identifier independent of the CMS page title
        if ($page->getPageId() && $page->isRoot()) {
            // disable the 'url key' and 'include in menu' configuration options for the root CMS page
            $includeInMenuDisabled = $urlKeyDisabled = true;
        }

        $form->getElement('base_fieldset')->addField('url_key', 'text', array(
            'name'     => 'url_key',
            'label'    => Mage::helper('cms')->__('URL Key'),
            'title'    => Mage::helper('cms')->__('URL Key'),
            'note'     => Mage::helper('cms')->__(
                'Leave blank for automatic generation.<br />URL is relative to parent URL. Current URL: %s',
                $page->getUrl()
            ),
            'value'    => $page->getIdentifier(),
            'disabled' => $urlKeyDisabled
        ));

        if (!Mage::app()->isSingleStoreMode() && $page->getStoreId() == 0) {
            $form->getElement('base_fieldset')
                ->removeField('stores');
            $form->getElement('base_fieldset')->addField('stores', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => Mage::helper('cms')->__('Store View'),
                'title'     => Mage::helper('cms')->__('Store View'),
                'required'  => false,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
            ));
        }

        $form->getElement('base_fieldset')->addField('include_in_menu', 'select', array(
            'name'     => 'include_in_menu',
            'label'    => Mage::helper('cms')->__('Include in Navigation Menu'),
            'title'    => Mage::helper('cms')->__('Include in Navigation Menu'),
            'note'     => Mage::helper('cms')->__('Will be ignored if global config is set to "No", which is default value.'),
            'values'   => array(
                '1' => Mage::helper('adminhtml')->__('Yes'),
                '0' => Mage::helper('adminhtml')->__('No')
            ),
            'disabled' => $includeInMenuDisabled,
        ));

        $form->getElement('base_fieldset')
            ->removeField('identifier')
            ->removeField('store_id');
        $form->addField('store_id', 'hidden', array('name' => 'store'));
        $form->addField('parent_id', 'hidden', array('name' => 'parent'));
        $form->addField('identifier', 'hidden', array('name' => 'identifier'));

        $request = Mage::app()->getRequest();
        $form->addField('current_tab', 'hidden', array('name' => 'tab', 'value' => $request->getParam('tab')));

        if (null === $page->getIncludeInMenu()) {
            $page->setIncludeInMenu(true);
        }
    }

    public function prepareSave($observer)
    {
        $request = $observer->getEvent()->getRequest();
        $page = $observer->getEvent()->getPage();
        if ($request->has('store')) {
            $page->setStoreId($request->getParam('store'));
        }
        if ($request->has('parent')) {
            $page->setParentId($request->getParam('parent'));
        }
    }
}