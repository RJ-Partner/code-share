<?php
namespace App\Http\Controllers;

use App\Events\CodeUpdated;
use App\Events\RoomInactive;
use App\Events\UserActivity;
use App\Models\RoomUser;
use DB;
use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    // Cache keys
    private const ACTIVE_USERS_CACHE_PREFIX = 'room_active_users_';
    private const USER_SESSION_PREFIX = 'room_user_session_';

    // Cache duration (5 minutes)
    private const CACHE_DURATION = 300;

    public function create()
    {
        return view('room.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_id' => 'required|string|max:10|unique:rooms,room_id',
            'password' => 'nullable|string|min:6',
        ]);

        $readOnly = $request->read_only == 'on' ? true : false;

        $room = Room::create([
            'name' => $validated['name'],
            'room_id' => $validated['room_id'],
            'password' => $validated['password'] ? bcrypt($validated['password']) : null,
            'is_public' => empty($validated['password']),
            'read_only' => $readOnly,
            'code' => '// Welcome to DevSync!',
        ]);

        // Store admin session
        session([
            self::USER_SESSION_PREFIX . $room->room_id => [
                'is_admin' => true,
                'user_id' => $this->generateUserId($request->ip()),
            ],
        ]);

        return redirect()->route('room.show', $room->room_id);
    }

    // public function show(Request $request, $roomId)
    // {
    //     $room = Room::where('room_id', $roomId)->where('is_active', true)->firstOrFail();

    //     // Check access
    //     $sessionData = session(self::USER_SESSION_PREFIX . $roomId, []);
    //     $hasAccess = $room->is_public || !empty($sessionData);

    //     if (!$hasAccess) {
    //         return view('room.password', compact('room'));
    //     }

    //     // Generate user ID
    //     $userId = $sessionData['user_id'] ?? $this->generateUserId($request->ip());

    //     // Update session
    //     session([
    //         self::USER_SESSION_PREFIX . $roomId => [
    //             'is_admin' => $sessionData['is_admin'] ?? false,
    //             'user_id' => $userId,
    //         ],
    //     ]);

    //     // Track user
    //     $userInfo = $this->getUserInfo($request, $userId, $sessionData['is_admin'] ?? false);
    //     $activeUsers = $this->trackActiveUser($roomId, $userId, $userInfo);
    //     $isAdmin = $sessionData['is_admin'] ?? false;

    //     // Broadcast join event
    //     broadcast(new UserActivity($roomId, $activeUsers, 'join'));

    //     return view('room.show', compact('room', 'activeUsers', 'userId', 'isAdmin'));
    // }
    public function show(Request $request, $roomId)
    {
        try {
            $room = Room::where('room_id', $roomId)->firstOrFail();

            // Check if room is inactive
            if (!$room->is_active) {
                return view('room.inactive', compact('room'));
            }

            // Check access
            $sessionData = session(self::USER_SESSION_PREFIX . $roomId, []);
            $hasAccess = $room->is_public || !empty($sessionData);

            if (!$hasAccess) {
                return view('room.password', compact('room'));
            }

            // Generate user ID
            $userId = $sessionData['user_id'] ?? $this->generateUserId($request->ip());

            // Update session
            session([
                self::USER_SESSION_PREFIX . $roomId => [
                    'is_admin' => $sessionData['is_admin'] ?? false,
                    'user_id' => $userId,
                ],
            ]);

            // Track user
            $userInfo = $this->getUserInfo($request, $userId, $sessionData['is_admin'] ?? false);
            $activeUsers = $this->trackActiveUser($roomId, $userId, $userInfo);
            $isAdmin = $sessionData['is_admin'] ?? false;

            // Broadcast join event
            broadcast(new UserActivity($roomId, $activeUsers, 'join'));

            return view('room.show', compact('room', 'activeUsers', 'userId', 'isAdmin'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect('/')->withErrors(['error' => 'Room not found']);
        } catch (\Exception $e) {
            Log::error('Room access failed: ' . $e->getMessage());
            return redirect('/')->withErrors(['error' => 'Failed to access room. Please try again.']);
        }
    }

    public function verifyPassword(Request $request, $roomId)
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $room = Room::where('room_id', $roomId)->where('is_active', true)->firstOrFail();

        if (Hash::check($validated['password'], $room->password)) {
            $userId = $this->generateUserId($request->ip());

            // Store session
            session([
                self::USER_SESSION_PREFIX . $roomId => [
                    'is_admin' => false,
                    'user_id' => $userId,
                ],
            ]);

            // Track user
            $userInfo = $this->getUserInfo($request, $userId, false);
            $activeUsers = $this->trackActiveUser($roomId, $userId, $userInfo);

            // Broadcast join event
            broadcast(new UserActivity($roomId, $activeUsers, 'join'));

            return redirect()->route('room.show', $roomId);
        }

        return back()->withErrors(['password' => 'Incorrect password']);
    }

    public function codeUpdate(Request $request, $roomId)
    {
        $room = Room::where('room_id', $roomId)->where('is_active', true)->firstOrFail();

        // Check admin access
        $sessionData = session(self::USER_SESSION_PREFIX . $roomId, []);

        $room->code = $request->code;
        $room->save();

        // Get user info
        $userId = $sessionData['user_id'];
        $userInfo = $this->getUserInfo($request, $userId, true);
        $activeUsers = $this->trackActiveUser($roomId, $userId, $userInfo);

        // Broadcast events
        broadcast(new CodeUpdated($roomId, $userId, $userInfo));
        broadcast(new UserActivity($roomId, $activeUsers, 'update'));

        return response()->json([
            'success' => true,
            'activeUsers' => $activeUsers,
        ]);
    }

    public function download($roomId)
    {
        $room = Room::where('room_id', $roomId)->where('is_active', true)->firstOrFail();

        // Check admin access
        $sessionData = session(self::USER_SESSION_PREFIX . $roomId, []);
        if (empty($sessionData['is_admin'])) {
            abort(403, 'Only admin can download code');
        }

        $filename = "devsync-{$room->room_id}.html";

        return response($room->code, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function heartbeat(Request $request, $roomId)
    {
        $validated = $request->validate([
            'userId' => 'required|string',
        ]);

        $sessionData = session(self::USER_SESSION_PREFIX . $roomId, []);
        $userInfo = $this->getUserInfo($request, $validated['userId'], $sessionData['is_admin'] ?? false);
        $activeUsers = $this->trackActiveUser($roomId, $validated['userId'], $userInfo);

        // Broadcast activity
        broadcast(new UserActivity($roomId, $activeUsers, 'update'));

        $this->removeInactiveUsers();

        return response()->json([
            'success' => true,
            'activeUsers' => $activeUsers,
        ]);
    }

    public function leave(Request $request, $roomId)
    {
        $validated = $request->validate([
            'userId' => 'required|string',
        ]);

        // Remove user from active users
        $activeUsers = $this->removeActiveUser($roomId, $validated['userId']);

        // Broadcast leave event
        broadcast(new UserActivity($roomId, $activeUsers, 'leave'));

        return response()->json([
            'success' => true,
            'activeUsers' => $activeUsers,
        ]);
    }

    /**
     * Generate a unique user ID based on IP and random string
     */
    private function generateUserId(string $ip): string
    {
        return hash('sha256', $ip . Str::random(10));
    }

    /**
     * Get user information array
     */
    private function getUserInfo(Request $request, string $userId, bool $isAdmin): array
    {
        return [
            'id' => $userId,
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'isAdmin' => $isAdmin,
            'lastActivity' => now()->timestamp,
        ];
    }

    /**
     * Track active user in cache
     */
    private function trackActiveUser(string $roomId, string $userId, array $userInfo): array
    {
        $cacheKey = self::ACTIVE_USERS_CACHE_PREFIX . $roomId;
        $activeUsers = Cache::get($cacheKey, []);

        // Update user info
        $activeUsers[$userId] = $userInfo;

        // Store in cache
        Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);

        // Only save to DB if activeUsers is not empty and user info is valid
        if (!empty($activeUsers) && !empty($activeUsers)) {
            DB::table('room_users')->updateOrInsert(
                ['room_id' => $roomId, 'user_id' => $userId],
                [
                    'ip' => $userInfo['ip'] ?? null,
                    'user_agent' => $userInfo['userAgent'] ?? null,
                    'is_admin' => $userInfo['isAdmin'] ?? false,
                    'last_activity' => now(),
                    'updated_at' => now(),
                ],
            );
        } else {
            // Remove user from cache if present
            unset($activeUsers[$userId]);
            Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);

            // Delete from DB
            RoomUser::where('room_id', $roomId)->where('user_id', $userId)->delete(); // Soft delete

            // ✅ If no active users left in DB, mark room as inactive
            $remainingUsers = DB::table('room_users')->where('room_id', $roomId)->count();
            if ($remainingUsers === 0) {
                DB::table('rooms')
                    ->where('id', $roomId)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                // Broadcast room inactive event
                broadcast(new RoomInactive($roomId));
            }
        }

        return $activeUsers;
    }

    /**
     * Remove active user from cache
     */
    private function removeActiveUser(string $roomId, string $userId): array
    {
        $cacheKey = self::ACTIVE_USERS_CACHE_PREFIX . $roomId;
        $activeUsers = Cache::get($cacheKey, []);

        // Remove user if exists
        if (isset($activeUsers[$userId])) {
            unset($activeUsers[$userId]);
            Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);

            // Delete from DB
            RoomUser::where('room_id', $roomId)->where('user_id', $userId)->delete(); // Soft delete

            // ✅ If no active users left in DB, mark room as inactive
            $remainingUsers = DB::table('room_users')->where('room_id', $roomId)->count();
            if ($remainingUsers === 0) {
                DB::table('rooms')
                    ->where('id', $roomId)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                // Broadcast room inactive event
                broadcast(new RoomInactive($roomId));
            }
        }

        return $activeUsers;
    }

    private function removeInactiveUsers(): void
    {
        $expired = now()->subSeconds(self::CACHE_DURATION);

        // Get users to remove
        $expiredUsers = DB::table('room_users')->where('last_activity', '<', $expired)->get();

        // Group by room_id to check room activity after removal
        $affectedRooms = [];

        foreach ($expiredUsers as $user) {
            // Remove from cache
            $cacheKey = self::ACTIVE_USERS_CACHE_PREFIX . $user->room_id;
            $activeUsers = Cache::get($cacheKey, []);

            if (isset($activeUsers[$user->user_id])) {
                unset($activeUsers[$user->user_id]);
                Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);
            }

            // Delete from DB
            DB::table('room_users')->where('room_id', $user->room_id)->where('user_id', $user->user_id)->delete();

            // Track affected rooms
            $affectedRooms[$user->room_id] = true;

            // Broadcast leave event
            broadcast(new UserActivity($user->room_id, $activeUsers, 'leave'));
        }

        // Check each affected room to see if it should be marked inactive
        foreach (array_keys($affectedRooms) as $roomId) {
            $remainingUsers = DB::table('room_users')->where('room_id', $roomId)->count();

            if ($remainingUsers === 0) {
                // Mark room as inactive
                DB::table('rooms')
                    ->where('room_id', $roomId)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);

                // Broadcast room inactive event
                broadcast(new RoomInactive($roomId));
            }
        }
    }

    public function reactivate(Request $request, $roomId)
    {
        try {
            $room = Room::where('room_id', $roomId)->firstOrFail();

            // Update room status
            $room->is_active = true;
            $room->save();

            return response()->json([
                'success' => true,
                'message' => 'Room reactivated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Room reactivation failed: ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to reactivate room',
                ],
                500,
            );
        }
    }

    public function status($roomId)
    {
        try {
            $room = Room::where('room_id', $roomId)->firstOrFail();

            return response()->json([
                'is_active' => $room->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'is_active' => false,
                ],
                404,
            );
        }
    }

    public function codeFetch($roomId)
    {
        $room = Room::where('room_id', $roomId)->where('is_active', true)->firstOrFail();

        return response()->json([
            'code' => $room->code ?? '',
        ]);
    }
}
