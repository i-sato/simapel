<?php

namespace App\Http\Controllers;

use App\Application\Administration\DashboardOverview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardOverview $overview): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('dashboard.index', ['user' => $user, 'overview' => $overview->for($user->id)]);
    }
}
