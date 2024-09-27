<?php

namespace App\Service;

use App\Enum\PaymentMethodEnum;
use App\Exception\PaypalProcessFailedException;
use App\Exception\StripeProcessFailedException;
use Exception;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class PaymentService
{
    protected PaymentMethodEnum $paymentMethod;
    const PAYMENT_METHOD_ALLOWED = [
        PaymentMethodEnum::PAYPAL->value => 'processByPayPal',
        PaymentMethodEnum::STRIPE->value => 'processByStripe',
    ];


    public function selectPaymentMethod(PaymentMethodEnum $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function paymentProcess(int|float $finalPrice): bool
    {
        $callable = self::PAYMENT_METHOD_ALLOWED[$this->paymentMethod->value];

        return $this->$callable($finalPrice);
    }

    /**
     * @param int $finalPrice
     * @return bool
     * @throws PaypalProcessFailedException
     */
    public function processByPayPal(int $finalPrice): bool
    {
        try {
            (new PaypalPaymentProcessor())->pay($finalPrice);
        } catch (Exception $e) {
            throw new PaypalProcessFailedException($e->getMessage());
        }

        return true;
    }

    /**
     * @param float $finalPrice
     * @return bool
     * @throws StripeProcessFailedException
     */
    public function processByStripe(float $finalPrice): bool
    {
        if(!(new StripePaymentProcessor())->processPayment($finalPrice)) {
            throw new StripeProcessFailedException();
        };

        return true;
    }

}