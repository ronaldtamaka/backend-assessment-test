<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Authenticate;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebitCardController extends BaseController
{
    /**
     * Get active debit cards list
     *
     * @param DebitCardShowRequest $request
     *
     * @return JsonResponse
     */
    public function index()
    {
        $debitCards = DebitCard::where('user_id',Auth::id())->get();
        return response()->json(DebitCardResource::collection($debitCards), HttpResponse::HTTP_OK);
    }

    /**
     * Create a debit card
     *
     * @param DebitCardCreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(), 201);
        }
        $user =  Auth::userOrFail();

        $debitCard = $user->debitCards()->create([
            'type' => $request->input('type'),
            'number' => rand(10000000, 99999999),
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
    public function show($debitCard)
    {
        $debitCard = DebitCard::where('user_id', Auth::id() )->find($debitCard);
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
    public function update(Request $request, $debitCard)
    {
        $user =  Auth::userOrFail();
        $debit =  $user->debitCards()->where('id', $debitCard )->update([
            'disabled_at' => $request->input('is_active') ? null : Carbon::now(),
        ]);

        $debit = $user->debitCards()->find($debitCard);
        return response()->json(new DebitCardResource($debit), HttpResponse::HTTP_OK);
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
    public function destroy($debitCard)
    {
        $debitCard = DebitCard::where('user_id', Auth::id() )->find($debitCard)->delete();
        return response()->json(['data' =>'data deleted successfully'], HttpResponse::HTTP_NO_CONTENT);
    }
}
