<?php

namespace App\Enum;

use phpDocumentor\Reflection\Types\Self_;

enum PaymentMethodEnum: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
}
