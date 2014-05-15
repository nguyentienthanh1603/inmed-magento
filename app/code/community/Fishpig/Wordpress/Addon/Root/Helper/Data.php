<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Addon_Root_Helper_Data extends Mage_Core_Helper_Abstract
{	
	/**
	 * Determine whether @root is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::getStoreConfigFlag('wordpress/integration/at_root');
	}
	
	/**
	 * Determine whether to replace the Magento homepage with the WP homepage
	 *
	 * @return bool
	 */
	public function canReplaceHomepage()
	{
		return $this->isEnabled()
			&& Mage::getStoreConfigFlag('wordpress/integration/replace_homepage');
	}	
	
	/**
	 * If can replace homepage, set the default route
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function controllerFrontInitObserver(Varien_Event_Observer $observer)
	{
		if ($this->isAdmin()) {
			return false;
		}

		$app = Mage::app();

		if ($app->getRequest()->getParam('preview') === 'true' && ($postId = $app->getRequest()->getParam('p'))) {
			$app->getStore()->setConfig('web/default/front', 'wordpress/post/view');
		}
		else if ($this->canReplaceHomepage()) {
			if (($pageId = Mage::helper('wordpress/router')->getHomepagePageId()) !== false) {
				$app->getStore()->setConfig('web/default/front', 'wordpress/page/view');
				$app->getRequest()->setParam('id', $pageId);
				$app->getRequest()->setParam('is_home', 1);
			}
			else {
				$app->getStore()->setConfig('web/default/front', 'wordpress');
			}
		}
		
		return $this;
	}

	/**
	 * Retrieve the blog route
	 * If Root enabled, set the blog route as an empty string
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function blogRouteGetObserver(Varien_Event_Observer $observer)
	{
		if ($this->isEnabled()) {
			$observer->getTransport()->setBlogRoute('');
		}
		
		return $this;
	}
	
	/**
	 * Retrieve the toplink URL
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function getToplinkUrlObserver(Varien_Event_Observer $observer)
	{
		if ($this->isEnabled() && ($url = $this->_getToplinkUrl()) !== null) {
			$observer->getEvent()
				->getTransport()
					->setToplinkUrl($url);
		}

		return $this;
	}
	
	protected function _getToplinkUrl()
	{
		if ($this->isEnabled()) {
			if (($pageId = Mage::helper('wordpress/router')->getBlogPageId()) !== false) {
				$page = Mage::getModel('wordpress/page')->load($pageId);
				
				if ($page->getId()) {
					return $page->getUrl();
				}
			}
		}
		
		return null;
	}
	
	/**
	 * Retrieve the toplink URL
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function getToplinkLabelObserver(Varien_Event_Observer $observer)
	{
		if ($this->isEnabled() && !$this->canReplaceHomepage() && is_null($this->_getToplinkUrl())) {
			$observer->getEvent()
				->getTransport()
					->setToplinkLabel(null);
		}

		return $this;
	}
	
	/**
	 * Determine whether request is Admin request
	 *
	 * @return bool
	 */
	public function isAdmin()
	{
		$adminFrontName = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
		$pathInfo = Mage::app()->getRequest()->getPathInfo();
		
		return (strpos($pathInfo, '/' . $adminFrontName . '/') === 0)
			|| ('/' . $adminFrontName === rtrim($pathInfo));
	}
}