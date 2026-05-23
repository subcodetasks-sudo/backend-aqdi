<?php

namespace App\Http\Resources\Admin\V2\Api\Concerns;

use App\Models\Payment;

trait ResolvesContractPaymentForAdmin
{
    /**
     * Admin orders list: completed contract (= paid). Amount from successful payment, else contract total.
     *
     * @return array{
     *     is_paid: bool,
     *     payment_status: 'paid'|'unpaid',
     *     payment_label_ar: string,
     *     amount_payment: float|string
     * }
     */
    protected function contractPaymentFields(): array
    {
        $isPaid = (bool) $this->is_completed;
        $successPayment = $this->resolveSuccessfulPayment();

        $amount = $this->resolveOrderAmountPayment() ?? $successPayment?->amount;
        if ($amount === null && $isPaid) {
            $amount = $this->total_price ?? null;
        }

        return [
            'is_paid' => $isPaid,
            'payment_status' => $isPaid ? 'paid' : 'unpaid',
            'payment_label_ar' => $isPaid ? 'تم الدفع' : 'لم يتم الدفع',
            'amount_payment' => $isPaid
                ? ($amount !== null && $amount !== '' ? round((float) $amount, 2) : 'تم الدفع')
                : 'لم يتم الدفع',
        ];
    }

    private function resolveOrderAmountPayment(): ?float
    {
        if (isset($this->order_amount_payment) && $this->order_amount_payment !== null && $this->order_amount_payment !== '') {
            return round((float) $this->order_amount_payment, 2);
        }

        return null;
    }

    private function resolveSuccessfulPayment(): ?Payment
    {
        if ($this->relationLoaded('contractPayments')) {
            return $this->contractPayments
                ->first(fn (Payment $payment) => $payment->status === 'success');
        }

        return $this->contractPayments()
            ->where('status', 'success')
            ->latest('id')
            ->first();
    }
}
