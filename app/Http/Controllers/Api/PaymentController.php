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
        $user = Auth::User();
        $data = [
            'message' => `Your Amount has not been credited to your account \n Your Total Amount is `.$user->balance,
            'amount' => $user->balance,
        ];
        $pay_id = $request->response['id'];
        $auth = paypalAuth();
        (string)$token = $auth['access_token'];
        if(!$token){
            return response('Token is not Verified' , 401);
        }
        $paymentRecord = paypalDetail($token , $pay_id);
        dd($paymentRecord);
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
                ]);
                $user->transactions()->save($transaction);
                $amount = $amount - config('global.PREMIUM_COST');
            }
            $user->balance = (float)$user->getAttributes()['balance'] + $amount;
            $user->update();
            $transaction = new Transaction([
                'user_id' => $user->id,
                'challenge_id' => null,
                'amount' => $amount,
                'type' => 'load',
                'invoice_id' => $pay_id,
            ]);
            $user->transactions()->save($transaction);
            $data = [
                'message' => '$'.$amount.' has been credited to your account \n Your Total Amount is '.$user->balance,
                'amount' => $user->balance,
            ];
            return response($data , 200);
        }
        return response($data , 402 ); 
    }
}
