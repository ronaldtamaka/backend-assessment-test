<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\DebitCardCreateRequest;
use App\Http\Requests\DebitCardDestroyRequest;
use App\Http\Requests\DebitCardShowRequest;
use App\Http\Requests\DebitCardUpdateRequest;
use App\Http\Resources\DebitCardResource;
use App\Models\DebitCard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DebitCardController extends BaseController
{
    use AuthorizesRequests;
    /**
     * Get active debit cards list
     *
     * @param DebitCardShowRequest $request
     *
     * @return JsonResponse
     */
  public function index(DebitCardShowRequest $request): JsonResponse
    {
        $debitCards = $request->user()
            ->debitCards()
            ->active()
            ->get();

        return response()->json([
            'data' => DebitCardResource::collection($debitCards),
        ]);
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
            'number' => rand(1000000000000000, 9999999999999999),
            'expiration_date' => Carbon::now()->addYear(),
        ]);
        return new DebitCardResource($debitCard);
        // return response()->json(new DebitCardResource($debitCard), HttpResponse::HTTP_CREATED);
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
        $this->authorize('view', $debitCard);

        return response()->json(new DebitCardResource($debitCard), HttpResponse::HTTP_OK);
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
        // ❗ Cek apakah user mau mengaktifkan kartu yang sudah expired
        if ($request->input('is_active') && $debitCard->expiration_date->isPast()) {
            return response()->json([
                'message' => 'Cannot activate expired debit card.'
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        $debitCard->update([
            'disabled_at' => $request->input('is_active') ? null : Carbon::now(),
        ]);

        return response()->json(new DebitCardResource($debitCard), HttpResponse::HTTP_OK);
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
        $this->authorize('delete', $debitCard);

        $debitCard->delete();

        return response()->noContent(); // HTTP 204
    }
}
