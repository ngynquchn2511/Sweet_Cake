<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        dd($users->toArray());
    }
}
