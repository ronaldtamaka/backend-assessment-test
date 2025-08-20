<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebitCardTransactionCreateRequest;
use App\Http\Requests\DebitCardTransactionShowIndexRequest;
use App\Http\Requests\DebitCardTransactionShowRequest;
use App\Http\Resources\DebitCardTransactionResource;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\Log;

class DebitCardTransactionController extends BaseController
{
    /**
     * Get debit card transactions list
     */
    public function index(DebitCardTransactionShowIndexRequest $request): JsonResponse
    {
        $debitCardId = $request->input('debit_card_id');

        $debitCard = DebitCard::where('id', $debitCardId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$debitCard) {
            return response()->json(['data' => []], HttpResponse::HTTP_OK);
        }

        $transactions = $debitCard->debitCardTransactions()->get();

        return response()->json([
            'data' => $transactions->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'amount' => $trx->amount,
                    'currency_code' => $trx->currency_code,
                ];
            })
        ], HttpResponse::HTTP_OK);
    }

    /**
     * Create a new debit card transaction
     */
    public function store(DebitCardTransactionCreateRequest $request)
    {
        $debitCard = DebitCard::where('id', $request->input('debit_card_id'))
            ->where('user_id', auth()->id())
            ->first();

        if (!$debitCard) {
            return response()->json(['message' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        $debitCardTransaction = $debitCard->debitCardTransactions()->create([
            'amount' => $request->input('amount'),
            'currency_code' => $request->input('currency_code'),
        ]);

        return response()->json(new DebitCardTransactionResource($debitCardTransaction), HttpResponse::HTTP_CREATED);
    }

    /**
     * Show a debit card transaction
     */
    public function show(DebitCardTransactionShowRequest $request, $id)
    {
        $transaction = DebitCardTransaction::with('debitCard')
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Not found'], HttpResponse::HTTP_NOT_FOUND);
        }

        if (!$transaction->debitCard || $transaction->debitCard->user_id != auth()->id()) {
            return response()->json(['message' => 'Forbidden'], HttpResponse::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency_code' => $transaction->currency_code,
            ]
        ], HttpResponse::HTTP_OK);

        Log::info('DEBUG_SHOW', [
            'auth_id' => auth()->id(),
            'trx_id' => $transaction->id,
            'trx_card_id' => $transaction->debit_card_id,
            'card_user_id' => $transaction->debitCard->user_id ?? null
        ]);
    }
}
