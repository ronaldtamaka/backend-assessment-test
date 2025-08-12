<?php

namespace App\Http\Controllers\API;

use App\Models\DebitCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\DebitCardTransaction;
use App\Http\Resources\DebitCardTransactionResource;
use App\Http\Requests\StoreDebitCardTransactionRequest;

class DebitCardTransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = DebitCardTransaction::whereHas(
            'debitCard',
            fn($q) =>
            $q->where('customer_id', auth()->id())
        )->paginate($request->input('limit', 10));

        return success_response(DebitCardTransactionResource::collection($transactions));
    }

    public function store(StoreDebitCardTransactionRequest $request)
    {
        DB::beginTransaction();
        try {
            $debitCard = DebitCard::findOrFail($request->debit_card_id);
            abort_if($debitCard->customer_id !== auth()->id(), 403);

            $transaction = $debitCard->transactions()->create($request->validated());
            DB::commit();
            return success_response(new DebitCardTransactionResource($transaction));
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function show(DebitCardTransaction $debitCardTransaction)
    {
        abort_if($debitCardTransaction->debitCard->customer_id !== auth()->id(), 403);
        return success_response(new DebitCardTransactionResource($debitCardTransaction));
    }
}
