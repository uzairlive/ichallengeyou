<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ChallengeRepository;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;

class PaymentController extends Controller
{
    public function loadBalance(Request $request)
    {
        $balance = 0;
        $user = Auth::User();
        $data = [
            'message' => config('global.LOAD_BALANCE_MESSAGE').' & Your Total Amount is '.$user->balance,
            'amount' => $user->balance ?? config('global.CURRENCY')." 0.00",
        ];
        $pay_id = $request->response['id'];
        $auth = paypalAuth();
        (string)$token = $auth['access_token'];
        if(!$token){
            return response('Token is not Verified' , 401);
        }
        $transaction = Transaction::where('invoice_id' , $pay_id)->first();
        if($transaction){
            return response(['message'=>'You have already loaded your balance'] , 402 ); 
        }
        $paymentRecord = paypalDetail($token , $pay_id);
        $amount = $paymentRecord['transactions'][0]['amount']['total'];
        if($paymentRecord['state'] == 'approved' && $paymentRecord['payer']['status'] == 'VERIFIED' ){
            if(!$user->is_premium){
                $user->is_premium = true;
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'challenge_id' => null,
                    'amount' => config('global.PREMIUM_COST'),
                    'type' => 'miscellaneous',
                    'invoice_id' => $pay_id,
                    'invoice_type' => 'PAYPAL',
                    'status' => 'paid',
                ]);
                $user->transactions()->save($transaction);
                $balance = $amount - config('global.PREMIUM_COST');
            }
            $user->balance = (float)$user->getAttributes()['balance'] + ($balance >= 1 ? $balance : $amount );
            $user->update();
            $transaction = new Transaction([
                'user_id' => $user->id,
                'challenge_id' => null,
                'amount' => $amount,
                'type' => 'load',
                'invoice_id' => $pay_id,
                'invoice_type' => 'PAYPAL',
                'status' => 'paid',
            ]);
            $user->transactions()->save($transaction);

            $data = [
                'message' => config('global.CURRENCY').' '.number_format($amount,2).' has been credited to your account & Your Total Amount is '.$user->balance,
                'amount' => $user->balance ?? config('global.CURRENCY')." 0.00",
                'is_premium' => $user->is_premium,
            ];
            return response($data , 200);
        }
        return response($data , 402 ); 
    }
}
