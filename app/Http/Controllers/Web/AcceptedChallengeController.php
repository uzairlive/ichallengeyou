<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ChallengeRepository;
use App\Models\Vote;
use App\Models\Challenge;
use App\Models\SubmitChallenge;
use App\Models\AcceptedChallenge;

class AcceptedChallengeController extends Controller
{
    protected $model;

    public function __construct(AcceptedChallenge $model)
    {
        $this->model = new ChallengeRepository($model);
    }

    public function voters(Challenge $challenge, Request $request)
    {
        $orderableCols = ['user_id', 'created_at'];
        $searchableCols = [];
        $whereChecks = ['challenge_id'];
        $whereOps = ['='];
        $whereVals = [$challenge->id];
        $with = ['user'];
        $withCount = [];
        $currentStatus = [];
        $withSums = [];
        $withSumsCol = [];
        $addWithSums = [];
        $whereHas = 'submitChallenge';
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);
        $serial = ($request->start ?? 0) + 1;
        collect($data["data"])->map(function ($item) use (&$serial) {
            $item['voter'] = $item->submitChallenge->votes[0];
            $item['serial'] = $serial++;
            return $item;
        });
        return response($data,200);
    }
    
    public function getAcceptors($id, Request $request)
    {
        $orderableCols = ['user_id', 'created_at'];
        $searchableCols = [];
        $whereChecks = ['challenge_id'];
        $whereOps = ['='];
        $whereVals = [$id];
        $with = ['user'];
        $withCount = [];
        $currentStatus = [];
        $withSums = [];
        $withSumsCol = [];
        $addWithSums = [];
        $whereHas = null;
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);
        $serial = ($request->start ?? 0) + 1;
        collect($data['data'])->map(function ($item) use (&$serial) {
            $item['serial'] = $serial++;
            return $item;
        });
        return response($data, 200);
    }

    public function getSubmitors(Challenge $challenge, Request $request)
    {
        $orderableCols = ['user_id', 'created_at'];
        $searchableCols = [];
        $whereChecks = ['id','challenge_id'];
        $whereOps = ['=','='];
        $whereVals = [$request->id,$challenge->id];
        $with = ['user','submitFiles'];
        $withCount = [];
        $currentStatus = [];
        $withSums = [];
        $withSumsCol = [];
        $addWithSums = [];
        $whereHas = 'submitChallenge';
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);

        $isWinner = 0;
        $showWinBtn = false;
        foreach ($data['data'] as $d) {
            $d->submitChallenge->isWinner ? ++$isWinner : $isWinner;
            $showWinBtn = (
                ( $d->challenge->result_type  == 'first_win' && $d->challenge->status == ResultPending() ) ||
                ( $d->challenge->allowVoter  == 'admin' && $d->challenge->status == ResultPending() )
            ) ? true : false;
        }

        $serial = ($request->start ?? 0) + 1;
        collect($data['data'])->map(function ($item) use (&$serial , $showWinBtn) {
            $item['isWinner'] = $item->submitChallenge->isWinner ? 'Winner' : '-';
            $item['vote_up'] = $item->submitChallenge->votes->where('vote_up', true)->count();
            $item['vote_down'] = $item->submitChallenge->votes->where('vote_down', true)->count();
            $item['total_votes'] = ($item->submitChallenge->votes->where('vote_up', true)->count() - 
                                    $item->submitChallenge->votes->where('vote_down', true)->count());
            $item['showWinBtn'] = $showWinBtn;
            $item['serial'] = $serial++;
            return $item;
        });
        return response($data, 200);
    }

    public function updateWinner(Request $request) {
        if($request->value == 'yes'){
            $acceptedChallenge = AcceptedChallenge::findOrFail($request->id);
            $submitChallenge = $acceptedChallenge->submitChallenge;
            $submitChallenge->isWinner = true;
            $submitChallenge->update();
        } 
        return true;
    }
}
