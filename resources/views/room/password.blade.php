@extends('layouts.app')

@section('content')
<div class="gradient-bg min-h-screen flex flex-col items-center justify-center p-4">
    <div class="glass-effect rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-lock text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Password Required</h1>
            <p class="text-blue-200">This room is protected with a password</p>
        </div>
        
        <form action="{{ route('room.verify', $room->room_id) }}" method="POST">
            @csrf
            <div class="mb-6">
                <label for="password" class="block text-blue-200 mb-2">Enter Password</label>
                <input type="password" id="password" name="password" required autofocus
                    class="w-full bg-slate-800 text-white py-3 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-700">
                @error('password')
                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-semibold py-3 px-4 rounded-xl transition duration-300 shadow-lg">
                Enter Room
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="{{ url('/') }}" class="text-blue-300 hover:text-white transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Home
            </a>
        </div>
    </div>
</div>
@endsection