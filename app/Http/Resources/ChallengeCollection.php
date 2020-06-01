<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChallengeCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file' => $this->file,
            'amounts_sum' => $this->amounts_sum,
            'amounts_trend_sum' => $this->amounts_trend_sum,
            'like' => $this->like,
            'unlike' => $this->unlike,
            'favorite' => $this->favorite,
            'file_mime' => $this->file_mime,
            'user' => $this->user,
            'category' => $this->category,

        ];
    }
}