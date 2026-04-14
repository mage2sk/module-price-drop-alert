<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 *
 * Smart price resolver for all product types.
 * Returns the "customer-facing" price — what a customer would actually pay.
 *
 * Simple/Virtual/Downloadable → getFinalPrice() (includes catalog rules, special price)
 * Configurable → minimum child getFinalPrice() (the "As low as $X" price)
 * Bundle → minimum calculated price from price model
 * Grouped → minimum associated simple product price
 */
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class PriceResolver
{
    private ProductRepositoryInterface $productRepository;
    private LoggerInterface $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    /**
     * Get the customer-facing price for any product type.
     *
     * @param ProductInterface $product
     * @return float
     */
    public function getPrice(ProductInterface $product): float
    {
        $typeId = $product->getTypeId();

        switch ($typeId) {
            case 'configurable':
                return $this->getConfigurablePrice($product);
            case 'bundle':
                return $this->getBundlePrice($product);
            case 'grouped':
                return $this->getGroupedPrice($product);
            default:
                // simple, virtual, downloadable
                return $this->getSimplePrice($product);
        }
    }

    /**
     * Get price by product ID (loads the product first).
     *
     * @param int $productId
     * @return float
     */
    public function getPriceById(int $productId): float
    {
        try {
            $product = $this->productRepository->getById($productId);
            return $this->getPrice($product);
        } catch (\Exception $e) {
            $this->logger->error('PriceResolver: Cannot load product #' . $productId . ': ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Simple/Virtual/Downloadable: use getFinalPrice() which includes
     * catalog price rules, special prices, and tier prices.
     */
    private function getSimplePrice(ProductInterface $product): float
    {
        $price = (float) $product->getFinalPrice();
        if ($price <= 0) {
            $price = (float) $product->getPrice();
        }
        return $price;
    }

    /**
     * Configurable: the parent has no price of its own.
     * Get the minimum finalPrice across all enabled simple children.
     * This matches what the customer sees as "As low as $X".
     */
    private function getConfigurablePrice(ProductInterface $product): float
    {
        // First try the price model (works when product is fully loaded)
        $priceInfo = $product->getPriceInfo();
        if ($priceInfo) {
            $finalPrice = $priceInfo->getPrice('final_price');
            if ($finalPrice) {
                $amount = (float) $finalPrice->getMinimalPrice()->getValue();
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        // Fallback: iterate children
        try {
            $children = $product->getTypeInstance()->getUsedProducts($product);
            $minPrice = PHP_FLOAT_MAX;
            foreach ($children as $child) {
                if ((int) $child->getStatus() !== \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                    continue;
                }
                $childPrice = (float) $child->getFinalPrice();
                if ($childPrice <= 0) {
                    $childPrice = (float) $child->getPrice();
                }
                if ($childPrice > 0 && $childPrice < $minPrice) {
                    $minPrice = $childPrice;
                }
            }
            return $minPrice < PHP_FLOAT_MAX ? $minPrice : 0.0;
        } catch (\Exception $e) {
            $this->logger->error('PriceResolver: Error getting configurable children for #' . $product->getId() . ': ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Bundle: use the price model to get the minimum calculated price.
     * Bundle products have dynamic pricing based on selected options.
     * We use the minimum price (cheapest combination).
     */
    private function getBundlePrice(ProductInterface $product): float
    {
        $priceInfo = $product->getPriceInfo();
        if ($priceInfo) {
            // Try final_price first
            $finalPrice = $priceInfo->getPrice('final_price');
            if ($finalPrice) {
                $amount = (float) $finalPrice->getMinimalPrice()->getValue();
                if ($amount > 0) {
                    return $amount;
                }
            }

            // Try regular_price
            $regularPrice = $priceInfo->getPrice('regular_price');
            if ($regularPrice) {
                $amount = (float) $regularPrice->getMinimalPrice()->getValue();
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        // Fallback to base price
        return $this->getSimplePrice($product);
    }

    /**
     * Grouped: the parent has no price.
     * Get the minimum price from associated simple products.
     * This matches the "Starting at $X" display on the frontend.
     */
    private function getGroupedPrice(ProductInterface $product): float
    {
        try {
            $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            $minPrice = PHP_FLOAT_MAX;
            foreach ($associatedProducts as $associated) {
                $assocPrice = (float) $associated->getFinalPrice();
                if ($assocPrice <= 0) {
                    $assocPrice = (float) $associated->getPrice();
                }
                if ($assocPrice > 0 && $assocPrice < $minPrice) {
                    $minPrice = $assocPrice;
                }
            }
            return $minPrice < PHP_FLOAT_MAX ? $minPrice : 0.0;
        } catch (\Exception $e) {
            $this->logger->error('PriceResolver: Error getting grouped children for #' . $product->getId() . ': ' . $e->getMessage());
            return 0.0;
        }
    }
}
