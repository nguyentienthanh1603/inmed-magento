<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Block_Widget_Page_Children
    extends Mage_Core_Block_Abstract
    implements Mage_Widget_Block_Interface
{
    public function getPage()
    {
        return Mage::getSingleton('cms/page');
    }

    protected function _toHtml()
    {
        if (!$this->getPage()->getId()) {
            return '';
        }

        $activePages = $this->getPage()
            ->getChildren()
            ->addActiveFilter();
        $activePagesCount = $activePages->count();

        if (!$activePagesCount) {
            // If no active children, display pages on the same level
            $activePages = $this->getPage()
                ->getParentPage()
                ->getChildren()
                ->addActiveFilter();
            $activePagesCount = $activePages->count();
        }

        if (!$activePagesCount) {
            // Should never occurs since we are displaying pages
            // on the same level if no active child is found
            return '';
        }

        $html = '';
        if ($this->getTitle()) {
            $html .= '<p class="page-children-title">' . $this->getTitle() . '</p>';
        }
        $html .= sprintf('<ul class="page-children %s">', $this->getCssClass());
        $i = 0;
        foreach ($activePages as $page) {
            $html .= $this->_getPageChildrenHtml(
                $page,
                0,                             // level
                ($i == $activePagesCount - 1), // is last
                ($i == 0)                      // is first
            );
            $i++;
        }
        $html .= '</ul>';

        return $html;
    }

    protected function _getPageChildrenHtml($page, $level = 0, $isLast = false, $isFirst = false)
    {
        $html = array();

        // get all children
        $activeChildren = $page->getChildren()
            ->addActiveFilter();
        if (Mage::helper('cms/page')->isPermissionsEnabled($this->getStore())) {
            $activeChildren->addPermissionsFilter($this->getCustomerGroupId());
        }

        $activeChildrenCount = $activeChildren->count();
        $hasActiveChildren = ($activeChildrenCount > 0);

        // prepare list item html classes
        $classes = array();
        $classes[] = 'level' . $level;

        if ($isFirst) {
            $classes[] = 'first';
        }
        if ($isLast) {
            $classes[] = 'last';
        }
        if ($hasActiveChildren) {
            $classes[] = 'parent';
        }

        // prepare list item attributes
        $attributes = array();
        if (count($classes) > 0) {
            $attributes['class'] = implode(' ', $classes);
        }

        // assemble list item with attributes
        $htmlLi = '<li';
        foreach ($attributes as $attrName => $attrValue) {
            $htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
        }
        $htmlLi .= '>';
        $html[] = $htmlLi;
        $html[] .= '<a href="'. $page->getUrl() . '">';
        $html[] .= '<span>'. $this->escapeHtml($page->getTitle()) .'</span>';
        $html[] .= '</a>';

        if ($hasActiveChildren && (!$this->getLevels() || $this->getLevels() > ($level + 1))) {
            // render children
            $htmlChildren = '';
            $i = 0;
            foreach ($activeChildren as $child) {
                $htmlChildren .= $this->_getPageChildrenHtml(
                    $child,
                    ($level + 1),
                    ($i == $activeChildrenCount - 1),  // is last
                    ($i == 0)                          // is first
                );
                $i++;
            }

            if (!empty($htmlChildren)) {
                $html[] = '<ul class="level' . $level . '">';
                $html[] = $htmlChildren;
                $html[] = '</ul>';
            }
        }

        $html[] = '</li>';
        $html = implode("\n", $html);

        return $html;
    }

    public function getStore($id = null)
    {
        return Mage::app()->getStore($id);
    }

    public function getCustomerGroupId()
    {
        return Mage::getSingleton('customer/session')->getCustomerGroupId();
    }
}
