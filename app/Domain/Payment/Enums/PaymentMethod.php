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

    /**
     * The methods a staff member can record synchronously at the front desk
     * — gateway methods are excluded here for the same reason
     * RecordFolioPaymentAction refuses them: they only ever become
     * "completed" through a verified gateway confirmation, never by a human
     * picking them from a dropdown.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return array_values(array_filter(self::cases(), fn (self $method): bool => ! $method->isGateway()));
    }
}
