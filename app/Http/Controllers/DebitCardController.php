<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\DebitCardCreateRequest;
use App\Http\Requests\DebitCardDestroyRequest;
use App\Http\Requests\DebitCardShowRequest;
use App\Http\Requests\DebitCardUpdateRequest;
use App\Http\Resources\DebitCardResource;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DebitCardController extends BaseController
{
    /**
     * Get active debit cards list
     *
     * @param DebitCardShowRequest $request
     *
     * @return JsonResponse
     */
    public function index(DebitCardShowRequest $request): JsonResponse
    {
        $cards = DebitCard::where('user_id', auth()->id())->get();

         return response()->json(['data' => $cards], 200);
    }

    /**
     * Create a debit card
     *
     * @param DebitCardCreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(DebitCardCreateRequest $request)
    {
        $debitCard = $request->user()->debitCards()->create([
            'type' => $request->input('type'),
            'card_number' =>  $request->card_number,
            'expiration_date' => Carbon::now()->addYear(),
        ]);

        return response()->json(new DebitCardResource($debitCard), HttpResponse::HTTP_CREATED);
    }

    /**
     * Show a debit card
     *
     * @param DebitCardShowRequest $request
     * @param DebitCard              $debitCard
     *
     * @return JsonResponse
     */
    public function show(DebitCardShowRequest $request, DebitCard $debitCard)
    {
        return response()->json(['data' => $debitCard], 200);
    }

    /**
     * Update a debit card
     *
     * @param DebitCardUpdateRequest $request
     * @param DebitCard              $debitCard
     *
     * @return JsonResponse
     */
    public function update(DebitCardUpdateRequest $request, DebitCard $debitCard)
    {
        if ($debitCard->user_id != auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        $debitCard->update([
            'status' => $request->status
        ]);

        return response()->json(['message' => 'Updated successfully'], 200);
    }

    /**
     * Destroy a debit card
     *
     * @param DebitCardDestroyRequest $request
     * @param DebitCard               $debitCard
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(DebitCardDestroyRequest $request, DebitCard $debitCard)
    {

      if (!$debitCard || $debitCard->user_id != auth()->id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    if (DebitCardTransaction::where('debit_card_id', $debitCard->id)->exists()) {
        return response()->json([
            'message' => 'Cannot delete a card with existing transactions.'
        ], 422);
    }

    $debitCard->forceDelete();

    return response()->json(['message' => 'Deleted successfully'], 200);
     } 
}