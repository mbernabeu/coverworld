<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Feed
 */


namespace Amasty\Feed\Block\Adminhtml\Feed\Edit\Tab\Xml;

use Magento\Backend\Block\Widget;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Content extends \Amasty\Feed\Block\Adminhtml\Feed\Edit\Tab\Content
{
    protected $_template = 'feed/xml.phtml';

    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Insert'), 'id' => 'insert_button', 'class' => 'add']
        );

        $this->setChild('insert_button', $button);

        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            ['label' => __('Update'), 'id' => 'update_button', 'class' => 'add hidden']
        );

        $this->setChild('update_button', $button);


        return parent::_prepareLayout();
    }

    public function getInsertButtonHtml()
    {
        return $this->getChildHtml('insert_button');
    }

    public function getUpdateButtonHtml()
    {
        return $this->getChildHtml('update_button');
    }
}