<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\Repository;
use App\Models\User;

class UserController extends Controller
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = new Repository($model);
    }

    public function getList(Request $request)
    {
        $orderableCols = ['created_at', 'name'];
        $searchableCols = ['name'];
        $whereChecks = [];
        $whereVals = [];
        $with = [];

        $data = $this->model->getData($request, $with, $whereChecks, $whereVals, $searchableCols, $orderableCols);

        return response($data, 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|min:2'
        ]);
        return $this->model->create($request->only($this->model->getModel()->fillable));

        return redirect('users')->with('success','Product created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            'name' => 'required|min:2'
        ]);
        $this->model->update($request->only($this->model->getModel()->fillable), $user);

        return redirect('users')->with('success','Product updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->model->delete($user);

        return redirect('users')->with('success','User deleted successfully');
    }
}
