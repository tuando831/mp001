<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Adminhtml_Promo_QuoteController extends Mage_Adminhtml_Controller_Action
{
    protected function _initRule()
    {
        Mage::register('current_promo_quote_rule', Mage::getModel('salesrule/rule'));
        if ($id = (int) $this->getRequest()->getParam('id')) {
            Mage::registry('current_promo_quote_rule')
                ->load($id);
        }
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promo/quote')
            ->_addBreadcrumb(Mage::helper('salesrule')->__('Promotions'), Mage::helper('salesrule')->__('Promotions'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb(Mage::helper('salesrule')->__('Catalog'), Mage::helper('salesrule')->__('Catalog'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('salesrule/rule');

        if ($id) {
            $model->load($id);
            if (! $model->getRuleId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('salesrule')->__('This rule no longer exists'));
                $this->_redirect('*/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        $model->getActions()->setJsFormObject('rule_actions_fieldset');

        Mage::register('current_promo_quote_rule', $model);

        $this->_initAction()->getLayout()->getBlock('promo_quote_edit')
             ->setData('action', $this->getUrl('*/*/save'));

        $this
            ->_addBreadcrumb($id ? Mage::helper('salesrule')->__('Edit Rule') : Mage::helper('salesrule')->__('New Rule'), $id ? Mage::helper('salesrule')->__('Edit Rule') : Mage::helper('salesrule')->__('New Rule'))
            ->renderLayout();

    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                $model = Mage::getModel('salesrule/rule');
                Mage::dispatchEvent('adminhtml_controller_salesrule_prepare_save', array('request' => $this->getRequest()));
                $data = $this->getRequest()->getPost();
                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        Mage::throwException(Mage::helper('salesrule')->__('Wrong rule specified.'));
                    }
                }
                if (isset($data['simple_action']) && $data['simple_action'] == 'by_percent' && isset($data['discount_amount'])) {
                    $data['discount_amount'] = min(100,$data['discount_amount']);
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);
                $model->loadPost($data);

                $session = Mage::getSingleton('adminhtml/session');
                $session->setPageData($model->getData());

                $model->save();
                $session->addSuccess(Mage::helper('salesrule')->__('Rule was successfully saved'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('catalogrule')->__('Error while saving rule data. Please review log and try again.'));
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                 $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('salesrule/rule');
                $model->load($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('salesrule')->__('Rule was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('catalogrule')->__('Error while deleting rule. Please review log and try again.'));
                Mage::logException($e);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('salesrule')->__('Unable to find a page to delete'));
        $this->_redirect('*/*/');
    }

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('salesrule/rule'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function newActionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('salesrule/rule'))
            ->setPrefix('actions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function applyRulesAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->_initRule()->loadLayout()->renderLayout();

    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo/quote');
    }
}
