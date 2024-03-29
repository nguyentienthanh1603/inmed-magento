<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
	/**
	 * Blocks used to generate RSS feed items
	 *
	 * @var string
	 */
	 protected $_feedBlock = false;

	/**
	 * Root templates to be used
	 *
	 * @var array
	 */
	protected $_rootTemplates = array('default');
	
	/**
	 * Storage for breadcrumbs
	 * These are added to the breadcrumbs block before rendering the page
	 *
	 * @var array
	 */
	protected $_crumbs = array();
	
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * If null, means no entity required
	 * If false, means entity required but not set
	 *
	 * @return null|false|Mage_Core_Model_Abstract
	 */
	public function getEntityObject()
	{
		return null;
	}
	
	/**
	 * Ensure that the a database connection exists
	 * If not, do load the route
	 *
	 * @return $this
	 */
    public function preDispatch()
    {
    	parent::preDispatch();

		try {
			if (!$this->_canRunUsingConfig()) {
				$this->_forceForwardViaException('noRoute');
				return;
			}

			if ($this->getRequest()->getParam('feed')) {
				if (strpos(strtolower($this->getRequest()->getActionName()), 'feed') === false) {
					$this->_forceForwardViaException('feed');
					return;
				}
			}
		}
		catch (Mage_Core_Controller_Varien_Exception $e) {
			throw $e;
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e->getMessage());

			$this->_forceForwardViaException('noRoute');
			return;
		}

		return $this;
    }

	/**
	 * Determine whether the extension can run using the current config settings for this scope
	 * This will attempt to connect to the DB
	 *
	 * @return bool
	 */
	protected function _canRunUsingConfig()
	{
		if (!$this->isEnabledForStore()) {
			return false;
		}

		$helper = Mage::helper('wordpress/database');
		
		if (!$helper->isConnected() || !$helper->isQueryable()) {
			return false;
		}
		
		if ($helper->isSameDatabase()) {
			$helper->getReadAdapter()->query('SET NAMES UTF8');
		}

		if (($object = $this->getEntityObject()) === false) {
			return false;
		}

		Mage::dispatchEvent($this->getFullActionName() . '_init_after', array('object' => $object, $this->getRequest()->getControllerName() => $object, 'action' => $this));

		return true;
	}
	
	/**
	 * Before rendering layout, apply root template (if set)
	 * and add various META items
	 *
	 * @param string $output = ''
	 * @return $this
	 */
    public function renderLayout($output='')
    {
		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			if ($entity = $this->getEntityObject()) {
				$headBlock->addItem('link_rel', ($entity->getCanonicalUrl() ? $entity->getCanonicalUrl() : $entity->getUrl()), 'rel="canonical"');
			}

			$headBlock->addItem('link_rel', 
				Mage::helper('wordpress')->getUrl('feed/'), 
				'rel="alternate" type="application/rss+xml" title="' . Mage::helper('wordpress')->getWpOption('blogname') . ' &raquo; Feed"'
			);
			
			$headBlock->addItem('link_rel', 
				Mage::helper('wordpress')->getUrl('comments/feed/'), 
				'rel="alternate" type="application/rss+xml" title="' . Mage::helper('wordpress')->getWpOption('blogname') . ' &raquo; Comments Feed"'
			);
		}

		$rootTemplates = array_reverse($this->_rootTemplates);
		
		foreach($rootTemplates as $rootTemplate) {
			if ($template = Mage::getStoreConfig('wordpress/template/' . $rootTemplate)) {
				$this->getLayout()->helper('page/layout')->applyTemplate($template);
				break;
			}
		}

		Mage::dispatchEvent('wordpress_render_layout_before', array('object' => $this->getEntityObject(), 'action' => $this));

		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			if (Mage::helper('wordpress')->getWpOption('blog_public') !== '1') {
				$headBlock->setRobots('noindex,nofollow');
			}
		}

		$crumbCount = count($this->_crumbs);

		// determine if contributor or not
		$contributor = false;
		if ($crumbCount > 0 && ($block = $this->getLayout()->getBlock('breadcrumbs')) !== false) {
			foreach($this->_crumbs as $crumbName => $crumb) {

				if ( $crumbName == 'author' )
				{
					// we need new breadcrumbs
					$contributor = true;
				}
			}
		}
		if ( $contributor )
		{


			// get contributor name
			$_old_name = $this->_crumbs['author'];

			// delete old entries
			unset($this->_crumbs['author_nolink']);
			unset($this->_crumbs['author']);

			// im-listening
			$this->_crumbs['im-listening'][0]['link'] = Mage::getBaseUrl().'im-listening';
			$this->_crumbs['im-listening'][0]['label'] = 'IM Listening';
			$this->_crumbs['im-listening'][0]['title'] = 'IM Listening';

			$this->_crumbs['contributors'][0]['link'] = Mage::getBaseUrl().'im-listening/contributors';
			$this->_crumbs['contributors'][0]['label'] = 'Contributors';
			$this->_crumbs['contributors'][0]['title'] = 'Contributors';

			// current user
			$_user_slug = explode('/',$_old_name[0]['link']);

			$this->_crumbs[ $_user_slug[4] ][0]['link'] = Mage::getBaseUrl().'im-listening/contributors/'.$_user_slug[4];
			$this->_crumbs[ $_user_slug[4] ][0]['label'] = $_old_name[0]['label'];
			$this->_crumbs[ $_user_slug[4] ][0]['title'] = $_old_name[0]['title'];

			$this->_crumbs['articles'][0]['link'] = Mage::getBaseUrl().'im-listening/contributors/'. $_user_slug[4] . '/all';
			$this->_crumbs['articles'][0]['label'] = 'All Articles';
			$this->_crumbs['articles'][0]['title'] = 'All Articles';

		}

//		var_dump( $this->_crumbs );

		$urlString = Mage::helper('core/url')->getCurrentUrl();
		$url = Mage::getSingleton('core/url')->parseUrl($urlString);

		if (strpos($url->getPath(),'im-listening/contributors') == 1 && count($this->_crumbs) == 1)
		{
			$this->_crumbs['im-listening'][0]['link'] = Mage::getBaseUrl().'im-listening';
			$this->_crumbs['im-listening'][0]['label'] = 'IM Listening';
			$this->_crumbs['im-listening'][0]['title'] = 'IM Listening';

			$this->_crumbs['contributors'][0]['link'] = Mage::getBaseUrl().'im-listening/contributors';
			$this->_crumbs['contributors'][0]['label'] = 'Contributors';
			$this->_crumbs['contributors'][0]['title'] = 'Contributors';
		}

		if (strpos($url->getPath(),'im-listening/media') == 1  && count($this->_crumbs) == 1)
		{
			$this->_crumbs['im-listening'][0]['link'] = Mage::getBaseUrl().'im-listening';
			$this->_crumbs['im-listening'][0]['label'] = 'IM Listening';
			$this->_crumbs['im-listening'][0]['title'] = 'IM Listening';

			$this->_crumbs['media'][0]['link'] = Mage::getBaseUrl().'im-listening/media';
			$this->_crumbs['media'][0]['label'] = 'Media';
			$this->_crumbs['media'][0]['title'] = 'Media';
		}

		if (strpos($url->getPath(),'im-listening/stories') == 1 && count($this->_crumbs) == 1 )
		{
			$this->_crumbs['im-listening'][0]['link'] = Mage::getBaseUrl().'im-listening';
			$this->_crumbs['im-listening'][0]['label'] = 'IM Listening';
			$this->_crumbs['im-listening'][0]['title'] = 'IM Listening';

			$this->_crumbs['stories'][0]['link'] = Mage::getBaseUrl().'im-listening/stories';
			$this->_crumbs['stories'][0]['label'] = 'Stories';
			$this->_crumbs['stories'][0]['title'] = 'Stories';
		}

		if (strpos($url->getPath(),'im-listening/interviews') == 1 && count($this->_crumbs) == 1 )
		{
			$this->_crumbs['im-listening'][0]['link'] = Mage::getBaseUrl().'im-listening';
			$this->_crumbs['im-listening'][0]['label'] = 'IM Listening';
			$this->_crumbs['im-listening'][0]['title'] = 'IM Listening';

			$this->_crumbs['interviews'][0]['link'] = Mage::getBaseUrl().'im-listening/interviews';
			$this->_crumbs['interviews'][0]['label'] = 'Interviews';
			$this->_crumbs['interviews'][0]['title'] = 'Interviews';
		}

		// if stories

		// end samuel hack

		
		if ($crumbCount > 0 && ($block = $this->getLayout()->getBlock('breadcrumbs')) !== false) {
			foreach($this->_crumbs as $crumbName => $crumb) {

				if (--$crumbCount === 0 && isset($crumb[0]['link'])) {
					unset($crumb[0]['link']);
				}
				
				$block->addCrumb($crumbName, $crumb[0], $crumb[1]);
			}
		}
		
		return parent::renderLayout($output);
	}

	/**
	 * Loads layout and performs initialising tasls
	 *
	 */
	protected function _initLayout()
	{
		if (!$this->_isLayoutLoaded) {
			$this->loadLayout();
		}
		
		$this->_title()->_title(Mage::helper('wordpress')->getWpOption('blogname'));

		$this->addCrumb('home', array('link' => Mage::getUrl(), 'label' => $this->__('Home')));
		
		if (!$this->isFrontPage()) {
			$toplinkUrl = Mage::helper('wordpress')->getTopLinkUrl();
			
			if ($toplinkUrl !== Mage::getUrl()) {
				$this->addCrumb('blog', array('link' => $toplinkUrl, 'label' => $this->__(Mage::helper('wordpress')->getTopLinkLabel())));
			}
		}
		else {
			$this->addCrumb('blog', array('label' => $this->__(Mage::helper('wordpress')->getTopLinkLabel())));
		}
		
		if ($rootBlock = $this->getLayout()->getBlock('root')) {
			$rootBlock->addBodyClass('is-blog');
		}
		
		Mage::dispatchEvent('wordpress_init_layout_after', array('object' => $this->getEntityObject()));
		Mage::dispatchEvent($this->getFullActionName() . '_init_layout_after', array('object' => $this->getEntityObject()));

		return $this;
	}
	
	/**
	 * Adds a crumb to the breadcrumb trail
	 *
	 * @param string $crumbName
	 * @param array $crumbInfo
	 * @param string $after
	 */
	public function addCrumb($crumbName, array $crumbInfo, $after = false)
	{
		if (!isset($crumbInfo['title'])) {
			$crumbInfo['title'] = $crumbInfo['label'];
		}
		
		$this->_crumbs[$crumbName] = array($crumbInfo, $after);
		
		return $this;
	}

	/**
	 * Retrieve a breadcrumb
	 *
	 * @param string $crumbName
	 * @return array
	 */
	public function getCrumb($crumbName)
	{
		return isset($this->_crumbs[$crumbName]) ? $this->_crumbs[$crumbName] : false;
	}
	
	/**
	 * Adds custom layout handles
	 *
	 * @param array $handles = array()
	 */
	protected function _addCustomLayoutHandles(array $handles = array())
	{
		$update = $this->getLayout()->getUpdate();

		array_unshift($handles, 'wordpress_default');

		$storeHandlePrefix = 'STORE_' . Mage::app()->getStore()->getCode() . '_';
		$allHandles = array();
		
		foreach($handles as $it => $handle) {
			$allHandles[] = $handle;
			$allHandles[] = $storeHandlePrefix . $handle;
		}
		array_unshift($allHandles, 'default');
		
		foreach($allHandles as $handle) {
			$update->addHandle($handle);
		}
		
		$this->addActionLayoutHandles();
		
		$handles = $update->getHandles();

		$update->addHandle($storeHandlePrefix . array_pop($handles));
		
		$this->loadLayoutUpdates();		
		$this->generateLayoutXml();
		$this->generateLayoutBlocks();
		
		$this->_isLayoutLoaded = true;
		
		return $this;
	}
	
	/**
	 * Force the extension to remove any currently set titles
	 * This is likely to be called by SEO plugins (AllInOneSEO and Yoast SEO)
	 * so that they can rewrite the page titles
	 *
	 * @return $this
	 */
	public function ignoreAutomaticTitles()
	{
		$this->_titles = array();
		$this->_removeDefaultTitle = true;
		
		return $this;
	}
	
	/**
	 * Retrieve the router helper object
	 *
	 * @return Fishpig_Wordpress_Helper_Router
	 */
	public function getRouterHelper()
	{
		return Mage::helper('wordpress/router');
	}
	
	/**
	 * Determine whether the extension has been enabled for the current store
	 *
	 * @return bool
	 */
	public function isEnabledForStore()
	{
		return !Mage::getStoreConfigFlag('advanced/modules_disable_output/Fishpig_Wordpress');
	}
	
	/**
	 * Determine whether the current page is the blog homepage
	 *
	 * @return bool
	 */	
	public function isFrontPage()
	{
		return $this->getFullActionName() === 'wordpress_index_index';
	}
	
	/**
	 * Force Magento ro redirect to a different route
	 * This will happen without changing the current URL
	 *
	 * @param string $action
	 * @param string $controller = ''
	 * @param string $module = ''
	 * @param array $params = array
	 * @return void
	 */
	protected function _forceForwardViaException($action, $controller = '', $module = '', $params = array())
	{
		if ($action === 'noRoute') {
			$controller = 'index';
			$module = 'cms';
		}
		else {
			if ($controller === '') {
				$controller = $this->getRequest()->getControllerName();
			}
			
			if ($module === '') {
				$module = $this->getRequest()->getModuleName();
			}
		}
				
		$this->setFlag('', self::FLAG_NO_DISPATCH, true);
		
		$e = new Mage_Core_Controller_Varien_Exception();
	
		throw $e->prepareForward($action, $controller, $module, $params);
	}
	
	/**
	 * Forward a request to WordPress
	 *
	 * @param string $uri = ''
	 * @return $this
	 */
	protected function _forwardToWordPress($uri = '')
	{
		return $this->_redirectUrl(
			rtrim(Mage::helper('wordpress')->getWpOption('siteurl'), '/') . '/' . ltrim($uri, '/')
		);
	}
	
	/**
	 * Render the RSS Feed
	 *
	 * @return void
	 */
	public function feedAction()
	{
		if (($block = $this->_feedBlock) !== false) {
			if (strpos($block, '/') === false) {
				$block = 'wordpress/' . $block;
			}

			$this->getResponse()
				->setHeader('Content-Type', 'text/xml; charset=UTF-8')
				->setBody(
					$this->getLayout()->createBlock('wordpress/feed_post')->setSourceBlock($block)->setFeedType(
						$this->getRequest()->getParam('feed', 'rss2')
					)->toHtml()
				);
		}
		else {
			$this->_forward('noRoute');
		}
	}
	
	/**
	 * Display the comments feed
	 *
	 * @return void
	 */
	public function commentsFeedAction()
	{
		$this->getResponse()
			->setHeader('Content-Type', 'text/xml; charset=UTF-8')
			->setBody(
				$this->getLayout()->createBlock('wordpress/feed_post_comment')
					->setSource(Mage::registry('wordpress_post'))
					->setFeedType($this->getRequest()->getParam('feed', 'rss2'))
					->toHtml()
			);
	}
}
