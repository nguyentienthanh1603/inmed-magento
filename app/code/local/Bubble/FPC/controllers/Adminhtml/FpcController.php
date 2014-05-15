<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Adminhtml_FpcController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Full Page Cache'));
        $this->loadLayout();
        $this->_setActiveMenu('system/fpc');
        $this->renderLayout();
    }

    public function actionsAction()
    {
        $this->_getSession()->setActiveSection('actions');
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newActionAction()
    {
        $this->_forward('editAction');
    }

    public function editActionAction()
    {
        $this->_getSession()->setActiveSection('actions');
        $id = $this->getRequest()->getParam('id');
        $action = Mage::getModel('bubble_fpc/action')->load($id);
        if ($id && !$action->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('bubble_fpc')->__('This action no longer exists.')
            );

            return $this->_redirect('*/*/');
        }

        Mage::register('fpc_action', $action);

        $this->_title($this->__('Edit Action'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bubble_fpc/adminhtml_action_edit'));
        $this->renderLayout();
    }

    public function saveActionAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $id = $this->getRequest()->getParam('action_id');
            $action = Mage::getModel('bubble_fpc/action')->load($id);
            if ($id && !$action->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('bubble_fpc')->__('This action no longer exists.')
                );

                return $this->_redirect('*/*/');
            }

            try {
                $action->setData($data);
                $action->save();

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('bubble_fpc')->__('The action has been saved.'));

                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $this->_redirect('*/*/editAction', array('id' => $this->getRequest()->getParam('id')));
            }
        } else {
            return $this->_redirect('*/*/');
        }
    }

    public function deleteActionAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('bubble_fpc/action');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('bubble_fpc')->__('The action has been deleted.'));

                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $this->_redirect('*/*/editAction', array('id' => $id));
            }
        }

        Mage::getSingleton('adminhtml/session')
            ->addError(Mage::helper('bubble_fpc')->__('Unable to find an action to delete.'));

        return $this->_redirect('*/*/');
    }

    public function blocksAction()
    {
        $this->_getSession()->setActiveSection('blocks');
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newBlockAction()
    {
        $this->_forward('editBlock');
    }

    public function editBlockAction()
    {
        $this->_getSession()->setActiveSection('blocks');
        $id = $this->getRequest()->getParam('id');
        $block = Mage::getModel('bubble_fpc/block')->load($id);
        if ($id && !$block->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('bubble_fpc')->__('This block no longer exists.')
            );

            return $this->_redirect('*/*/');
        }

        Mage::register('fpc_block', $block);

        $this->_title($this->__('Edit Block'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bubble_fpc/adminhtml_block_edit'));
        $this->renderLayout();
    }

    public function saveBlockAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $id = $this->getRequest()->getParam('block_id');
            $block = Mage::getModel('bubble_fpc/block')->load($id);
            if ($id && !$block->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('bubble_fpc')->__('This block no longer exists.')
                );

                return $this->_redirect('*/*/');
            }

            try {
                $block->setData($data);
                $block->save();

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('bubble_fpc')->__('The block has been saved.'));

                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $this->_redirect('*/*/editBlock', array('id' => $this->getRequest()->getParam('id')));
            }
        } else {
            return $this->_redirect('*/*/');
        }
    }

    public function deleteBlockAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('bubble_fpc/block');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('bubble_fpc')->__('The block has been deleted.'));

                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $this->_redirect('*/*/editBlock', array('id' => $id));
            }
        }

        Mage::getSingleton('adminhtml/session')
            ->addError(Mage::helper('bubble_fpc')->__('Unable to find a block to delete.'));

        return $this->_redirect('*/*/');
    }

    public function clearBlockAction()
    {
        $id = $this->getRequest()->getParam('id');
        $block = Mage::getModel('bubble_fpc/block')->load($id);
        if (!$block->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('bubble_fpc')->__('This block no longer exists.')
            );

            return $this->_redirect('*/*/');
        }

        $dynamicBlock = Mage::helper('bubble_fpc')->getBlockModel($block->getModel());
        if (!$dynamicBlock) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('bubble_fpc')->__('The block model cannot be instantiated.')
            );

            return $this->_redirect('*/*/');
        }

        try {
            $dynamicBlock->clearCache();

            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('bubble_fpc')->__(
                    "The cache of the block '%s' has been flushed.", $dynamicBlock->getName()
                ));

            return $this->_redirect('*/*/');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

            return $this->_redirect('*/*/');
        }
    }

    public function storesAction()
    {
        $this->_getSession()->setActiveSection('stores');
        $this->loadLayout();
        $this->renderLayout();
    }

    public function editStoreAction()
    {
        $this->_getSession()->setActiveSection('stores');
        $id = $this->getRequest()->getParam('id');
        $store = Mage::app()->getStore($id);
        if ($id && !$store->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('bubble_fpc')->__('This store no longer exists.')
            );

            return $this->_redirect('*/*/');
        }

        Mage::register('fpc_store', $store);

        $this->_title($this->__('Edit Store View'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('bubble_fpc/adminhtml_store_edit'));
        $this->renderLayout();
    }

    public function saveStoreAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $id = $this->getRequest()->getParam('store_id');
            $store = Mage::app()->getStore($id);
            if ($id && !$store->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('bubble_fpc')->__('This store no longer exists.')
                );

                return $this->_redirect('*/*/');
            }

            try {
                if ($data['is_active']) {
                    Mage::helper('bubble_fpc')->enableStoreCache($store);
                } else {
                    Mage::helper('bubble_fpc')->disableStoreCache($store);
                }

                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(Mage::helper('bubble_fpc')->__('The store has been saved.'));

                return $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());

                return $this->_redirect('*/*/editStore', array('id' => $this->getRequest()->getParam('id')));
            }
        } else {
            return $this->_redirect('*/*/');
        }
    }

    public function generateAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $store = Mage::app()->getStore($storeId);
        if (!$storeId || !$store->getId()) {
            Mage::getSingleton('adminhtml/session')->addError('Please specify a valid store for cache generation');
        }

        Mage::register('fpc_store', $store);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function generateUrlAction()
    {
        $url = $this->getRequest()->getParam('url');
        $result = array();
        if (!$url) {
            $result['error'] = true;
        } else {
            $store = $this->getRequest()->getParam('store');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if ($store) {
                curl_setopt($ch, CURLOPT_COOKIE, 'store=' . $store);
            }
            if (substr($url, 0, 5) === 'https') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }

            curl_exec($ch);

            if (!curl_errno($ch)) {
                $result = curl_getinfo($ch);
                curl_close($ch);
            }
        }

        $json = Mage::helper('core')->jsonEncode($result);

        return $this->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody($json);
    }
}