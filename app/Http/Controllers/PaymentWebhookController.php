<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MidtransSnapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransSnapService $midtrans): JsonResponse
    {
        $payload = $request->all();

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
}
