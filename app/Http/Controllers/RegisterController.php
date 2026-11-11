<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create(){
        return view('auth.register');
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|min:3|max:255|unique:users,email',
            'password' => 'required|string|min:3|max:255',
        ]);
        $senhaCriptografada = Hash::make($request->password);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $senhaCriptografada
        ]);
        Auth::login($user);

        return redirect()->route('eventos.index');
    }
}
