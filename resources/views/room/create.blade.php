@extends('layouts.app')

@section('content')
<div class="gradient-bg min-h-screen flex flex-col items-center justify-center p-4">
    <div class="glass-effect rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Create New Room</h1>
            <p class="text-blue-200">Set up your collaborative coding space</p>
        </div>
        
        <form action="{{ route('room.store') }}" method="POST">
            @csrf
            <div class="mb-6">
                <label for="name" class="block text-blue-200 mb-2">Room Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full bg-slate-800 text-white py-3 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-700">
                @error('name')
                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="room_id" class="block text-blue-200 mb-2">Room ID</label>
                <input type="text" id="room_id" name="room_id" value="{{ old('room_id') }}" required
                    class="w-full bg-slate-800 text-white py-3 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-700">
                <p class="mt-1 text-slate-400 text-sm">Unique identifier for your room</p>
                @error('room_id')
                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-blue-200 mb-2">Password (optional)</label>
                <input type="password" id="password" name="password" value="{{ old('password') }}"
                    class="w-full bg-slate-800 text-white py-3 px-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-700">
                <p class="mt-1 text-slate-400 text-sm">Leave empty to make room public</p>
                @error('password')
                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="mb-8">
                <div class="flex items-center">
                    <input type="checkbox" id="read_only" name="read_only" {{ old('read_only') ? 'checked' : '' }}
                        class="w-5 h-5 text-blue-600 bg-slate-800 border-slate-600 rounded focus:ring-blue-500 focus:ring-2">
                    <label for="read_only" class="ml-2 text-blue-200">Read Only Mode</label>
                </div>
                <p class="mt-1 text-slate-400 text-sm ml-7">Prevent others from editing your code</p>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-semibold py-3 px-4 rounded-xl transition duration-300 shadow-lg">
                Create Room
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