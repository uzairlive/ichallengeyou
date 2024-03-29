<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\FavouriteCollection;
use App\Repositories\ChallengeRepository;
use App\Models\Reaction;

class FavoriteController extends Controller
{
    protected $model;

    public function __construct(Reaction $reaction)
    {
        $this->model = new ChallengeRepository($reaction);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $orderableCols = ['created_at', 'title', 'start_time', 'user.name', 'trend', 'amounts_sum', 'amounts_trend_sum'];
        $searchableCols = ['title'];
        $whereChecks = ['favorite', 'user_id'];
        $whereOps = ['=', '='];
        $whereVals = [true, auth()->id()];
        $with = ['challenge'];
        $withCount = [];
        $currentStatus = [];
        $withSums = [];
        $withSumsCol = [];
        $addWithSums = [];
        $whereHas = null;
        $withTrash = false;

        $data = $this->model->getData($request, $with, $withTrash, $withCount, $whereHas, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);

        collect($data['data'])->map(function ($item) use (&$serial) {
            if($item->challenge) {
                $amount_sum = number_format($item->challenge->amount_sum, 2, '.', '') ?? '0.00';
                $item['amounts_sum'] = config('global.CURRENCY').' '.$amount_sum;
            }
        });

        $data['data'] = FavouriteCollection::collection($data['data']);
        return response($data, $data['response']);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
