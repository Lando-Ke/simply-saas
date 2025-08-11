<?php

namespace App\Domain\Services;

use App\Domain\ValueObjects\Money;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

interface PaymentGatewayInterface
{
    public function createPaymentIntent(Money $amount, array $metadata = []): array;
    public function confirmPayment(string $paymentIntentId): bool;
    public function refundPayment(string $paymentIntentId, Money $amount = null): bool;
    public function getPaymentStatus(string $paymentIntentId): string;
}

class StripePaymentGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $publishableKey;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
        $this->publishableKey = config('services.stripe.key');
    }

    public function createPaymentIntent(Money $amount, array $metadata = []): array
    {
        try {
            // In a real implementation, you would use Stripe SDK
            // For now, we'll simulate the response
            $paymentIntentId = 'pi_' . uniqid();
            
            return [
                'id' => $paymentIntentId,
                'client_secret' => 'pi_' . uniqid() . '_secret_' . uniqid(),
                'amount' => $amount->getAmountInCents(),
                'currency' => $amount->getCurrency(),
                'status' => 'requires_payment_method',
                'metadata' => $metadata
            ];
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount->getAmount(),
                'currency' => $amount->getCurrency()
            ]);
            throw $e;
        }
    }

    public function confirmPayment(string $paymentIntentId): bool
    {
        try {
            // In a real implementation, you would confirm with Stripe
            // For now, we'll simulate success
            Log::info('Payment confirmed', ['payment_intent_id' => $paymentIntentId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);
            return false;
        }
    }

    public function refundPayment(string $paymentIntentId, Money $amount = null): bool
    {
        try {
            // In a real implementation, you would refund with Stripe
            Log::info('Payment refunded', [
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount?->getAmount()
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);
            return false;
        }
    }

    public function getPaymentStatus(string $paymentIntentId): string
    {
        try {
            // In a real implementation, you would check with Stripe
            // For now, we'll simulate a successful status
            return 'succeeded';
        } catch (\Exception $e) {
            Log::error('Payment status check failed', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId
            ]);
            return 'failed';
        }
    }
}

class PaymentGatewayService
{
    private PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway = null)
    {
        $this->gateway = $gateway ?? new StripePaymentGateway();
    }

    /**
     * Process payment for an invoice
     */
    public function processPayment(Invoice $invoice, User $user, array $paymentMethod = []): Payment
    {
        $amount = new Money($invoice->total_amount);
        
        // Create payment intent
        $paymentIntent = $this->gateway->createPaymentIntent($amount, [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'invoice_number' => $invoice->invoice_number
        ]);

        // Create payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'payment_method' => 'stripe',
            'status' => 'pending',
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
            'transaction_id' => $paymentIntent['id'],
            'stripe_payment_intent_id' => $paymentIntent['id'],
            'payment_details' => $paymentIntent,
            'metadata' => $paymentMethod
        ]);

        return $payment;
    }

    /**
     * Confirm a payment
     */
    public function confirmPayment(Payment $payment): bool
    {
        if (!$payment->stripe_payment_intent_id) {
            throw new \InvalidArgumentException('Payment does not have a Stripe payment intent ID');
        }

        $success = $this->gateway->confirmPayment($payment->stripe_payment_intent_id);
        
        if ($success) {
            $payment->update([
                'status' => 'completed',
                'processed_at' => now()
            ]);

            // Update invoice status
            $payment->invoice->update([
                'status' => 'paid',
                'paid_at' => now()
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Payment confirmation failed'
            ]);
        }

        return $success;
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Payment $payment, Money $amount = null): bool
    {
        if (!$payment->stripe_payment_intent_id) {
            throw new \InvalidArgumentException('Payment does not have a Stripe payment intent ID');
        }

        $refundAmount = $amount ?? new Money($payment->amount);
        $success = $this->gateway->refundPayment($payment->stripe_payment_intent_id, $refundAmount);
        
        if ($success) {
            $payment->update(['status' => 'refunded']);
        }

        return $success;
    }

    /**
     * Get payment status from gateway
     */
    public function getPaymentStatus(Payment $payment): string
    {
        if (!$payment->stripe_payment_intent_id) {
            return $payment->status;
        }

        return $this->gateway->getPaymentStatus($payment->stripe_payment_intent_id);
    }

    /**
     * Sync payment status with gateway
     */
    public function syncPaymentStatus(Payment $payment): void
    {
        $gatewayStatus = $this->getPaymentStatus($payment);
        
        if ($gatewayStatus !== $payment->status) {
            $payment->update(['status' => $gatewayStatus]);
        }
    }

    /**
     * Create a test payment for development
     */
    public function createTestPayment(Invoice $invoice, User $user): Payment
    {
        $amount = new Money($invoice->total_amount);
        
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'payment_method' => 'test',
            'status' => 'completed',
            'amount' => $amount->getAmount(),
            'currency' => $amount->getCurrency(),
            'transaction_id' => 'test_' . uniqid(),
            'payment_details' => ['test' => true],
            'processed_at' => now()
        ]);

        // Update invoice status
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return $payment;
    }

    /**
     * Validate payment method
     */
    public function validatePaymentMethod(array $paymentMethod): bool
    {
        $requiredFields = ['type', 'token'];
        
        foreach ($requiredFields as $field) {
            if (!isset($paymentMethod[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get supported payment methods
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'card' => [
                'name' => 'Credit/Debit Card',
                'currencies' => ['USD', 'EUR', 'GBP'],
                'supported_cards' => ['visa', 'mastercard', 'amex']
            ],
            'bank_transfer' => [
                'name' => 'Bank Transfer',
                'currencies' => ['USD', 'EUR'],
                'processing_time' => '3-5 business days'
            ]
        ];
    }
}
