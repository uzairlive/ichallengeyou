<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Benwilkins\FCM\FcmMessage;

class ChallengeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $challenge_id;
    protected $challenge_name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($challenge_id, $challenge_name)
    {
        $this->challenge_id = $challenge_id;
        $this->challenge_name = $challenge_name;
    }
    
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['fcm'];
    }
    
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return  Benwilkins\FCM\FcmMessage;
     */
    public function toFcm($notifiable)
    {
        $message = new FcmMessage();
        $message->content([
            'title' => 'New Challenge Created',
            'body' => 'You have Created The Challenge '.$this->challenge_name,
            'sound' => '', // Optional
            'icon' => 'favicon.ico', // Optional
            'click_action' => 'CHALLENGE_DETAIL_SCREEN' // Optional
        ])->data([
            'data_id' => $this->challenge_id // Optional
        ])->priority(FcmMessage::PRIORITY_HIGH); // Optional - Default is 'normal'.

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
