@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center h-screen bg-gray-900 text-white">
    <div class="text-center max-w-md p-8 bg-gray-800 rounded-xl shadow-2xl">
        <div class="mb-6">
            <i class="fas fa-power-off text-6xl text-red-500 mb-4"></i>
            <h1 class="text-3xl font-bold mb-2">Room Inactive</h1>
            <p class="text-gray-300 mb-6">The room "{{ strtoupper($room->name) }}" is currently inactive.</p>
        </div>
        
        <div class="mb-8 p-4 bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-400 mb-2">This happens when all users have left the room.</p>
            <p class="text-sm text-gray-400">Room ID: <span class="font-mono text-blue-400">{{ strtoupper($room->room_id) }}</span></p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                <i class="fas fa-home mr-2"></i> Go to Home
            </a>
            {{-- <button id="reactivate-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                <i class="fas fa-redo mr-2"></i> Reactivate Room
            </button> --}}
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reactivateBtn = document.getElementById('reactivate-btn');
        const roomId = '{{ $room->room_id }}';
        
        if (reactivateBtn) {
            reactivateBtn.addEventListener('click', function() {
                fetch(`/room/${roomId}/reactivate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/room/${roomId}`;
                    } else {
                        alert('Failed to reactivate room. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        }
    });
</script>
@endpush
@endsection