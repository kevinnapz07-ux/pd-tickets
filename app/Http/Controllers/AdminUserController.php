<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $users = User::query()
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->when($request->query('search'), function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $registrationCounts = Registration::query()
            ->select('email', DB::raw('count(*) as total'))
            ->whereIn('email', $users->pluck('email'))
            ->groupBy('email')
            ->pluck('total', 'email');

        return view('admin.users.index', compact('users', 'registrationCounts'));
    }
}
