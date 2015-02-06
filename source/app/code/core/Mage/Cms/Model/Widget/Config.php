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
 * @package     Mage_Cms
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Widgets Insertion Plugin Config for Editor HTML Element
 *
 * @category    Mage
 * @package     Mage_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Cms_Model_Widget_Config extends Varien_Object
{

    /**
     * Return config settings for widgets insertion plugin based on editor element config
     *
     * @param Varien_Object $config
     * @return array
     */
    public function getPluginSettings($config)
    {
        $settings = array(
            'widget_plugin_src'             => Mage::getBaseUrl('js').'mage/adminhtml/wysiwyg/tiny_mce/plugins/magentowidget/editor_plugin.js',
            'widget_images_url'             => $this->getPlaceholderImagesBaseUrl(),
            'widget_placeholders'           => $this->getAvailablePlaceholderFilenames(),
            'widget_window_url'             => $this->getWidgetWindowUrl($config),
            'widget_window_no_wysiwyg_url'  => $this->getWidgetWindowUrl($config, false),
        );

        return $settings;
    }

    /**
     * Return Widget placeholders images URL
     *
     * @return string
     */
    public function getPlaceholderImagesBaseUrl()
    {
        return Mage::getDesign()->getSkinUrl('images/widget/');
    }

    /**
     * Return Widget placeholders images dir
     *
     * @return string
     */
    public function getPlaceholderImagesBaseDir()
    {
        return Mage::getDesign()->getSkinBaseDir() . DS . 'images' . DS . 'widget';
    }

    /**
     * Return list of existing widget image placeholders
     *
     * @return array
     */
    public function getAvailablePlaceholderFilenames()
    {
        $collection = new Varien_Data_Collection_Filesystem();
        $collection->addTargetDir($this->getPlaceholderImagesBaseDir())
            ->setCollectDirs(false)
            ->setCollectFiles(true)
            ->setCollectRecursively(false);
        $result = array();
        foreach ($collection as $file) {
            $result[] = $file->getBasename();
        }
        return $result;
    }

    /**
     * Return Widgets Insertion Plugin Window URL
     *
     * @param Varien_Object Editor element config
     * @param array $params URL params
     * @return string
     */
    public function getWidgetWindowUrl($config, $wysiwygMode = true)
    {
        $params = $wysiwygMode ? array() : array('no_wysiwyg' => true);

        if ($config->hasData('skip_widgets')) {
            $params['skip_widgets'] = $this->encodeWidgetsToQuery($config->getData('skip_widgets'));
        }

        return Mage::getSingleton('adminhtml/url')->getUrl('*/cms_widget/index', $params);
    }

    /**
     * Encode list of widget types into query param
     *
     * @param array $widgets List of widgets
     * @return string Query param value
     */
    public function encodeWidgetsToQuery($widgets)
    {
        $widgets = is_array($widgets) ? $widgets : array($widgets);
        $param = implode(',', $widgets);
        return Mage::helper('core')->urlEncode($param);
    }

    /**
     * Decode URL query param and return list of widgets
     *
     * @param string $queryParam Query param value to decode
     * @return array Array of widget types
     */
    public function decodeWidgetsFromQuery($queryParam)
    {
        $param = Mage::helper('core')->urlDecode($queryParam);
        return preg_split('/\s*\,\s*/', $param, 0, PREG_SPLIT_NO_EMPTY);
    }

}