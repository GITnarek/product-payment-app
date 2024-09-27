<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class CouponExists extends Constraint
{
    public string $message = 'The coupon code "{{ value }}" does not exist.';
}