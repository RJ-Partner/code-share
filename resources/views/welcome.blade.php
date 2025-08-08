@extends('layouts.app')

@section('content')
<div class="gradient-bg min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-4xl w-full text-center mb-12">
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg">
                <i class="fas fa-code text-white text-3xl"></i>
            </div>
        </div>
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-4">DevSync</h1>
        <p class="text-xl md:text-2xl text-blue-200 mb-8">Real-time collaborative code editor</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('room.create') }}" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-semibold py-3 px-8 rounded-xl transition duration-300 shadow-lg transform hover:scale-105">
                <i class="fas fa-plus mr-2"></i> Create New Room
            </a>
        </div>
    </div>
    
    <div class="glass-effect rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-6 text-center">Join Existing Room</h2>
        <form id="join-room-form">
            @csrf
            <div class="mb-4">
                <label for="join-room-id" class="block text-blue-200 mb-2">Room ID</label>
                <div class="flex">
                    <input type="text" id="join-room-id" placeholder="Enter Room ID" required
                        class="flex-1 bg-slate-800 text-white py-3 px-4 rounded-l-xl focus:outline-none focus:ring-2 focus:ring-blue-500 border border-slate-700">
                    <button type="submit" class="bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white py-3 px-6 rounded-r-xl transition duration-300">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="mt-12 text-center text-blue-200">
        <p class="mb-2">No account required • Instant collaboration • Password protected rooms</p>
        <div class="flex justify-center space-x-6 mt-4">
            <div class="flex items-center">
                <i class="fas fa-lock text-green-400 mr-2"></i>
                <span>Secure</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                <span>Fast</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-users text-purple-400 mr-2"></i>
                <span>Collaborative</span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('join-room-form');
        const roomIdInput = document.getElementById('join-room-id');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const roomId = roomIdInput.value.trim();
            if (roomId) {
                window.location.href = `/room/${roomId}`;
            }
        });
    });
</script>
@endsection