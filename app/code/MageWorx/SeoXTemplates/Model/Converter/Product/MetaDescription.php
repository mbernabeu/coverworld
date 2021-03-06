<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\SeoXTemplates\Model\Converter\Product;

use MageWorx\SeoXTemplates\Model\Converter\Product as ConverterProduct;

class MetaDescription extends ConverterProduct
{
    /**
     *
     * @param string $convertValue
     * @return string
     */
    protected function _render($convertValue)
    {
        $convertValue = parent::_render($convertValue);
        $convertValue = strip_tags($convertValue);

        if ($this->helperData->isCropMetaDescription($this->_item->getStoreId())) {
            $length       = $this->helperData->getMaxLengthMetaDescription($this->_item->getStoreId());
            $convertValue = mb_substr($convertValue, 0, $length);
        }
        return trim($convertValue);
    }

}