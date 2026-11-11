<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create(){
        return view('auth.login');
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'password' => 'required|string|min:3|max:255',
        ]);
        
        if(Auth::attempt($validated)){
            $request->session()->regenerate();
            return redirect()->route('eventos.index', ['intended' => true]);
        }

        return back()->withErrors(['loginError' => 'Nome ou senha estão errados'])->withInput(['name']);
    }

    public function logout(Request $request) {
        $request->session()->invalidate();
        return redirect()->route('eventos.index', ['intended' => true]);
    }

}
