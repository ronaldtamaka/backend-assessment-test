<?php

namespace App\Http\Controllers;

use App\Models\ScheduledRepayment;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\Validator;

class LoanController extends BaseController
{

    public function credit_application(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'terms' => 'required',
            'currency_code' => 'required',
            'processed_at' => 'required',
        ]);

        if($validator->fails()){
            return response()->json($validator->messages(), 201);
        }

        $user =  Auth::userOrFail();
        $debitCard = $user->loans()->create([
            'amount' => $request->amount,
            'terms' => $request->terms,
            'outstanding_amount' => $request->amount,
            'currency_code' =>  $request->currency_code,
            'processed_at' => $request->processed_at,
            'status' =>  'not paid',
        ]);
        $debitCard = $debitCard->fresh();
        $ciclan = $request->amount / $request->terms;
        
    //  Karna tidak ada admin yg aprov jadi pengajuan pinjaman auto di terima
        for ($i=1; $i <= $request->terms ; $i++) { 
            $month = '+'.$i.' month';
            $date = date('Y-m-d', strtotime($month, strtotime($request->processed_at)));
            ScheduledRepayment::create([
                'loan_id' => $debitCard->id,
                'pay_month' => $date,
                'pay_amount' => $ciclan,
                'status' => 'not paid',
            ]);
        }

        return response()->json(['msg' => 'Berhasil Melakukan Mengajuan'], HttpResponse::HTTP_OK);
    }

    public function repayment($repayment){

    }
    
}
