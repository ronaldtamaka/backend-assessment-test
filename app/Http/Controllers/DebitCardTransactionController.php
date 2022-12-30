<?php

namespace App\Http\Controllers;

use App\Http\Requests\DebitCardTransactionCreateRequest;
use App\Http\Requests\DebitCardTransactionDestroyRequest;
use App\Http\Requests\DebitCardTransactionShowIndexRequest;
use App\Http\Requests\DebitCardTransactionShowRequest;
use App\Http\Requests\DebitCardTransactionUpdateRequest;
use App\Http\Resources\DebitCardTransactionResource;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

use Illuminate\Http\Request;
use Validator;

class DebitCardTransactionController extends BaseController
{
    /**
     * Get debit card transactions list
     *
     * @param DebitCardTransactionShowIndexRequest $request
     *
     * @return JsonResponse
     */
    public function index(DebitCardTransactionShowIndexRequest $request): JsonResponse
    {
        $debitCard = DebitCard::find($request->input('debit_card_id'));

        $debitCardTransactions = $debitCard
            ->debitCardTransactions()
            ->get();

        return response()->json(DebitCardTransactionResource::collection($debitCardTransactions), HttpResponse::HTTP_OK);
    }

    public function indextransdebit(Request $request)
    {
        return view('.admin.utama.pages.trans.table');
    }

    /**
     * Create a new debit card transaction
     *
     * @param DebitCardTransactionCreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(DebitCardTransactionCreateRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'currency_code' => 'required',
            'debit_card_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => 200, "message" => "Something Wrong"]);
        }

        $debitCard = DebitCard::find($request->input('debit_card_id'));

        $debitCardTransaction = $debitCard->debitCardTransactions()->create([
            'amount' => $request->input('amount'),
            'currency_code' => $request->input('currency_code'),
        ]);

        return response()->json(new DebitCardTransactionResource($debitCardTransaction), HttpResponse::HTTP_CREATED);
    }

    /**
     * Show a debit card transaction
     *
     * @param DebitCardTransactionShowRequest $request
     * @param DebitCardTransaction            $debitCardTransaction
     *
     * @return JsonResponse
     */
    public function show(DebitCardTransactionShowRequest $request, DebitCardTransaction $debitCardTransaction)
    {
        return response()->json(new DebitCardTransactionResource($debitCardTransaction), HttpResponse::HTTP_OK);
    }
}
