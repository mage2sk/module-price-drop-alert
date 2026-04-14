<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Display Position Source Model for Admin Configuration
 */

namespace Panth\PriceDropAlert\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayPosition implements OptionSourceInterface
{
    /**
     * Display position option constants
     */
    const ABOVE_ADD_TO_CART = 'above_add_to_cart';
    const BELOW_ADD_TO_CART = 'below_add_to_cart';
    const ABOVE_DESCRIPTION = 'above_description';
    const BELOW_DESCRIPTION = 'below_description';
    const IN_PRODUCT_INFO_COLUMN = 'in_product_info_column';
    const CUSTOM_POSITION = 'custom_position';

    /**
     * Get display position options for admin configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
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
            ],
            [
                'value' => self::IN_PRODUCT_INFO_COLUMN,
                'label' => __('In Product Info Column (after price)')
            ],
            [
                'value' => self::CUSTOM_POSITION,
                'label' => __('Custom Position (requires layout XML knowledge)')
            ]
        ];
    }

    /**
     * Get display position options as array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::ABOVE_ADD_TO_CART => __('Above Add to Cart Button'),
            self::BELOW_ADD_TO_CART => __('Below Add to Cart Button'),
            self::ABOVE_DESCRIPTION => __('Above Product Description'),
            self::BELOW_DESCRIPTION => __('Below Product Description'),
            self::IN_PRODUCT_INFO_COLUMN => __('In Product Info Column (after price)'),
            self::CUSTOM_POSITION => __('Custom Position (requires layout XML knowledge)')
        ];
    }

    /**
     * Get container configuration based on display position value
     *
     * @param string $position
     * @return array [container, position, sibling]
     */
    public function getContainerConfig($position)
    {
        switch ($position) {
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
            case self::IN_PRODUCT_INFO_COLUMN:
                return [
                    'container' => 'product.info.main',
                    'position' => 'after',
                    'sibling' => 'product.info.price'
                ];
            case self::CUSTOM_POSITION:
                return [
                    'container' => 'custom',
                    'position' => 'custom',
                    'sibling' => null
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
