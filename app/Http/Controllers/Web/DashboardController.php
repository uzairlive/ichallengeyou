<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Challenge;
use App\Notifications\AccountActivated;

class DashboardController extends Controller
{
    public function index()
    {
        $challenges = Challenge::all();
        $totalChallenges = $challenges->count();
        $approvedChallenges = Challenge::currentStatus('approved')->count();

        return view('dashboard')->with([
            'approvedChallenges' => $approvedChallenges,
            'totalChallenges' => $totalChallenges
        ]);
    }
}
