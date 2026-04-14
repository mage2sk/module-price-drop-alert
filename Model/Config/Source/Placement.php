<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Placement Source Model for Admin Configuration
 */

namespace Panth\PriceDropAlert\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Placement implements OptionSourceInterface
{
    /**
     * Placement option constants
     */
    const AFTER_PRICE = 'after_price';
    const ABOVE_ADD_TO_CART = 'above_add_to_cart';
    const BELOW_ADD_TO_CART = 'below_add_to_cart';
    const ABOVE_DESCRIPTION = 'above_description';
    const BELOW_DESCRIPTION = 'below_description';

    /**
     * Get placement options for admin configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AFTER_PRICE,
                'label' => __('After Price (Default)')
            ],
            [
                'value' => self::ABOVE_ADD_TO_CART,
                'label' => __('Above Add to Cart Button')
            ],
            [
                'value' => self::BELOW_ADD_TO_CART,
                'label' => __('Below Add to Cart Button')
            ],
            [
                'value' => self::ABOVE_DESCRIPTION,
                'label' => __('Above Product Description')
            ],
            [
                'value' => self::BELOW_DESCRIPTION,
                'label' => __('Below Product Description')
            ]
        ];
    }

    /**
     * Get placement options as array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::AFTER_PRICE => __('After Price (Default)'),
            self::ABOVE_ADD_TO_CART => __('Above Add to Cart Button'),
            self::BELOW_ADD_TO_CART => __('Below Add to Cart Button'),
            self::ABOVE_DESCRIPTION => __('Above Product Description'),
            self::BELOW_DESCRIPTION => __('Below Product Description')
        ];
    }

    /**
     * Get container name based on placement value
     *
     * @param string $placement
     * @return array [container, position]
     */
    public function getContainerConfig($placement)
    {
        switch ($placement) {
            case self::AFTER_PRICE:
                return [
                    'container' => 'product.info.main',
                    'position' => 'after',
                    'sibling' => 'product.info.price'
                ];
            case self::ABOVE_ADD_TO_CART:
                return [
                    'container' => 'product.info.main',
                    'position' => 'before',
                    'sibling' => 'product.info.addtocart'
                ];
            case self::BELOW_ADD_TO_CART:
                return [
                    'container' => 'product.info.main',
                    'position' => 'after',
                    'sibling' => 'product.info.addtocart.additional'
                ];
            case self::ABOVE_DESCRIPTION:
                return [
                    'container' => 'product.info.main',
                    'position' => 'before',
                    'sibling' => 'product.info.overview'
                ];
            case self::BELOW_DESCRIPTION:
                return [
                    'container' => 'product.info.main',
                    'position' => 'after',
                    'sibling' => 'product.info.details'
                ];
            default:
                return [
                    'container' => 'product.info.main',
                    'position' => 'after',
                    'sibling' => 'product.info.price'
                ];
        }
    }
}
