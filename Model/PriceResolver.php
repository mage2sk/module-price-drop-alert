<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

class PriceResolver
{
    private ProductRepositoryInterface $productRepository;
    private LoggerInterface $logger;
    private CustomerRepositoryInterface $customerRepository;
    private array $groupByCustomer = [];

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
    }

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

                return $this->getSimplePrice($product);
        }
    }

    public function getPriceById(int $productId, ?int $storeId = null, ?int $customerGroupId = null): float
    {
        try {
            if ($customerGroupId !== null && $customerGroupId > 0) {
                $product = $this->productRepository->getById($productId, false, $storeId, true);
                return $this->getGroupPrice($product, $customerGroupId);
            }
            $product = $this->productRepository->getById($productId, false, $storeId);
            return $this->getPrice($product);
        } catch (\Exception $e) {
            $this->logger->error('PriceResolver: Cannot load product #' . $productId . ': ' . $e->getMessage());
            return 0.0;
        }
    }

    public function getPriceForAlert(PriceAlert $alert): float
    {
        return $this->getPriceById(
            (int) $alert->getProductId(),
            (int) $alert->getStoreId(),
            $this->getCustomerGroupId((int) $alert->getCustomerId())
        );
    }

    public function getCustomerGroupId(int $customerId): ?int
    {
        if ($customerId <= 0) {
            return null;
        }
        if (!array_key_exists($customerId, $this->groupByCustomer)) {
            try {
                $this->groupByCustomer[$customerId] = (int) $this->customerRepository->getById($customerId)->getGroupId();
            } catch (\Exception $e) {
                $this->groupByCustomer[$customerId] = null;
            }
        }
        return $this->groupByCustomer[$customerId];
    }

    private function getGroupPrice(ProductInterface $product, int $customerGroupId): float
    {
        switch ($product->getTypeId()) {
            case 'configurable':
                $children = $product->getTypeInstance()->getUsedProducts($product);
                return $this->getLowestChildPrice($children, $customerGroupId, true);
            case 'grouped':
                $children = $product->getTypeInstance()->getAssociatedProducts($product);
                return $this->getLowestChildPrice($children, $customerGroupId, false);
            case 'bundle':
                return $this->getBundlePrice($product);
            default:
                $product->setCustomerGroupId($customerGroupId);
                return $this->getSimplePrice($product);
        }
    }

    private function getLowestChildPrice(array $children, int $customerGroupId, bool $enabledOnly): float
    {
        $minPrice = PHP_FLOAT_MAX;
        foreach ($children as $child) {
            if ($enabledOnly
                && (int) $child->getStatus() !== \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            ) {
                continue;
            }
            $child->setCustomerGroupId($customerGroupId);
            $child->unsetData('final_price');
            $child->unsetData('calculated_final_price');
            $childPrice = $this->getSimplePrice($child);
            if ($childPrice > 0 && $childPrice < $minPrice) {
                $minPrice = $childPrice;
            }
        }
        return $minPrice < PHP_FLOAT_MAX ? $minPrice : 0.0;
    }

    private function getSimplePrice(ProductInterface $product): float
    {
        $price = (float) $product->getFinalPrice();
        if ($price <= 0) {
            $price = (float) $product->getPrice();
        }
        return $price;
    }

    private function getConfigurablePrice(ProductInterface $product): float
    {
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

    private function getBundlePrice(ProductInterface $product): float
    {
        $priceInfo = $product->getPriceInfo();
        if ($priceInfo) {
            $finalPrice = $priceInfo->getPrice('final_price');
            if ($finalPrice) {
                $amount = (float) $finalPrice->getMinimalPrice()->getValue();
                if ($amount > 0) {
                    return $amount;
                }
            }

            $regularPrice = $priceInfo->getPrice('regular_price');
            if ($regularPrice) {
                $amount = (float) $regularPrice->getMinimalPrice()->getValue();
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        return $this->getSimplePrice($product);
    }

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
