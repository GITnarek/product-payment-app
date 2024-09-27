<?php

namespace App\Tests\Service;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Exception\ProductNotFoundException;
use App\Service\PriceCalculatorService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PriceCalculatorServiceTest extends TestCase
{
    private $entityManager;
    private $priceCalculator;
    private $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->priceCalculator = new PriceCalculatorService($this->entityManager, $this->logger);
    }

    /**
     * @throws ProductNotFoundException
     */
    public function testCalculateFinalPriceWithValidProduct()
    {
        $product = new Product();
        $product->setPrice(100);

        $coupon = new Coupon();
        $coupon->setCode('P10');
        $coupon->setDiscountValue(10);

        $productRepo = $this->createMock(EntityRepository::class);
        $productRepo->method('find')->willReturn($product);

        $couponRepo = $this->createMock(EntityRepository::class);
        $couponRepo->method('findOneBy')->willReturn($coupon);

        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [Product::class, $productRepo],
                [Coupon::class, $couponRepo],
            ]));

        $finalPrice = $this->priceCalculator->calculateFinalPrice(1, 'DE123456789', 'P10');

        $this->assertEquals(109, $finalPrice);
    }

    public function testCalculateFinalPriceWithInvalidProduct()
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);

        $this->entityManager->method('getRepository')
            ->willReturn($repo);

        $this->expectException(ProductNotFoundException::class);

        $this->priceCalculator->calculateFinalPrice(999, 'DE123456789', 'D15');
    }
}
