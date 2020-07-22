<?php

namespace App\Observers;

use Illuminate\Http\Request;
use App\Models\SubmitChallenge;
use App\Models\Challenge;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ChallengeSubmited;

class SubmitChallengeObserver
{
    /**
     * Handle the submit challenge "created" event.
     *
     * @param  \App\SubmitChallenge  $submitChallenge
     * @return void
     */
    public function created(SubmitChallenge $submitChallenge)
    {
        try {
            $donators = $submitChallenge->acceptedChallenge->challenge->donations()->get();
            $submitor = $submitChallenge->acceptedChallenge->user;
            $creater = $submitChallenge->acceptedChallenge->challenge->user;
            $challenge = $submitChallenge->acceptedChallenge->challenge;   
            // TO CURRENT USER Notification
            $userNotification = new Notification([
                'user_id' => auth()->id(),
                'title' => 'Challenge Submited', 
                'body' => 'You has been Submited the Challenge '.$challenge->title, 
                'click_action' =>'SUBMITED_CHALLENGE_DETAIL_SCREEN', 
                'data_id' => $submitChallenge->accepted_challenge_id, 
            ]);
            $submitChallenge->notifications()->save($userNotification);  
            $creater->notify(new ChallengeSubmited('onUser',$submitChallenge->accepted_challenge_id, $donator, $submitor, $creater, $challenge));
            // Donators Notification
            foreach ($donators as $donator) {
                $notification[] = new Notification([
                    'user_id' => $donator->user->id,
                    'title' => 'Challenge Submited', 
                    'body' => $submitor->name.' has been Submited the Challenge '.$challenge->title, 
                    'click_action' =>'SUBMITED_CHALLENGE_DETAIL_SCREEN', 
                    'data_id' => $submitChallenge->accepted_challenge_id, 
                ]);
                $donator->user->notify(new ChallengeSubmited('onCreated',$submitChallenge->accepted_challenge_id, $donator, $submitor, $creater, $challenge));
            }
            $submitChallenge->notifications()->saveMany($notification);
            // Creater Notification
            $createrNotification = new Notification([
                'user_id' => $creater->id,
                'title' => 'Challenge Submited', 
                'body' => $submitor->name.' has been Submited the Challenge '.$challenge->title, 
                'click_action' =>'SUBMITED_CHALLENGE_DETAIL_SCREEN', 
                'data_id' => $submitChallenge->accepted_challenge_id, 
            ]);
            $submitChallenge->notifications()->save($createrNotification);  
            $creater->notify(new ChallengeSubmited('onCreated',$submitChallenge->accepted_challenge_id, $donator, $submitor, $creater, $challenge));
            // Admin Notification
            $createrNotification = new Notification([
                'user_id' => 1,
                'title' => 'Challenge Submited', 
                'body' => $submitor->name.' has been Submited the Challenge '.$challenge->title, 
                'click_action' =>'SUBMITED_CHALLENGE_DETAIL_SCREEN', 
                'data_id' => $submitChallenge->accepted_challenge_id, 
            ]);
            $submitChallenge->notifications()->save($createrNotification);  
            $creater->notify(new ChallengeSubmited('onCreated',$submitChallenge->accepted_challenge_id, $donator, $submitor, $creater, $challenge));

        } catch (\Throwable $th) {
            return response($th->getMessage() , 400);
        }
    }

    /**
     * Handle the submit challenge "updated" event.
     *
     * @param  \App\SubmitChallenge  $submitChallenge
     * @return void
     */
    public function updated(SubmitChallenge $submitChallenge)
    {
        if($submitChallenge->isWinner == true){
            $donators = $submitChallenge->acceptedChallenge->challenge->donations()->get();
            $winner = $submitChallenge->acceptedChallenge->user;
            $creater = $submitChallenge->acceptedChallenge->challenge->user;
            $challenge = $submitChallenge->acceptedChallenge->challenge; 
            $submitors =  $submitChallenge->acceptedChallenge->challenge->acceptedChallenges;
            // TO DONATORS
            foreach ($donators as $donator) {
                $notification[] = new Notification([
                    'user_id' => $donator->user->id,
                    'title' => 'Win Challenge', 
                    'body' => $winner->name.' WIN the Challenge Donor '.$challenge->title, 
                    'click_action' =>'SUBMITED_CHALLENGE_LIST_SCREEN', 
                    'data_id' => $challenge->id, 
                ]);
                $donator->user->notify(new ChallengeSubmited('toDonator&Creator', $challenge->id, $donator, $winner, $creater, $challenge));
            }
            $submitChallenge->notifications()->saveMany($notification);
            // TO CREATER
            $createrNotification = new Notification([
                'user_id' => $creater->id,
                'title' => 'Win Challenge', 
                'body' => $winner->name.' WIN the Challenge Createer '.$challenge->title, 
                'click_action' =>'SUBMITED_CHALLENGE_LIST_SCREEN', 
                'data_id' => $challenge->id, 
            ]);
            $submitChallenge->notifications()->save($createrNotification);
            $creater->notify(new ChallengeSubmited('toDonator&Creator', $challenge->id, $donator, $winner, $creater, $challenge));
            // TO SUBMItORS
            foreach ($submitors as $submitor) {
                $submitorNotification[] = new Notification([
                    'user_id' => $submitor->user_id,
                    'title' => 'Win Challenge', 
                    'body' => $winner->name.' WIN the Challenge submitor '.$challenge->title, 
                    'click_action' =>'SUBMITED_CHALLENGE_LIST_SCREEN', 
                    'data_id' => $challenge->id, 
                ]);
                $submitor->user->notify(new ChallengeSubmited('toSubmitor', $challenge->id, $donator, $winner, $creater, $challenge));
            }
            $submitChallenge->notifications()->saveMany($submitorNotification);  
            // TO WINNER
            $winnerNotification = new Notification([
                'user_id' => $winner->id,
                'title' => 'Congratulations! You have Won The Challenge ★', 
                'body' => $winner->name.' WIN the Challenge '.$challenge->title, 
                'click_action' =>'SUBMITED_CHALLENGE_LIST_SCREEN', 
                'data_id' => $challenge->id, 
            ]);
            $submitChallenge->notifications()->save($winnerNotification);  
            $creater->notify(new ChallengeSubmited('toWinner', $challenge->id, $donator, $winner, $creater, $challenge));
            // TO ADMIN
            Notification::create([
                'user_id' => 1,
                'title' => $winner->name.' - THE WINNER ★', 
                'body' => $winner->name.' WIN the Challenge '.$challenge->title, 
                'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                'data_id' =>  $challenge->id, 
            ]);
            
        }
    }

    /**
     * Handle the submit challenge "deleted" event.
     *
     * @param  \App\SubmitChallenge  $submitChallenge
     * @return void
     */
    public function deleted(SubmitChallenge $submitChallenge)
    {
        //
    }

    /**
     * Handle the submit challenge "restored" event.
     *
     * @param  \App\SubmitChallenge  $submitChallenge
     * @return void
     */
    public function restored(SubmitChallenge $submitChallenge)
    {
        //
    }

    /**
     * Handle the submit challenge "force deleted" event.
     *
     * @param  \App\SubmitChallenge  $submitChallenge
     * @return void
     */
    public function forceDeleted(SubmitChallenge $submitChallenge)
    {
        //
    }
}
