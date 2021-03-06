<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace MageWorx\SeoMarkup\Block\Head\SocialMarkup;

class Category extends \MageWorx\SeoMarkup\Block\Head\SocialMarkup
{
    /**
     * @var \MageWorx\SeoMarkup\Helper\Category
     */
    protected $helperCategory;

    /**
     * @var \MageWorx\SeoMarkup\Helper\Website
     */
    protected $helperWebsite;

    /**
     * Category constructor.
     * @param \MageWorx\SeoMarkup\Helper\Category $helperCategory
     * @param \MageWorx\SeoMarkup\Helper\Website $helperWebsite
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \MageWorx\SeoMarkup\Helper\Category $helperCategory,
        \MageWorx\SeoMarkup\Helper\Website $helperWebsite,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data
    ) {
        $this->helperCategory = $helperCategory;
        $this->helperWebsite  = $helperWebsite;
        parent::__construct($context, $data);
    }

    protected function getMarkupHtml()
    {
        if (!$this->helperCategory->isOgEnabled()) {
            return '';
        }

        return $this->getSocialCategoryInfo();
    }

    /**
     * @return string
     */
    protected function getSocialCategoryInfo()
    {
        $type        = 'product.group';
        $title       = $this->escapeHtml($this->pageConfig->getTitle()->get());
        $description = $this->escapeHtml($this->pageConfig->getDescription());
        $siteName    = $this->escapeHtml($this->helperWebsite->getName());

        list($urlRaw) = explode('?', $this->_urlBuilder->getCurrentUrl());
        $url = rtrim($urlRaw, '/');

        $html  = "\n<meta property=\"og:type\" content=\"" . $type . "\"/>\n";
        $html .= "<meta property=\"og:title\" content=\"" . $title . "\"/>\n";
        $html .= "<meta property=\"og:description\" content=\"" . $description . "\"/>\n";
        $html .= "<meta property=\"og:url\" content=\"" . $url . "\"/>\n";
        if ($siteName) {
            $html .= "<meta property=\"og:site_name\" content=\"" . $siteName . "\"/>\n";
        }

        return $html;
    }
}
