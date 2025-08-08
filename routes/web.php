<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Room routes
Route::get('/room/create', [RoomController::class, 'create'])->name('room.create');
Route::post('/room', [RoomController::class, 'store'])->name('room.store');
Route::get('/room/{roomId}', [RoomController::class, 'show'])->name('room.show');
Route::post('/room/{roomId}/verify', [RoomController::class, 'verifyPassword'])->name('room.verify');
Route::get('/room/{roomId}/download', [RoomController::class, 'download'])->name('room.download');
Route::post('/room/{roomId}/code-update', [RoomController::class, 'codeUpdate'])->name('room.code-update');
Route::post('/room/{roomId}/heartbeat', [RoomController::class, 'heartbeat'])->name('room.heartbeat');
Route::post('/room/{roomId}/leave', [RoomController::class, 'leave'])->name('room.leave');
Route::get('/room/{roomId}/code-fetch', [RoomController::class, 'codeFetch']);
Route::post('/room/{roomId}/reactivate', [RoomController::class, 'reactivate'])->name('room.reactivate');
Route::get('/room/{roomId}/status', [RoomController::class, 'status'])->name('room.status');