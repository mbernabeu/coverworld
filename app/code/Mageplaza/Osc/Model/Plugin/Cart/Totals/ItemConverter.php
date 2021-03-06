<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Osc
 * @copyright   Copyright (c) 2016 Mageplaza (http://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\Osc\Model\Plugin\Cart\Totals;


/**
 * Class ItemConverter
 * @package Mageplaza\Osc\Model\Plugin\Cart\Totals
 */
class ItemConverter
{

    public function afterModelToDataObject(
        \Magento\Quote\Model\Cart\Totals\ItemConverter $subject,
        $item
    ){

        $options= $item->getOptions();
        $options=  \Zend_Json::decode($options);

        if(isset( $options['fitment'])){
            $temp= $options['fitment'];
            unset( $options['fitment']);
             $options[]= $temp;
        }
        $options=  \Zend_Json::encode($options);
        $item->setOptions($options);
        return $item;
    }
}
