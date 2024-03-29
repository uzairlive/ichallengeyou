<?php

namespace App\Observers;
use App\Models\Transaction;
use App\Models\Notification;
use App\Notifications\LoadNotification;
use App\Notifications\MiscellaneousNotification;
use App\Notifications\WithdrawalNotification;
use App\Notifications\DonateNotification;
use App\Models\User;
use Notification as Notifications;


class TransactionObserver
{
    /**
     * Handle the submit challenge "created" event.
     *
     * @param  \App\Transaction  $transaction
     * @return void
     */
    public function created(Transaction $transaction)
    {
        $user = auth()->user() ;
        $transaction_type = $transaction->type;
        switch ($transaction_type) {
            case 'load':
                // TO USER
                $transactionArray = new Notification([
                    'user_id' => $transaction->user_id,
                    'title' => 'Balance Loaded Successfully!', 
                    'body' => config('global.CURRENCY').' '.$transaction->amount.' has been Successfully Added to your Account!', 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                // $transaction->user->notify(new LoadNotification($transaction->amount));
                $notify_user = User::find($transaction->user->id);
                Notifications::send($notify_user, new LoadNotification($transaction->amount));       

                $transaction->notifications()->save($transactionArray);
                // TO ADMIN
                $transactionArray = new Notification([
                    'user_id' => 1,
                    'title' => 'Load Balance', 
                    'body' => ($transaction->user->name ?? $transaction->user->username).' has Loaded '.config('global.CURRENCY').' '.$transaction->amount.' Balance Successfully!', 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                $transaction->notifications()->save($transactionArray);
                break;
            case 'miscellaneous':
                // TO USER
                $transactionArray = new Notification([
                    'user_id' => $transaction->user_id, 
                    'title' => 'Congratulation! ♥', 
                    'body' => 'By using '.config('global.CURRENCY').' '.config('global.PREMIUM_COST').' You\'re Premium User Now!', 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                // $transaction->user->notify(new MiscellaneousNotification);
                $notify_user = User::find($transaction->user->id);
                Notifications::send($notify_user, new MiscellaneousNotification);       

                $transaction->notifications()->save($transactionArray);
                // TO ADMIN
                $transactionArray = new Notification([
                    'user_id' => 1,
                    'title' => 'Miscellaneous Amount', 
                    'body' => 'By using '.config('global.CURRENCY').' '.config('global.PREMIUM_COST').' '.$transaction->user->name ?? $transaction->user->username.' is Premium User Now!', 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                $transaction->notifications()->save($transactionArray);
                break;
            case 'withdraw':
                // TO USER
                $transactionArray = new Notification([
                    'user_id' => $transaction->user_id,
                    'title' => 'Withdrawal Transaction',
                    'body' => config('global.CURRENCY').' '.$transaction->amount.' has been debited', 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                // $transaction->user->notify(new WithdrawalNotification($transaction->amount));
                $notify_user = User::find($transaction->user->id);
                Notifications::send($notify_user, new WithdrawalNotification($transaction->amount));       

                $transaction->notifications()->save($transactionArray);
                // TO ADMIN
                $transactionArray = new Notification([
                    'user_id' => 1,
                    'title' => 'Withdrawal Transaction',
                    'body' => ($transaction->user->name ?? $transaction->user->username).' has been debited '.config('global.CURRENCY').' '.$transaction->amount, 
                    'click_action' =>'TRANSACTION_LIST', 
                    'data_id' => $transaction->user_id, 
                ]);
                $transaction->notifications()->save($transactionArray);
                break;
            case 'donate':
                // TO USER
                $transactionArray = new Notification([
                    'user_id' =>  $transaction->user_id,
                    'title' => 'You have Donated Successfully!',
                    'body' => config('global.CURRENCY').' '.$transaction->amount.' has been donated', 
                    'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                    'data_id' => $transaction->challenge_id, 
                ]);
                $transaction->notifications()->save($transactionArray);
                // $transaction->user->notify(new DonateNotification('current_user',$transaction->challenge_id,$transaction->challenge->title,$transaction->amount));
                $notify_user = User::find($transaction->user->id);
                Notifications::send($notify_user, new DonateNotification('current_user',$transaction->challenge_id,$transaction->challenge->title,$transaction->amount));

                // TO Challenge Owner
                $transactionArray = new Notification([
                    'user_id' => $transaction->challenge->user->id,
                    'title' => ((auth()->user()->name ?? auth()->user()->username )?? 'Seeder Test User' ).' have Donated on Your Challenge '.$transaction->challenge->title,
                    'body' => config('global.CURRENCY').' '.$transaction->amount.' has been donated', 
                    'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                    'data_id' => $transaction->challenge_id, 
                ]);
                $user_name = auth()->user()->name ?? auth()->user()->username ;
                // $transaction->challenge->user->notify(new DonateNotification($user_name,$transaction->challenge_id,$transaction->challenge->title,$transaction->amount));
                $notify_user = User::find($transaction->challenge->user->id);
                Notifications::send($notify_user, new DonateNotification($user_name,$transaction->challenge_id,$transaction->challenge->title,$transaction->amount));

                $transaction->notifications()->save($transactionArray);
                // TO ADMIN
                $transactionArray = new Notification([
                    'user_id' => 1,
                    'title' => (($transaction->user->name ?? $transaction->user->username) ?? 'Seeder Test User' ).' have Donated on Challenge '.$transaction->challenge->title,
                    'body' => config('global.CURRENCY').' '.$transaction->amount.' has been donated', 
                    'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                    'data_id' => $transaction->challenge_id, 
                ]);
                $transaction->notifications()->save($transactionArray);
                break;
            
            default:
                # code...
                break;
        }
    }

    /**
     * Handle the submit challenge "updated" event.
     *
     * @param  \App\Transaction  $transaction
     * @return void
     */
    public function updated(Transaction $transaction)
    {
        
    }

    /**
     * Handle the submit challenge "deleted" event.
     *
     * @param  \App\Transaction  $transaction
     * @return void
     */
    public function deleted(Transaction $transaction)
    {
        //
    }

    /**
     * Handle the submit challenge "restored" event.
     *
     * @param  \App\Transaction  $transaction
     * @return void
     */
    public function restored(Transaction $transaction)
    {
        //
    }

    /**
     * Handle the submit challenge "force deleted" event.
     *
     * @param  \App\Transaction  $transaction
     * @return void
     */
    public function forceDeleted(Transaction $transaction)
    {
        //
    }
}
