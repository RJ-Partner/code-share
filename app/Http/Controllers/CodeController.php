<?php

namespace App\Http\Controllers;

use App\Events\CodeChanged;
use Illuminate\Http\Request;

class CodeController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'roomId' => 'required|string',
            'userId' => 'required|string'
        ]);

        // Broadcast the code change event

        broadcast(new CodeChanged(
            $request->code,
            $request->roomId,
            $request->userId
        ))->toOthers();

        return response()->json([
            'status' => 'Code updated',
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
