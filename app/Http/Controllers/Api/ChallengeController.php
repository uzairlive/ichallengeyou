<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Challenges\ChallengeRequest;
use App\Http\Requests\Challenges\CreateChallengeRequest;
use App\Http\Requests\Comments\CreateCommentRequest;
use App\Http\Requests\Donations\CreateDonationRequest;
use App\Http\Resources\ChallengeCollection;
use App\Http\Resources\ChallengeList;
use App\Http\Resources\ChallengeDetailCollection;
use App\Repositories\ChallengeRepository;
use App\Models\Challenge;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Amount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Auth;

class ChallengeController extends Controller
{
    protected $model;

    public function __construct(Challenge $model)
    {
        $this->model = new ChallengeRepository($model);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user_id = $request->user_id;
        $orderableCols = ['created_at', 'title', 'start_time', 'user.name', 'trend', 'amounts_sum', 'amounts_trend_sum'];
        $searchableCols = ['title'];
        if($request->category_id){
            $whereChecks = ['category_id'];
            $whereOps = ['='];
            $whereVals = [$request->category_id];
        } else {
            $whereChecks = [];
            $whereOps = [];
            $whereVals = [];
        }
        $with = array(
            'userReaction' => function($query) use ($user_id) {
                $query->where('user_id', $user_id);
            },
            'comments',
        );
        $withCount = [];
        $currentStatus = [Approved(), ResultPending()];
        $withSums = ['amounts'];
        $withSumsCol = ['amount'];
        $addWithSums = ['trend'];
        $whereHas = null;
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);
        $serial = ($request->start ?? 0) + 1;
        collect($data['data'])->map(function ($item) use (&$serial) {
            $item['serial'] = $serial++;
            $item['amounts_sum'] = config('global.CURRENCY').' '.$item->amounts_sum;
            return $item;
        });
        $data['data'] = ChallengeCollection::collection($data['data']);
        return response($data, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(ChallengeRequest $request)
    {
        $message['message'] = config('global.PREMIUM_USER_MESSAGE'); $res = 400;
        $message['premiumBtn'] = true;
        $user = auth()->user();
        if($user->is_premium){
            $message['premiumBtn'] = false;
            $message['message'] = config('global.CHALLENGE_CREATED_MESSAGE'); $res = 200;
            $data = $request->all();
            (float)$balance = $user->getAttributes()['balance'];
            if($balance < (float)$data['amount'] ){
                return response(['message' => config('global.CHALLENGE_AMOUNT_MESSAGE')]);
            }
            $user->balance = $user->getAttributes()['balance'] - (float)$data['amount'];
            $user->update();
            $message['balance'] = $user->balance;

            if($request->hasFile('file')){
                $data['file'] = uploadFile($request->file, challengesPath(), null);
            }
            if($request->location == null || $request->location == "" ){
                $data['location'] = "Anywhere";
            }
            $data['user_id'] = auth()->id();

            if($data['duration_hours'] == 24){
                $data['duration_days'] =$data['duration_days'] + 1;
                $data['duration_hours'] = 0;
            }
            if($data['duration_minutes'] == 60){
                $data['duration_hours'] =$data['duration_hours'] + 1;
                $data['duration_minutes'] = 0;
            }
            $data['start_time'] = Carbon::createFromFormat('Y-m-d H:i', $request->start_time)->toDateTimeString();
            $challenge = $this->model->create($data);
            $challenge->setStatus(Pending());
            $amount = new Amount([
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'type' => 'initial',
                'created_at' => now()
            ]);
            $challenge->amounts()->save($amount);
            $transaction = new Transaction([
                'user_id' => auth()->id(),
                'challenge_id' => $challenge->id,
                'amount' => $request->amount,
                'type' => 'create_challenge',
                'invoice_id' => null,
                'status' => 'paid',
            ]);
            $challenge->transactions()->save($transaction);
        }
        return response($message, $res);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function show(Challenge $challenge, Request $request)
    {
        $id = (int)$request->user_id;
        $user = User::find($id);
        $challenge_id = $challenge->id;
        $whereChecks = ['id'];
        $whereOps = ['='];
        $whereVals = [$challenge->id];
        $with = array(
            'userReaction' => function($query) use ($id, $challenge_id) {
                $query->where('user_id', $id)->where(['reactionable_id'=>$challenge_id]);
            },
            'donations' => function($query) {
                $query->with('user');
            },
            'initialAmount',
            'bids',
            'acceptedChallenges' => function($query) use ($id, $challenge_id){
                $query->where('user_id', $id)->where('challenge_id', $challenge_id);
            },
        );
        $withSums = ['amounts'];
        $withSumsCol = ['amount'];
        $addWithSums = [];

        $data = $this->model->showChallenge($request,$with,$withSums, $withSumsCol,$whereChecks, $whereOps, $whereVals);
        $data['data']->amounts_sum = config('global.CURRENCY').' '.$data['data']->amounts_sum;
        $data['data']->initialAmount->amount = config('global.CURRENCY').' '.$data['data']->initialAmount->amount;
        $data['data']['status'] = $data['data']->status;

        // if accepted challenge
        if($data['data']->acceptedChallenges()->where('user_id', $id)->first() ){
            $data['data']['acceptBtn'] = false;
            $data['data']['donateBtn'] = false;
        }
        // if accepted challenge & start time has arrived
        if($data['data']->acceptedChallenges()->where('user_id', $id)->first() && now() > $data['data']->start_time ){
            $data['data']['submitBtn'] = true;
        }
        // if submitted challenge
        if(optional($data['data']->acceptedChallenges()->where('user_id', $id)->first())->submitChallenge){
            $data['data']['acceptBtn'] = false;
            $data['data']['submitBtn'] = false;
            $data['data']['donateBtn'] = false;
            $data['data']['bidBtn'] = false;
        }
        if($user){
            if($data['data']->user_id == (int)$id){
                $data['data']['acceptBtn'] = false;
                $data['data']['donateBtn'] = false;
                $data['data']['bidBtn'] = false;
                if(now() <= $challenge->start_time && $challenge->status === Pending()){
                    $data['data']['editBtn'] =  true;
                }
            }
            $acceptedChallenges = $data['data']->acceptedChallenges()->where('challenge_id', $challenge_id)->with('submitChallenge')->get();
            $isAccepted = 0; $isDonator = false; $isCreator = false; $isSubmitted = false;
            if($challenge->user->id == $id){
                $isCreator = true;
            }
            if($challenge->donations->where('user_id',  $id)->isNotEmpty()){
                $isDonator = true;
            }
            $isSubmitted = optional($data['data']->acceptedChallenges()->where('user_id', $id)
            ->where('challenge_id', $challenge->id)->first())->SubmitChallenge ? true : false;
            if( $isCreator || $isDonator || $isSubmitted ){
                $data['data']['reviewBtn'] = true;
            }
            if($challenge->allowVoter == "premiumUsers"  && $user->is_premium){
                $data['data']['reviewBtn'] = true;
            }

        }
        if(in_array($data['data']->status, [Expired(), Completed(), Deleted(), Denied(), ResultPending()])) {
            $data['data']['acceptBtn'] = false;
            $data['data']['editBtn'] = false;
            $data['data']['submitBtn'] = false;
            $data['data']['donateBtn'] = false;
            $data['data']['bidBtn'] = false;
        }
        if(in_array($data['data']->status, [Denied()])) {
            $data['data']['reviewBtn'] = false;
        }
        $data = ChallengeDetailCollection::collection($data);
        return response($data,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function edit(Challenge $challenge)
    {
        if($challenge->user_id <> auth()->id()){
            return response(['message' => 'You cannot edit this challenge.'], 403);
        }
        $challenge['amount'] = $challenge->initialAmount->getAttributes()['amount'] ?? 0;
        return response($challenge, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function update(ChallengeRequest $request, Challenge $challenge)
    {
        $message['message'] = 'You cannot edit your challenge, It has been '.$challenge->status.' by Admin!'; $res = 400;
        if($challenge->status == Pending()){
            $message['message'] = config('global.PREMIUM_USER_MESSAGE'); $res = 400;
            $user = auth()->user();
            $data['premiumBtn'] = true;
            if(auth()->user()->is_premium){
                $data['premiumBtn'] = false;
                $message['message'] = 'Challenge has been Updated!'; $res = 200;
                $res = 200;
                $data = $request->all();
                if($request->hasFile('file')){
                    $deleteFile = $challenge->getAttributes()['file'] == 'no-image.png' ? null : $challenge->file;
                    $data['file'] = uploadFile($request->file, challengesPath(), $deleteFile);
                }
                if($request->location == null || $request->location == "" ){
                    $data['location'] = "Anywhere";
                }
                $data['start_time'] = Carbon::createFromFormat('Y-m-d H:i', $request->start_time)->toDateTimeString();
                $challenge = $this->model->update($data , $challenge );
            }
            $message['balance'] = $user->balance;
        }
        return response($message, $res);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function destroy(Challenge $challenge)
    {
        $challenge->setStatus(Deleted());
        return $this->model->delete($challenge);
    }

    /**
     * Donate on the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function donation(Challenge $challenge, CreateDonationRequest $request)
    {
        try {
            $data['message'] = config('global.PREMIUM_USER_MESSAGE'); $res = 400;
            $data['premiumBtn'] = true;
            if(auth()->user()->is_premium){
                $data['premiumBtn'] = false;
                $user = auth()->user();
                if((float)$request->amount > (float)$user->getAttributes()['balance']){
                    return response(['message' => 'Donation amount cannot be greater than current account balance.'], 400);
                }
                if($challenge->allowVoter == 'donators'){
                    $message['message'] = config('global.TIMEOUT_MESSAGE');
                    if(now() <= $challenge->after_date){
                        $donation = $this->donating($challenge,$request);
                    }
                } else if($challenge->allowVoter == 'premiumUsers' || $challenge->allowVoter == 'admin' ){
                    $message['message'] = config('global.TIMEOUT_MESSAGE');
                    if(now() <= $challenge->after_date->addDays(config('global.SECOND_VOTE_DURATION_IN_DAYS')) ){
                        $donation = $this->donating($challenge,$request);
                    }
                }

                return response([
                    'message' => 'Your Donation of '.config('global.CURRENCY').' '.$donation->amount.' has been contributed to the '.$challenge->title,
                    'balance' => $user->balance ?? config('global.CURRENCY')." 0.00"
                ], 200);
            }
            return response([
                'message' =>  $data['message'] ,
                'balance' => $user->balance ?? config('global.CURRENCY')." 0.00"
            ], 200);
        } catch (\Throwable $th) {
            return response($message,400);
        }
    }

    public function donating(Challenge $challenge, CreateDonationRequest $request) {
        $user = auth()->user();
        $donation = new Amount([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'donation'
        ]);
        $challenge->amounts()->save($donation);
        $transaction = new Transaction([
            'user_id' => $user->id,
            'challenge_id' => $challenge->id,
            'amount' => $request->amount,
            'type' => 'donate',
            'invoice_id' => null,
            'status' => 'paid',
        ]);
        $challenge->transactions()->save($transaction);
        $user->balance = (double)$user->getAttributes()['balance'] -= (double)$request->amount;
        $user->update();

        return $donation;
    }

    /**
     * Get Comments of the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function comments(Comment $model, Request $request, $id)
    {
        $user_id = $request->user_id;
        $this->model = new ChallengeRepository($model);
        $with = ['user','replies'];
        $comments = $this->model->comments($request,$with,$id);
        if($user_id){
            collect($comments['data'])->map(function ($item) use ($user_id) {
                if($item->user_id == $user_id){
                    $item['isDeletable'] = true;
                } else {
                    $item['isDeletable'] = false;
                }
            });
        } else {
            collect($comments['data'])->map(function ($item) {
                $item['isDeletable'] = false;
            });
        }
        return response($comments,200);
    }

    /**
     * Comment on the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function comment(Challenge $challenge, CreateCommentRequest $request)
    {
        $comment = new Comment([
            'parent_id' => $request->parent_id ?? 0,
            'user_id' => auth()->id(),
            'text' => $request->text
        ]);
        $challenge->comments()->save($comment);
        return response(['message' => 'Comment has been submitted.'], 200);
    }

    public function deleteComment(Comment $comment) {
        try {
            $replies = $comment->replies;
            if($replies->first()){
                foreach ($replies as $reply ) {
                    $reply->delete();
                }
            }
            $comment->delete();
            return true;
        } catch (\Throwable $th) {
           return false;
        }
    }

    public function restoreComment($id){
        try {
            $comment = Comment::withTrashed()->find($id);
            $comment->restore();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Like the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function likeChallenge(Challenge $challenge)
    {
        $reaction = $challenge->userReaction ? $challenge->userReaction->where('user_id', auth()->id())->first() : null;
        if(!$reaction){
            $reaction = new Reaction([
                'user_id' => auth()->id(),
                'like' => true,
            ]);
            $challenge->userReaction()->save($reaction);
            $challenge->increment('trend');
        } else {
            $reaction->update([
                'like' => $reaction->like == false ? true : false,
                'unlike' => false
            ]);
            $reaction->like ? $challenge->increment('trend') : $challenge->decrement('trend');
        }
        return response([
            'like' => $reaction->like,
            'like_count' => format_number_in_k_notation($challenge->likes->count()),
            'unlike_count' => format_number_in_k_notation($challenge->unlikes->count()),
        ], 200);
    }

    public function likeComment(Comment $comment)
    {
        $reaction = $comment->userReaction ? $comment->userReaction->where('user_id', auth()->id())->first() : null;
        if(!$reaction){
            $reaction = new Reaction([
                'user_id' => auth()->id(),
                'like' => true,
            ]);
            $comment->userReaction()->save($reaction);
        } else {
            $reaction->update([
                'like' => $reaction->like == false ? true : false,
                'unlike' => false
            ]);
        }
        return response(['like' => $reaction->like], 200);
    }

    /**
     * Unlike the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function unlikeChallenge(Challenge $challenge)
    {
        $reaction = $challenge->userReaction ? $challenge->userReaction->where('user_id', auth()->id())->first() : null;
        if(!$reaction){
            $react = $reaction = new Reaction([
                'user_id' => auth()->id(),
                'unlike' => true,
            ]);
            $challenge->userReaction()->save($reaction);
            $return = true;
        } else {
            $reaction->update([
                'like' => false,
                'unlike' => $reaction->unlike == false ? true : false
            ]);
            if($challenge->trend != 0){
                $challenge->decrement('trend');
            }
        }
        return response([
            'unlike' => $reaction->unlike,
            'like_count' => format_number_in_k_notation($challenge->likes->count()),
            'unlike_count' => format_number_in_k_notation($challenge->unlikes->count()),
        ], 200);
    }

    public function unlikeComment(Comment $comment)
    {
        $reaction = $comment->userReaction ? $comment->userReaction->where('user_id', auth()->id())->first() : null;
        if(!$reaction){
            $react = $reaction = new Reaction([
                'user_id' => auth()->id(),
                'unlike' => true,
            ]);
            $comment->userReaction()->save($reaction);
            $return = true;
        } else {
            $reaction->update([
                'like' => false,
                'unlike' => $reaction->unlike == false ? true : false
            ]);
        }
        return response(['unlike' => $reaction->unlike ], 200);
    }

    /**
     * Favorite the specified resource.
     *
     * @param  \App\Models\Challenge $challenge
     * @return \Illuminate\Http\Response
     */
    public function favoriteChallenge(Challenge $challenge)
    {
        $reaction = $challenge->userReaction ? $challenge->userReaction->where('user_id', auth()->id())->first() : null;
        if(!$reaction){
            $reaction = new Reaction([
                'user_id' => auth()->id(),
                'favorite' => true,
            ]);
            $challenge->userReaction()->save($reaction);
        } else {
            $reaction->update([
                'favorite' => $reaction->favorite == false ? true : false,
            ]);
        }
        return response(['favorite' => $reaction->favorite], 200);
    }

    public function myList(Request $request)
    {
        $orderableCols = ['created_at', 'title', 'start_time', 'user.name', 'trend', 'amounts_sum', 'amounts_trend_sum'];
        $searchableCols = ['title'];
        $whereChecks = ['user_id'];
        $whereOps = ['='];
        $whereVals = [auth()->id()];
        $with = [];
        $withCount = [];
        $currentStatus = [];
        $withSums = ['amounts'];
        $withSumsCol = ['amount'];
        $addWithSums = [];
        $whereHas = null;
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);
        collect($data['data'])->map(function ($item){
            $item['amounts_sum'] = config('global.CURRENCY').' '.$item->amounts_sum;
            return $item;
        });
        $data['data'] = ChallengeList::collection($data['data']);
        return response($data, $data['response']);

    }

}
