<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransSnapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransSnapService $midtrans): JsonResponse
    {
        $payload = $request->all();

        if (! $this->isTransactionNotification($payload)) {
            Log::info('Ignored unsupported Midtrans notification payload.', [
                'merchant_id' => $payload['merchant_id'] ?? null,
                'payload_fields' => array_keys($payload),
            ]);

            return response()->json(['message' => 'Notification ignored']);
        }

        if (! $midtrans->signatureIsValid($payload)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payment = Payment::where('order_id', $payload['order_id'] ?? '')->firstOrFail();

        if (! $midtrans->amountMatches($payment, $payload['gross_amount'] ?? null)) {
            return response()->json(['message' => 'Transaction amount mismatch'], 422);
        }

        $midtrans->processWebhook($payment, $payload);

        return response()->json(['message' => 'Notification processed']);
    }

    private function isTransactionNotification(array $payload): bool
    {
        return isset(
            $payload['order_id'],
            $payload['status_code'],
            $payload['gross_amount'],
            $payload['signature_key']
        );
    }
}
