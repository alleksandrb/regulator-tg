<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(): Response
    {
        return Inertia::render('UserManagement', [
            'users' => User::select('id', 'name', 'email', 'created_at')->get(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('users.manage')->with('success', 'Пользователь успешно создан');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Проверяем, что это не последний пользователь
        if (User::count() <= 1) {
            return redirect()->route('users.manage')->with('error', 'Нельзя удалить последнего пользователя');
        }

        $user->delete();

        return redirect()->route('users.manage')->with('success', 'Пользователь удален');
    }
}
