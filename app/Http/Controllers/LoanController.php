<?php

namespace App\Http\Controllers;

use App\Models\ReceivedRepayment;
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
        $scheduledrepayment = $user->loans()->create([
            'amount' => $request->amount,
            'terms' => $request->terms,
            'outstanding_amount' => $request->amount,
            'currency_code' =>  $request->currency_code,
            'processed_at' => $request->processed_at,
            'status' =>  'not paid',
        ]);
        $scheduledrepayment = $scheduledrepayment->fresh();
        $ciclan = $request->amount / $request->terms;
        
    //  Karna tidak ada admin yg aprov jadi pengajuan pinjaman auto di terima
        for ($i=1; $i <= $request->terms ; $i++) { 
            $month = '+'.$i.' month';
            $date = date('Y-m-d', strtotime($month, strtotime($request->processed_at)));
            ScheduledRepayment::create([
                'loan_id' => $scheduledrepayment->id,
                'pay_month' => $date,
                'pay_amount' => $ciclan,
                'status' => 'not paid',
            ]);
        }

        return response()->json(['msg' => 'Berhasil Melakukan Mengajuan'], HttpResponse::HTTP_OK);
    }

    public function repayment(Request $request , $repayment){

        $user =  Auth::userOrFail();

        $loan =  $user->loans()->where('id', $request->loan);

        // inputan yg di isi jika melunasi semua cicilan pada 1 tagiahan berisi id loan
        if ($request->repaymentAll == 'all') {
              $loan = $user->loans()->where('id', $request->loan)->update([
                'outstanding_amount' => 0,
                'status' =>  'paid',
            ]);

            $sc = ScheduledRepayment::where('loan_id', $loan->first()->id)->where('status', 'not paid')->get();

            foreach ($sc as $key => $value) {
                ScheduledRepayment::where('id', $value->id)->update([
                    'status' => 'paid'
                ]);

                // karna tidak ada admin pembayaran auto diterima (accept/reject)
                ReceivedRepayment::create([
                    'loan_id' => $user->loans->where('id', $request->loan)->first()->id,
                    'scheduled_repayment_id' => $value->id,
                    'pay_date' => date("Y-m-d"),
                    'status' => 'accept',
                ]);

            }
            return response()->json(['msg' => 'Berhasil Melakukan Pembayaran'], HttpResponse::HTTP_OK);
        }


        
        $loanup = clone $loan;

       $sc =  ScheduledRepayment::where('id', $repayment)->first();

        $loanup->update([
            'outstanding_amount' => $loan->first()->outstanding_amount - $sc->pay_amount,
            'status' => $loan->first()->outstanding_amount - $sc->pay_amount == 0 ? 'paid' : 'not paid',
        ]);

       $sc->update([
            'status' => 'paid'
        ]);

        ReceivedRepayment::create([
            'loan_id' => $loan->first()->id,
            'scheduled_repayment_id' => $repayment,
            'pay_date' => date("Y-m-d"),
            'status' => 'accept',
        ]);

        return response()->json(['msg' => 'Berhasil Melakukan Pembayaran'], HttpResponse::HTTP_OK);

    }
    
}
