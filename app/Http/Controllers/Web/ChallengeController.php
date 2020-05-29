<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\ChallengeRepository;
use App\Models\Challenge;

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
    public function getList(Request $request)
    {
        $orderableCols = ['created_at', 'title', 'start_time', 'user.name'];
        $searchableCols = ['title'];
        $whereChecks = [];
        $whereOps = [];
        $whereVals = [];
        $with = [];
        $withCount = [];
        $currentStatus = [];
        $withSums = ['amounts'];
        $withSumsCol = ['amount'];
        $addWithSums = ['trend'];

        $data = $this->model->getData($request, $with, $withCount, $withSums, $withSumsCol, $addWithSums, $whereChecks,
                                        $whereOps, $whereVals, $searchableCols, $orderableCols, $currentStatus);

        $serial = ($request->start ?? 0) + 1;
        collect($data['data'])->map(function ($item) use (&$serial) {
            $item['serial'] = $serial++;
            $item['amounts_sum'] = config('global.CURRENCY').$item->amounts_sum;
            return $item;
        });
        return response($data, 200);
    }

    public function index()
    {
        return view('challenges.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        // $this->validate($request, [
        //     'name' => 'required|min:2'
        // ]);
        // return $this->model->create($request->only($this->model->getModel()->fillable));
    }

    public function show($id)
    {
        return $this->model->show($id);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        // $this->validate($request, [
        //     'name' => 'required|min:2'
        // ]);
        // $this->model->update($request->only($this->model->getModel()->fillable), $id);
        // return $this->model->find($id);
    }

    public function destroy($id)
    {
        // return $this->model->delete($id);
    }
}
