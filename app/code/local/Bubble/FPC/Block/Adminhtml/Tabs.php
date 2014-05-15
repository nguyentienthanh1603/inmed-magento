<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('fpc_tabs');
        $this->setTitle(Mage::helper('bubble_fpc')->__('Full Page Cache'));
    }

    protected function _beforeToHtml()
    {
        $activeSection = $this->_getActiveSection();

        $this->addTab('stores_section', array(
            'label'     => Mage::helper('bubble_fpc')->__('Store Views'),
            'title'     => Mage::helper('bubble_fpc')->__('Store Views'),
            'url'       => $this->getUrl('*/*/stores', array('_current' => true)),
            'class'     => 'ajax',
            'active'    => $activeSection === 'stores',
        ));

        $this->addTab('actions_section', array(
            'label'     => Mage::helper('bubble_fpc')->__('Cacheable Actions'),
            'title'     => Mage::helper('bubble_fpc')->__('Cacheable Actions'),
            'url'       => $this->getUrl('*/*/actions', array('_current' => true)),
            'class'     => 'ajax',
            'active'    => $activeSection === 'actions',
        ));

        $this->addTab('blocks_section', array(
            'label'     => Mage::helper('bubble_fpc')->__('Dynamic Blocks'),
            'title'     => Mage::helper('bubble_fpc')->__('Dynamic Blocks'),
            'url'       => $this->getUrl('*/*/blocks', array('_current' => true)),
            'class'     => 'ajax',
            'active'    => $activeSection === 'blocks',
        ));

        return parent::_beforeToHtml();
    }

    protected function _getActiveSection($default = 'stores')
    {
        $activeSection = Mage::getSingleton('adminhtml/session')->getActiveSection();
        if (!$activeSection) {
            $activeSection = $default;
        }

        return $activeSection;
    }
}