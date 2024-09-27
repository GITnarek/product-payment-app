<?php

namespace App\Service;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class PriceCalculatorService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @param int $productId
     * @param string $taxNumber
     * @param string $couponCode
     * @return float
     * @throws ProductNotFoundException
     * @throws Exception
     */
    public function calculateFinalPrice(int $productId, string $taxNumber, string $couponCode): float
    {
        $this->logger->debug("Calculating final price for $productId");

        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        if (!$product) {
            throw new ProductNotFoundException('Product not found.');
        }

        $discountValue = 0;
        if ($couponCode) {
            $discountValue = $this->getDiscountByCouponCode($couponCode);
        }
        $price = $product->getPrice();
        $taxRate = $this->getTaxRateByTaxNumber($taxNumber);
        $calculatedPrice = $price + $price * ( $taxRate - $discountValue) / 100 ;

        return round($calculatedPrice, 2);
    }

    /**
     * @param $taxNumber
     * @return float
     * @throws Exception
     */
    private function getTaxRateByTaxNumber($taxNumber): float
    {
        $this->logger->debug("Getting tax rate for $taxNumber");

        $countryCode = substr($taxNumber, 0, 2);
        return match ($countryCode) {
            'DE' => 19,
            'IT' => 22,
            'GR' => 24,
            'FR' => 20,
            default => throw new Exception('Unsupported tax number country.'),
        };
    }

    /**
     * @param string $couponCode
     * @return int
     */
    private function getDiscountByCouponCode(string $couponCode): int
    {
        $this->logger->debug("Getting discount for $couponCode");

        $discountValue = $this->entityManager
            ->getRepository(Coupon::class)
            ->findOneBy(['code' => $couponCode]);

        return $discountValue ? $discountValue->getDiscountValue() : 0;
    }
}