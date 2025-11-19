<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityType;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index($tab = 'users')
    {
        return Inertia::render('Settings/Index', [
            'tab' => $tab,

            'users' => User::with('roles')->get(),

            'roles' => Role::with('permissions')->get(),

            'activityTypes' => ActivityType::all(),
        ]);
    }
}
