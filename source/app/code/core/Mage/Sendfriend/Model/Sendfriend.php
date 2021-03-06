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
 * @package     Mage_Sendfriend
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Sendfriend_Model_Sendfriend extends Mage_Core_Model_Abstract
{
    /**
     * Recipient Names
     *
     * @var array
     */
    protected $_names   = array();

    /**
     * Recipient Emails
     *
     * @var array
     */
    protected $_emails  = array();

    /**
     * Sender data array
     *
     * @var array
     */
    protected $_sender  = array();

    /**
     * Product Instance
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_product;

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_init('sendfriend/sendfriend');
    }

    /**
     * Retrieve Data Helper
     *
     * @return Mage_Sendfriend_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('sendfriend');
    }

    /**
     * Retrieve Option Array
     *
     * @deprecated It Is a not Source model
     * @return array
     */
    public function toOptionArray()
    {
        return array();
    }

    public function send()
    {
        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);

        /* @var $mailTemplate Mage_Core_Model_Email_Template */
        $mailTemplate = Mage::getModel('core/email_template');

        $message = nl2br(htmlspecialchars($this->getSender()->getMessage()));
        $sender  = array(
            'name'  => $this->_getHelper()->htmlEscape($this->getSender()->getName()),
            'email' => $this->_getHelper()->htmlEscape($this->getSender()->getEmail())
        );

        $mailTemplate->setDesignConfig(array(
            'area'  => 'frontend',
            'store' => Mage::app()->getStore()->getId()
        ));

        foreach ($this->getRecipients()->getEmails() as $k => $email) {
            $name = $this->getRecipients()->getNames($k);
            $mailTemplate->sendTransactional(
                $this->getTemplate(),
                $sender,
                $email,
                $name,
                array(
                    'name'          => $name,
                    'email'         => $email,
                    'product_name'  => $this->getProduct()->getName(),
                    'product_url'   => $this->getProduct()->getUrlInStore(),
                    'message'       => $message,
                    'sender_name'   => $sender['name'],
                    'sender_email'  => $sender['email'],
                    'product_image' => Mage::helper('catalog/image')->init($this->getProduct(),
                        'small_image')->resize(75),
                )
            );
        }

        $translate->setTranslateInline(true);

        return $this;
    }

    /**
     * Validate Form data
     *
     * @return bool|array
     */
    public function validate()
    {
        $errors = array();

        $name = $this->getSender()->getName();
        if (empty($name)) {
            $errors[] = Mage::helper('sendfriend')->__('Sender name can\'t be empty');
        }

        $email = $this->getSender()->getEmail();
        if (empty($email) OR !Zend_Validate::is($email, 'EmailAddress')) {
            $errors[] = Mage::helper('sendfriend')->__('Invalid sender email');
        }

        $message = $this->getSender()->getMessage();
        if (empty($message)) {
            $errors[] = Mage::helper('sendfriend')->__('Message can\'t be empty');
        }

        if (!$this->getRecipients()->getEmails()) {
            $errors[] = Mage::helper('sendfriend')->__('You have to specify at least one recipient');
        }

        // validate recipients email addresses
        foreach ($this->getRecipients()->getEmails() as $email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $errors[] = Mage::helper('sendfriend')->__('You input invalid email address for recipient');
                break;
            }
        }

        switch ($this->_getHelper()->getLimitBy()) {
            case Mage_Sendfriend_Helper_Data::CHECK_COOKIE:
                $amount = $this->_amountByCookies();
                break;

            case Mage_Sendfriend_Helper_Data::CHECK_IP:
                $amount = $this->_amountByIp();
                break;
            default:
                $amount = 0;
                break;
        }

        if ($amount >= $this->getMaxSendsToFriend()){
            $errors[] = Mage::helper('sendfriend')->__('You have exceeded limit of %d sends in an hour', $this->getMaxSendsToFriend());
        }

        $maxRecipients = $this->getMaxRecipients();
        if (count($this->getRecipients()->getEmails()) > $maxRecipients) {
            $errors[] = Mage::helper('sendfriend')->__('You cannot send more than %d emails at a time', $this->getMaxRecipients());
        }

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    /**
     * Set cookie instance
     *
     * @param Mage_Core_Model_Cookie $product
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function setCookie($cookie)
    {
        return $this->setData('_cookie', $cookie);
    }

    /**
     * Retrieve Cookie instance
     *
     * @throws Mage_Core_Exception
     * @return Mage_Core_Model_Cookie
     */
    public function getCookie()
    {
        $cookie = $this->_getData('_cookie');
        if (!$cookie instanceof Mage_Core_Model_Cookie) {
            Mage::throwException(Mage::helper('sendfriend')->__('Please define correct Cookie instance'));
        }
        return $cookie;
    }

    /**
     * Set Visitor Remote Address
     *
     * @param int $ipAddr the IP address on Long Format
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function setRemoteAddr($ipAddr)
    {
        $this->setData('_remote_addr', $ipAddr);
        return $this;
    }

    /**
     * Retrieve Visitor Remote Address
     *
     * @return int
     */
    public function getRemoteAddr()
    {
        return $this->_getData('_remote_addr');
    }

    /**
     * Set Recipients
     *
     * @param array $recipients
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function setRecipients($recipients)
    {
        // validate array
        if (!is_array($recipients) OR !isset($recipients['email'])
            OR !isset($recipients['name']) OR !is_array($recipients['email'])
            OR !is_array($recipients['name'])) {
            return $this;
        }

        $emails = array();
        $names  = array();
        foreach ($recipients['email'] as $k => $email) {
            if (!isset($emails[$email]) && isset($recipients['name'][$k])) {
                $emails[$email] = true;
                $names[] = $recipients['name'][$k];
            }
        }

        if ($emails) {
            $emails = array_keys($emails);
        }

        return $this->setData('_recipients', new Varien_Object(array(
            'emails' => $emails,
            'names'  => $names
        )));
    }

    /**
     * Retrieve Recipients object
     *
     * @return Varien_Object
     */
    public function getRecipients()
    {
        $recipients = $this->_getData('_recipients');
        if (!$recipients instanceof Varien_Object) {
            $recipients =  new Varien_Object(array(
                'emails' => array(),
                'names'  => array()
            ));
            $this->setData('_recipients', $recipients);
        }
        return $recipients;
    }

    /**
     * Set product instance
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function setProduct($product)
    {
        return $this->setData('_product', $product);
    }

    /**
     * Retrieve Product instance
     *
     * @throws Mage_Core_Exception
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        $product = $this->_getData('_product');
        if (!$product instanceof Mage_Catalog_Model_Product) {
            Mage::throwException(Mage::helper('sendfriend')->__('Please define correct Product instance'));
        }
        return $product;
    }

    /**
     * Set Sender Information array
     *
     * @param array $sender
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function setSender($sender)
    {
        if (!is_array($sender)) {
            Mage::helper('sendfriend')->__('Invalid Sender information');
        }

        return $this->setData('_sender', new Varien_Object($sender));
    }

    /**
     * Retrieve Sender Information Object
     *
     * @throws Mage_Core_Exception
     * @return Varien_Object
     */
    public function getSender()
    {
        $sender = $this->_getData('_sender');
        if (!$sender instanceof Varien_Object) {
            Mage::throwException(Mage::helper('sendfriend')->__('Please define correct Sender information'));
        }
        return $sender;
    }

    /**
     * Retrieve Send count by IP
     *
     * @param int $ip
     * @param int $startTime
     * @return int
     */
    public function getSendCount($ip = null, $startTime = null)
    {
        if (is_null($ip)) {
            $ip = $this->getRemoteAddr();
        }
        if (is_null($startTime)) {
            $startTime = time() - $this->_getHelper()->getPeriod();
        }

        return $this->_getResource()->getSendCount($this, $ip, $startTime);
    }

    /**
     * Get max allowed uses of "Send to Friend" function per hour
     *
     * @return integer
     */
    public function getMaxSendsToFriend()
    {
        return $this->_getHelper()->getMaxEmailPerPeriod();
    }

    /**
     * Get current Email "Send to friend" template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->_getHelper()->getEmailTemplate();
    }

    /**
     * Get max allowed recipients for "Send to a Friend" function
     *
     * @return integer
     */
    public function getMaxRecipients()
    {
        return $this->_getHelper()->getMaxRecipients();
    }

    /**
     * Check if user is allowed to email product to a friend
     *
     * @return boolean
     */
    public function canEmailToFriend()
    {
        return $this->_getHelper()->isEnabled();
    }

    /**
     * Retrieve amount by cookie
     *
     * @return int
     */
    protected function _amountByCookies()
    {
        $cookie   = $this->_getHelper()->getCookieName();
        $time     = time();
        $newTimes = array();
        $oldTimes = $this->getCookie()->get($cookie);
        if ($oldTimes) {
            $oldTimes = explode(',', $oldTimes);
            foreach ($oldTimes as $oldTime) {
                $periodTime = $time - $this->_getHelper()->getPeriod();
                if (is_numeric($oldTime) AND $oldTime >= $periodTime) {
                    $newTimes[] = $oldTime;
                }
            }
        }

        $amount = count($newTimes);
        $newTimes[] = $time;

        $this->getCookie()->set($cookie, implode(',', $newTimes));

        return $amount;
    }

    /**
     * Retrieve amount by IP address
     *
     * @return int
     */
    protected function _amountByIp()
    {
        $time   = time();
        $period = $this->_getHelper()->getPeriod();

        // delete expired logs
        $this->_getResource()->deleteLogsBefore($time - $period);

        $amount = $this->getSendCount($this->_ip, time() - $this->_period);

        $this->setIp($this->getRemoteAddr())
            ->setTime($time)
            ->save();

        return $amount;
    }

    /**
     * Register self in global register with name send_to_friend_model
     *
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    public function register()
    {
        if (!Mage::registry('send_to_friend_model')) {
            Mage::register('send_to_friend_model', $this);
        }
        return $this;
    }
}
