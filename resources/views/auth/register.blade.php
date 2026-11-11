@extends('layouts.app')

@section('title', 'Eventos — FalaQ')

@section('content')
<form action="{{ route('register.store') }}" method="post" class="bg-black p-6 rounded shadow-md w-96 mx-auto">
    @csrf
    <h2 class="text-2xl font-bold mb-4">Criar Conta</h2>
    <div class="mb-4">
        <label for="name">Nome</label>
        <input type="text" name="name" value="{{ old('name') }}" class="w-full mt-1 p-2 border rounded @error('name') border-red-500 @enderror"> 
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror

        <label for="email">Email</label>
        <input type="text" name="email" value="{{ old('email') }}" class="w-full mt-1 p-2 border rounded @error('email') border-red-500 @enderror"> 
        @error('email')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror

        <label for="password">Senha</label>
        <input type="text" name="password" value="{{ old('password') }}" class="w-full mt-1 p-2 border rounded @error('password') border-red-500 @enderror"> 
        @error('password')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror


        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Criar</button>

    </div>
</form>
@endsection
