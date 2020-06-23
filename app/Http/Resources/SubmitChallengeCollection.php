<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubmitChallengeCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return  [
            'accepted_challenge_id' => $this->id,
            'title' => $this->acceptedChallenge->challenge->title,
            'user' => $this->acceptedChallenge->user,
            'submit_date' => $this->created_at->format('Y-m-d H:i A'),
        ];
    }
}
