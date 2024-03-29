<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Block_Catalog_Navigation extends Mage_Catalog_Block_Navigation
{
    const CACHE_TAG = 'catalog_navigation';

    protected function _construct()
    {
        $this->addData(array(
            'cache_lifetime' => false,
            'cache_tags'     => array(
                Mage_Catalog_Model_Category::CACHE_TAG,
                Mage_Core_Model_Store_Group::CACHE_TAG,
                self::CACHE_TAG,
            ),
        ));
    }

    public function getCacheKeyInfo()
    {
        $shortCacheId = array(
            'CATALOG_NAVIGATION',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
            $this->getCurrenCategoryKey(),
            'page_id' => $this->getCurrentCmsPage() ? $this->getCurrentCmsPage()->getId() : false
        );
        $cacheId = $shortCacheId;
        $shortCacheId = array_values($shortCacheId);
        $shortCacheId = implode('|', $shortCacheId);
        $shortCacheId = md5($shortCacheId);
        $cacheId['category_path'] = $this->getCurrenCategoryKey();
        $cacheId['short_cache_id'] = $shortCacheId;

        return $cacheId;
    }

    /**
     * Render CMS to html
     *
     * @param Mage_Cms_Model_Page $page
     * @param int Nesting level number
     * @param boolean Whether or not this item is last, affects list item class
     * @param boolean Whether or not this item is first, affects list item class
     * @param boolean Whether or not this item is outermost, affects list item class
     * @param string Extra class of outermost list items
     * @param string If specified wraps children list in div with this class
     * @param boolean Whether or not to add on* attributes to list item
     * @return string
     */
    protected function _renderCmsMenuItemHtml($page, $level = 0, $isLast = false, $isFirst = false,
                                              $isOutermost = false, $outermostItemClass = '',
                                              $childrenWrapClass = '', $noEventAttributes = false)
    {
        if (!$this->_isAllowed($page)) {
            return '';
        }
        $html = array();

        // get all children
        $children = $page->getChildren();
        if (Mage::helper('cms/page')->isPermissionsEnabled($this->getStore())) {
            $children->addPermissionsFilter($this->getCustomerGroupId());
        }

        // select active children
        $activeChildren = array();
        foreach ($children as $child) {
            if ($child->getIsActive() && $child->getIncludeInMenu()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);

        // prepare list item html classes
        $classes = array();
        $classes[] = 'level' . $level;
//		$classes[] = 'dropdown';

        // note: not dealing with the 'nav-' class at the moment
        if ($this->isCmsPageActive($page)) {
            $classes[] = 'active';
        }
        $linkClass = '';
		$_bootstrap = '';
        if ($isOutermost && $outermostItemClass) {
            $classes[] = $outermostItemClass;
            $linkClass = ' class="'.$outermostItemClass.'"';
        }
        if ($isFirst) {
            $classes[] = 'first';
        }
        if ($isLast) {
            $classes[] = 'last';
        }
        if ($hasActiveChildren) {
            $classes[] = 'parent';
			// bootstrap
//			$_bootstrap = ' data-toggle="dropdown"';
        }

        // prepare list item attributes
        $attributes = array();
        if (count($classes) > 0) {
            $attributes['class'] = implode(' ', $classes);
        }
        if ($hasActiveChildren && !$noEventAttributes) {
//             $attributes['onmouseover'] = 'toggleMenu(this,1)';
//             $attributes['onmouseout'] = 'toggleMenu(this,0)';
        }

        // assemble list item with attributes
        $htmlLi = '<li';
        foreach ($attributes as $attrName => $attrValue) {
            $htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
        }
        $htmlLi .= '>';
        $html[] = $htmlLi;
        $html[] .= '<a href="'. $page->getUrl() . "/" .'"'.$linkClass.$_bootstrap.'>';
        $html[] .=  $this->escapeHtml($page->getTitle());
        $html[] .= '</a>';

        // render children
        $htmlChildren = '';
        $j = 0;
        foreach ($activeChildren as $child) {
            $htmlChildren .= $this->_renderCmsMenuItemHtml(
                $child,
                ($level + 1),
                ($j == $activeChildrenCount - 1),  // is last
                ($j == 0),                         // is first
                false,                             // is outermost
                $outermostItemClass,
                $childrenWrapClass,
                $noEventAttributes
            );
            $j++;
        }

        if (!empty($htmlChildren)) {
            if ($childrenWrapClass) {
                $html[] = '<div class="' . $childrenWrapClass . '">';
            }
            $html[] = '<ul class="level' . $level . '">';
            $html[] = $htmlChildren;
            $html[] = '</ul>';
            if ($childrenWrapClass) {
                $html[] = '</div>';
            }
        }

        $html[] = '</li>';

		$html = $this->_addToggle($html);
		$html = $this->_addCaret($html);
		$html = $this->_addDropdown($html);

        $html = implode("\n", $html);



        return $html;
    }

    /**
     * Render CMS menu in HTML
     *
     * @param int Level number for list item class to start from
     * @param bool Whether or not this group of items is last, affects list item class
     * @param bool Whether or not this group of items is first, affects list item class
     * @param string Extra class of outermost list items
     * @param string If specified wraps children list in div with this class
     * @return string
     */
    public function renderCmsMenuHtml($level = 0, $isLast = true, $isFirst = true,
                                      $outermostItemClass = '', $childrenWrapClass = '')
    {
        $activePages = array();
        foreach ($this->getStoreCmsPages() as $child) {
            if ($child->getIsActive() && $child->getIncludeInMenu()) {
                $activePages[] = $child;
            }
        }

        $activePagesCount = count($activePages);
        $hasActivePagesCount = ($activePagesCount > 0);

        if (!$hasActivePagesCount) {
            return '';
        }

        $html = '';
        $j = 0;


		
        foreach ($activePages as $page) {

			//-------------------------------------------------------
			//------------ Samuel Hack ---------------------
			//-------------------------------------------------------
			if ($page->getIdentifier() != 'product-faq' )
			{

				$html .= $this->_renderCmsMenuItemHtml(
					$page,
					$level,
					($isLast && $j == $activePagesCount - 1), // is last
					($isFirst && $j == 0),                    // is first
					true,                                     // is outermost
					$outermostItemClass,
					$childrenWrapClass,
					true
				);
				$j++;
			}

		}

		$html = $this->_addToggle($html);
		$html = $this->_addCaret($html);
		$html = $this->_addDropdown($html);

        return $html;
    }


    /**
     * Render categories menu in HTML. Unfortunately in order to 'overload' the
     * renderCategoriesMenuHtml() and add the two new parameters $isLast and
     * $isFirst, I had to copy the original method here and rename it.
     *
     * @param int Level number for list item class to start from
     * @param bool Whether or not this group of items is last, affects list item class
     * @param bool Whether or not this group of items is first, affects list item class
     * @param string Extra class of outermost list items
     * @param string If specified wraps children list in div with this class
     * @return string
     */
    public function renderCategoriesMenuHtmlBubble($level = 0, $isLast = true, $isFirst = true,
                                                   $outermostItemClass = '', $childrenWrapClass = '')
    {
        $activeCategories = array();
        foreach ($this->getStoreCategories() as $child) {
            if ($child->getIsActive()) {
                $activeCategories[] = $child;
            }
        }
        $activeCategoriesCount = count($activeCategories);
        $hasActiveCategoriesCount = ($activeCategoriesCount > 0);

        if (!$hasActiveCategoriesCount) {
            return '';
        }

        $html = '';
        $j = 0;
        foreach ($activeCategories as $category) {
            $html .= $this->_renderCategoryMenuItemHtml(
                $category,
                $level,
                ($isLast && $j == $activeCategoriesCount - 1),
                ($isFirst && $j == 0),
                true,
                $outermostItemClass,
                $childrenWrapClass,
                true
            );
            $j++;
        }

//		echo $html;

//		$html = $this->_addToggle($html);

//		$html = $this->_addCaret($html);
		$html = $this->_addDropdown($html);
//		echo $html;

        return $html;
    }

    /**
     * Get top level CMS pages of current store
     *
     * @return array of Mage_Cms_Model_Page
     */
    public function getStoreCmsPages()
    {
        $collection = $this->getCmsRootPage(0)->getChildren();
        foreach ($this->getCmsRootPage()->getChildren() as $page) {
            $collection->addItem($page);
        }

        return $collection;
    }

    /**
     * Return the root CMS page for this store
     *
     * @return Mage_Cms_Model_Page
     */
    public function getCmsRootPage($storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore()->getId();
        }

        return Mage::getModel('cms/page')->loadRootByStoreId($storeId);
    }

    /**
     * Returns true if $page is the current page, or is on the path (is a parent)
     * of the current page.  The root (index) page must be handled specially and
     * this function will *not* return true when it is the active page.
     *
     * @param Mage_Cms_Model_Page
     * @return bool
     */
    public function isCmsPageActive($page)
    {
        $current = $this->getCurrentCmsPage();
        if ($current) {
            return in_array($page->getId(), $current->getPathIds());
        }

        return false;
    }

    /**
     * Get the current CMS page.  The root (index) page must be handled specially and
     * this function will *not* return it when it is the active page.
     *
     * @return Mage_Cms_Model_Page the current CMS page, or false if the current
     *         page is not CMS
     */
    public function getCurrentCmsPage()
    {
        $page = Mage::getSingleton('cms/page');
        if (count($page->getData()) > 0 && !$page->isRoot()) {
            return $page;
        }

        return false;
    }

    /**
     * Returns true if there is at least one active top level CMS page to display
     * in the navigation menu.
     *
     * @return bool
     */
    public function hasCmsNavigationMenuPages()
    {
        foreach ($this->getStoreCmsPages() as $child) {
            if ($child->getIsActive() && $child->getIncludeInMenu()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if there is at least one active top level Category page to
     * display in the navigation menu.
     */
    public function hasCategoriesNavigationMenuPages()
    {
        foreach ($this->getStoreCategories() as $child) {
            if ($child->getIsActive()) {
                return true;
            }
        }

        return false;
    }

    public function getStore($id = null)
    {
        return Mage::app()->getStore($id);
    }

    public function getCustomerGroupId()
    {
        return Mage::getSingleton('customer/session')->getCustomerGroupId();
    }

    protected function _isAllowed($page)
    {
        return Mage::helper('cms/page')->isAllowed(
            $this->getStore()->getId(),
            $this->getCustomerGroupId(),
            $page->getId()
        );
    }

	/**
	 * Adds a dropdown HTML5 attribute to top level links.
	 *
	 * @param  string  $html
	 * @return string
	 */
	protected function _addToggle($html)
	{

		return preg_replace('/(<li\s+class="level0.*?<a.*?)(>)/', '$1 data-toggle="dropdown"$2', $html);
	}

	/**
	 * Adds a caret HTML symbol to top level links.
	 *
	 * @param  string  $html
	 */
	protected function _addCaret($html)
	{
//		echo "ASd";
		return preg_replace('/<a\s+"level-top">/', '$0 <b class="caret"></b>', $html);
//		return preg_replace('/(<li\s+class="level0.*?)(<\/a>)/', '$1 <b class="caret"></b> $2', $html);
	}

	/**
	 * Adds a CSS class to top level dropdowns.
	 *
	 * @param  string  $html
	 * @return string
	 */
	protected function _addDropdown($html)
	{
		return preg_replace('/<ul\s+class="level0/', '$0 dropdown-menu', $html);
	}
}