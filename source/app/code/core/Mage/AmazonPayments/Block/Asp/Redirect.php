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
 * @package     Mage_AmazonPayments
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * AmazonPayments Form Block
 *
 * @category    Mage
 * @package     Mage_AmazonPayments
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_AmazonPayments_Block_Asp_Redirect extends Mage_Core_Block_Template
{

    /**
     * Return Amazon Simple Pay payment model
     *
     * @return Mage_AmazonPayments_Model_Payment_Asp
     */
    public function getPayment()
    {
        return Mage::registry('amazonpayments_payment_asp');
    }

    /**
     * Return Amazon Simple Pay payment url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->getPayment()->getPayRedirectUrl();
    }

    /**
     * Return pay params for current order
     *
     * @return array
     */
    public function getRedirectParams()
    {
        return $this->getPayment()->getPayRedirectParams();
    }

    /**
     * Return redirect message
     *
     * @return string
     */
    public function getRedirectMessage()
    {
        return $this->getPayment()->getRedirectMessage();
    }
    
}
