<?php

namespace App\Controller;

use App\Enum\PaymentMethodEnum;
use App\Exception\PaypalProcessFailedException;
use App\Exception\ProductNotFoundException;
use App\Exception\StripeProcessFailedException;
use App\Service\PaymentService;
use App\Service\PriceCalculatorService;
use App\Validator\CouponExists;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PriceController extends AbstractController
{

    private PriceCalculatorService $priceCalculator;

    /**
     * @param PriceCalculatorService $priceCalculator
     */
    public function __construct(PriceCalculatorService $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @return Response
     * @throws ProductNotFoundException
     */
    #[Route('/calculate-price', methods: 'POST')]
    public function calculatePrice(Request $request, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);
        $errors = $this->validatePriceRequest($data, $validator);

        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $finalPrice = $this->priceCalculator->calculateFinalPrice($data['product'], $data['taxNumber'], $data['couponCode']);

        return $this->json(['finalPrice' => $finalPrice], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param PaymentService $paymentService
     * @return Response
     */
    #[Route('/purchase', methods: 'POST')]
    public function purchase(Request $request, ValidatorInterface $validator, PaymentService $paymentService): Response
    {
        $data = json_decode($request->getContent(), true);
        $errors = $this->validatePurchaseRequest($data, $validator);

        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $finalPrice = $this->priceCalculator->calculateFinalPrice($data['product'], $data['taxNumber'], $data['couponCode']);

            if (!isset($data['paymentProcessor']) || !PaymentMethodEnum::tryFrom($data['paymentProcessor'])) {
                return $this->json(['error' => 'Invalid payment processor'], Response::HTTP_BAD_REQUEST);
            }

            $paymentMethod = PaymentMethodEnum::from($data['paymentProcessor']);
            $paymentService->selectPaymentMethod($paymentMethod);

            $paymentService->paymentProcess($finalPrice);

        } catch (ProductNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (PaypalProcessFailedException $e) {
            return $this->json(['error' => 'PayPal payment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (StripeProcessFailedException $e) {
            return $this->json(['error' => 'Stripe payment failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => 'Payment processing failed: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Payment successful'], Response::HTTP_OK);
    }

    /**
     * @param array $data
     * @param ValidatorInterface $validator
     * @return array
     */
    private function validatePriceRequest(array $data, ValidatorInterface $validator): array
    {
        $constraints = new Assert\Collection([
            'product' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'integer']),
                new Assert\GreaterThan(['value' => 0]),
            ],
            'taxNumber' => [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => '/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[a-zA-Z]\d{9})$/',
                    'message' => 'Invalid tax number format.',
                ]),
            ],
            'couponCode' => [
                new Assert\Optional(
                    [
                        new Assert\Type(['type' => 'string']),
                        new CouponExists()
                    ]),
            ],
        ]);


        $violations = $validator->validate($data, $constraints);

        return $this->formatErrors($violations);
    }

    /**
     * @param array $data
     * @param ValidatorInterface $validator
     * @return array
     */
    private function validatePurchaseRequest(array $data, ValidatorInterface $validator): array
    {
        $constraints = new Assert\Collection([
            'product' => [
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'integer']),
                new Assert\GreaterThan(['value' => 0]),
            ],
            'taxNumber' => [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => '/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[a-zA-Z]\d{9})$/',
                    'message' => 'Invalid tax number format.',
                ]),
            ],
            'couponCode' => [
                new Assert\Optional([new Assert\Type(['type' => 'string'])]),
            ],
            'paymentProcessor' => [
                new Assert\NotBlank(),
                new Assert\Choice(['choices' => ['paypal', 'stripe'], 'message' => 'Invalid payment processor.']),
            ],
        ]);

        $violations = $validator->validate($data, $constraints);
        return $this->formatErrors($violations);
    }

    /**
     * @param $violations
     * @return array
     */
    private function formatErrors($violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }
        return $errors;
    }
}
