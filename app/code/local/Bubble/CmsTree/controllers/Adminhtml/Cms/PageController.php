<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
require_once 'Mage/Adminhtml/controllers/Cms/PageController.php';

class Bubble_CmsTree_Adminhtml_Cms_PageController extends Mage_Adminhtml_Cms_PageController
{
    protected function _initPage()
    {
        $pageId = (int) $this->getRequest()->getParam('id');
        $page = Mage::getModel('cms/page')->load($pageId);
        Mage::register('cms_page', $page);
        Mage::getSingleton('cms/wysiwyg_config')->setStoreId($this->getRequest()->getParam('store'));

        return $page;
    }

    public function indexAction()
    {
        $storeId = $this->getRequest()->getParam('store');
        if (null === $storeId) {
            $storeId = Mage::getSingleton('admin/session')->getCmsLastViewedStore();
            if (null === $storeId) {
                if (Mage::app()->isSingleStoreMode()) {
                    $storeId = Mage::app()->getDefaultStoreView()->getId();
                } else {
                    $storeId = 0;
                }
            }
            $params = array(
                'store' => $storeId
            );
            if ($tab = $this->getRequest()->getParam('tab')) {
                $params['tab'] = $tab;
            }
            $this->_redirect('*/*/', $params);

            return;
        }

        if ($pageId = Mage::getSingleton('admin/session')->getLastEditedPage()) {
            $page = Mage::getModel('cms/page')->load($pageId);
            if ($page->getId()) {
                Mage::register('cms_page', $page);
            }
        }

        $this->_title($this->__('CMS'))
            ->_title($this->__('Pages'))
            ->_title($this->__('Manage Content'));

        $this->_initAction();
        $this->renderLayout();
    }

    public function addAction()
    {
        Mage::getSingleton('admin/session')->unsLastEditedPage();
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('CMS'))
            ->_title($this->__('Pages'))
            ->_title($this->__('Manage Content'));

        // 1. Get ID and create model
        $storeId = (int) $this->getRequest()->getParam('store');
        $id = (int) $this->getRequest()->getParam('id');

        $page = Mage::getModel('cms/page');

        // 2. Initial checking
        if ($id) {
            $page->load($id);
            if (!$page->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('cms')->__('This page no longer exists.')
                );
                $this->_redirect('*/*/');

                return;
            }
        } else {
            $page->setStoreId($storeId);
            if ($storeId === 0 && !Mage::app()->isSingleStoreMode()) {
                $page->setStores(array_keys(Mage::app()->getStores()));
            }
        }

        $this->_title($page->getId() ? $page->getTitle() : $this->__('New Page'));

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $page->setData($data);
        }

        Mage::getSingleton('admin/session')->setCmsLastViewedStore($storeId);
        Mage::getSingleton('admin/session')->setLastEditedPage($page->getId());

        // 4. Register model to use later in blocks
        Mage::register('cms_page', $page);

        // 5. Build edit form
        $label = $id ? Mage::helper('cms')->__('Edit Page') : Mage::helper('cms')->__('New Page');
        $this->_initAction()->_addBreadcrumb($label, $label);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->getLayout()->getBlock('root')->setTemplate('bubble/cmstree/cms/page/ajax-edit.phtml');
        }

        $this->renderLayout();
    }

    public function treeAction()
    {
        $storeId = (int) $this->getRequest()->getParam('store');
        $pageId = (int) $this->getRequest()->getParam('id');

        if ($storeId) {
            if (!$pageId) {
                $rootId = Mage::getResourceModel('cms/page')->getStoreRootId($storeId);
                $this->getRequest()->setParam('id', $rootId);
            }
            Mage::getSingleton('admin/session')->setCmsLastViewedStore($storeId);
        }

        $page = $this->_initPage();
        $block = $this->getLayout()->createBlock('bubble_cmstree/adminhtml_cms_page_tree');
        $root = $block->getRoot();
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
            'data' => $block->getTree(),
            'parameters' => array(
                'text'          => $block->buildNodeName($root),
                'draggable'     => false,
                'allowDrop'     => ($root->getIsVisible()) ? true : false,
                'id'            => (int) $root->getId(),
                'expanded'      => (int) $block->getIsWasExpanded(),
                'store_id'      => (int) $block->getStore()->getId(),
                'page_id'       => (int) $page->getId(),
                'root_visible'  => (int) $root->getIsVisible()
            )
        )));
    }

    public function moveAction()
    {
        $page = $this->_initPage();
        if (!$page) {
            $this->getResponse()->setBody(Mage::helper('cms')->__('Page move error'));
            return;
        }

        // new parent page identifier
        $parentNodeId   = $this->getRequest()->getPost('pid', false);

        // page id after which we have put our page
        $prevNodeId     = $this->getRequest()->getPost('aid', false);

        try {
            $page->move($parentNodeId, $prevNodeId);
            $this->getResponse()->setBody('SUCCESS');
        } catch (Mage_Core_Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        } catch (Exception $e){
            $this->getResponse()->setBody(Mage::helper('cms')->__('Page move error' . $e));
            Mage::logException($e);
        }
    }

    public function pagesJsonAction()
    {
        if ($this->getRequest()->getParam('expand_all')) {
            Mage::getSingleton('admin/session')->setIsTreeWasExpanded(true);
        } else {
            Mage::getSingleton('admin/session')->setIsTreeWasExpanded(false);
        }
        if ($pageId = (int) $this->getRequest()->getPost('id')) {
            $this->getRequest()->setParam('id', $pageId);
            if (!$page = $this->_initPage()) {
                return;
            }
            $this->getResponse()->setBody(
                $this->getLayout()->createBlock('bubble_cmstree/adminhtml_cms_page_tree')->getTreeJson($page)
            );
        }
    }

    public function saveAction()
    {
        // check if data has been sent
        if ($data = $this->getRequest()->getPost()) {
            $data = $this->_filterPostData($data);

            // init model and set data
            $model = Mage::getModel('cms/page');



			if(isset($data['page_image']['delete']) && $data['page_image']['delete']=='1'){
				if(!empty($data['page_image']['value'])){
					$path = Mage::getBaseDir('media') . DS ;
					if(@unlink($path.$data['page_image']['value'])){
					$data['page_image']='';
					}
				}
			}



			if(isset($_FILES['page_image']['name']) && !empty($_FILES['page_image']['name'])) {

				try {
					$uploader = new Varien_File_Uploader('page_image');
					$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png')); // or pdf or anything
					$uploader->setAllowRenameFiles(true);
					// setAllowRenameFiles(true) -> move your file in a folder the magento way
					// setAllowRenameFiles(false) -> move your file directly in the $path folder
					$uploader->setFilesDispersion(true);
					$path = Mage::getBaseDir('media') . DS . 'cms' . DS;

					//$uploader->saveresized($path, $_FILES['nfile']['name'],100,72);
					//$_tmp_nfilethumb = $uploader->getUploadedFileName();

					$uploader->save($path, $_FILES['page_image']['name']);
					$_tmp_nfile = $uploader->getUploadedFileName();

					//$data['nfilethumb'] = $_tmp_nfilethumb;
					$data['page_image'] = 'cms'.$_tmp_nfile;

				}catch(Exception $e) {

				}
			}elseif(isset($data['page_image']['value']) && !empty($data['page_image']['value'])){
				$data['page_image']=$data['page_image']['value'];
			}

//			var_dump($data['page_image']);
//			var_dump($_FILES['page_image']);
//			exit();




            if ($id = $this->getRequest()->getParam('page_id')) {
                $model->load($id);
            }

            $data['parent_id'] = $this->getRequest()->getParam('parent');
            $data['store_id'] = $this->getRequest()->getParam('store');
            if (!isset($data['stores'])) {
                $data['stores'] = array();
            }

            $model->addData($data);

            Mage::dispatchEvent('cms_page_prepare_save', array('page' => $model, 'request' => $this->getRequest()));

            // try to save data
            try {
                $model->save();
                Mage::getSingleton('admin/session')->setLastEditedPage($model->getId());

                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('cms')->__('The page has been saved.')
                );

                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                // go to grid
                $params = array();
                if (isset($data['tab']) && !empty($data['tab'])) {
                    $params['tab'] = $data['tab'];
                }
                $this->_redirect('*/*/', $params);

                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('cms')->__('An error occurred while saving the page.'));
            }

            $this->_getSession()->setFormData($data);
            $this->_redirect('*/*/index', array('store' => $data['store_id']));

            return;
        }

        $this->_redirect('*/*/');
    }

    public function switchAction()
    {
        Mage::getSingleton('admin/session')
            ->unsCmsLastViewedStore()
            ->unsLastEditedPage();

        $this->_forward('index');
    }
}