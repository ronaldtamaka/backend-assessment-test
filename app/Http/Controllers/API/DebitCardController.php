<?php

namespace App\Http\Controllers\API;

use App\Models\DebitCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\DebitCardResource;
use App\Http\Requests\StoreDebitCardRequest;
use App\Http\Requests\UpdateDebitCardRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DebitCardController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        return success_response(DebitCardResource::collection(auth()->user()->debitCards()->paginate($request->input('limit', 10))));
    }

    public function store(StoreDebitCardRequest $request)
    {
        DB::beginTransaction();
        try {
            $card = auth()->user()->debitCards()->create($request->validated());
            DB::commit();
            return success_response(new DebitCardResource($card));
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function show(DebitCard $debitCard)
    {
        $this->authorize('view', $debitCard);
        return success_response(new DebitCardResource($debitCard));
    }

    public function update(UpdateDebitCardRequest $request, DebitCard $debitCard)
    {
        $this->authorize('update', $debitCard);

        DB::beginTransaction();
        try {
            $debitCard->update($request->validated());
            DB::commit();
            return success_response(new DebitCardResource($debitCard));
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function destroy(DebitCard $debitCard)
    {
        $this->authorize('delete', $debitCard);

        DB::beginTransaction();
        try {
            $debitCard->delete();
            DB::commit();
            return success_response(code: 204);
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
}
