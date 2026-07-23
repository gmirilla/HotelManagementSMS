<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

enum PaymentMethod: string
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';
    case Flutterwave = 'flutterwave';
    case Paystack = 'paystack';
    case Cash = 'cash';
    case PosTerminal = 'pos_terminal';
    case BankTransfer = 'bank_transfer';

    public function isGateway(): bool
    {
        return match ($this) {
            self::Stripe, self::PayPal, self::Flutterwave, self::Paystack => true,
            self::Cash, self::PosTerminal, self::BankTransfer => false,
        };
    }
}
