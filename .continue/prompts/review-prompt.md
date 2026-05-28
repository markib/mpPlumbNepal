---
name: review
description: Senior Code Review
invokable: true
---

Act as a Senior Engineer. Review this code for:
- Memory efficiency (8GB limit)
- Laravel best practices 
- Security in payment gateways

// app/Services/PaymentService.php
public function verifyCallback(array $data, Payment $payment): bool
{
    // 1. Verify HMAC Signature provided by Gateway
    if (!$this->isSignatureValid($data)) {
        throw new SecurityException("Invalid Callback Signature");
    }

    // 2. Query Gateway API to confirm actual status
    return $this->gateway->verifyTransaction($data['transaction_reference']);
}

// app/Http/Controllers/Api/PaymentController.php
public function callback(Request $request, PaymentService $service)
{
    // ... validate input ...

    DB::transaction(function () use ($request, $service) {
        $payment = Payment::findOrFail($request->payment_id);

        if ($service->verifyCallback($request->all(), $payment)) {
            $payment->update(['status' => 'completed']);
            $payment->booking->update(['status_id' => BookingStatus::PAID]);
        }
    });
}
