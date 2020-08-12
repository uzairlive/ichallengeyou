<?php

namespace App\Observers;

use App\Models\Challenge;
use App\Models\Notification;
use App\Notifications\ChallengeNotification;
use App\Notifications\ChallengeUpdateNotification;

class ChallengeObserver
{
    /**
     * Handle the challenges "created" event.
     *
     * @param  \App\challenges  $challenge
     * @return void
     */
    public function created(Challenge $challenge)
    {
        // TO CHALLENGE OWNER
        $notification = new Notification([
            'user_id' => $challenge->user_id, 
            'title' => 'New Challenge Created', 
            'body' => 'You have Created The Challenge '.$challenge->name, 
            'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
            'data_id' => $challenge->id, 
        ]);
        $challenge->user->notify(new ChallengeNotification($challenge->id,$challenge->name));
        $challenge->notifications()->save($notification);
        
        // TO ADMIN
        $notification = new Notification([
            'user_id' => 1, 
            'title' => 'New Challenge Created', 
            'body' => $challenge->user->name.' have Created The Challenge '.$challenge->name, 
            'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
            'data_id' => $challenge->id, 
        ]);
        $challenge->notifications()->save($notification);
    }

    /**
     * Handle the challenges "updated" event.
     *
     * @param  \App\challenges  $challenge
     * @return void
     */
    public function updated(Challenge $challenge)
    {
        # APPROVED CHALLENGE
        if ($challenge->status == 'Approved') {
            $body = 'Congratulation! Your Challenge '.$challenge->name.' has been Approved';
            // TO CHALLENGE OWNER
            $notification = new Notification([
                'user_id' => $challenge->user_id, 
                'title' => 'Challenge Approved', 
                'body' => $body, 
                'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                'data_id' => $challenge->id, 
            ]);
            $challenge->user->notify(new ChallengeUpdateNotification($challenge->id,$challenge->name,$body));
            $challenge->notifications()->save($notification);
        }
        # DENIED CHALLENGE
        if ($challenge->status == 'Denied') {
            $body = 'Your Challenge '.$challenge->name.' has been Rejected by admin';
            // TO CHALLENGE OWNER
            $notification = new Notification([
                'user_id' => $challenge->user_id, 
                'title' => 'Challenge Rejected', 
                'body' => $body, 
                'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
                'data_id' => $challenge->id, 
            ]);
            $challenge->user->notify(new ChallengeUpdateNotification($challenge->id,$challenge->name,$body));
            $challenge->notifications()->save($notification);
        }
    }

    /**
     * Handle the challenges "deleted" event.
     *
     * @param  \App\challenges  $challenge
     * @return void
     */
    public function deleted(Challenge $challenge)
    {
        // TO CHALLENGE OWNER
        $notification = new Notification([
            'user_id' => $challenge->user_id, 
            'title' => 'Challenge Rejected', 
            'body' => 'Your Challenge '.$challenge->name.' has been rejected by admin', 
            'click_action' =>'CHALLENGE_DETAIL_SCREEN', 
            'data_id' => $challenge->id, 
        ]);
        $challenge->user->notify(new ChallengeNotification($challenge->id,$challenge->name));
        $challenge->notifications()->save($notification);
    }

    /**
     * Handle the challenges "restored" event.
     *
     * @param  \App\challenges  $challenge
     * @return void
     */
    public function restored(Challenge $challenge)
    {
        //
    }

    /**
     * Handle the challenges "force deleted" event.
     *
     * @param  \App\challenges  $challenge
     * @return void
     */
    public function forceDeleted(Challenge $challenge)
    {
        //
    }
}
