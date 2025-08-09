<?php
namespace App\Http\Controllers;
use App\Events\CodeUpdated;
use App\Events\RoomInactive;
use App\Events\UserActivity;
use App\Models\RoomUser;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class RoomController extends Controller
{
    // Cache keys
    private const ACTIVE_USERS_CACHE_PREFIX = 'room_active_users_';
    private const USER_SESSION_PREFIX = 'room_user_session_';
    private const CACHE_DURATION = 43200; // 12 hours in seconds

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
            'expired_at' => now()->addSeconds(self::CACHE_DURATION),
            'code' => '// Welcome to DevSync!',
        ]);

        // Generate display ID
        $displayId = $this->generateDisplayId();

        // Store admin session
        session([
            self::USER_SESSION_PREFIX . $room->room_id => [
                'is_admin' => true,
                'user_id' => $this->generateUserId($request->ip()),
                'display_id' => $displayId,
            ],
        ]);

        return redirect()->route('room.show', $room->room_id);
    }

    public function show(Request $request, $roomId)
    {
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

        // Generate user ID and display ID if not exists
        $userId = $sessionData['user_id'] ?? $this->generateUserId($request->ip());
        $displayId = $sessionData['display_id'] ?? $this->generateDisplayId();

        // Update session
        session([
            self::USER_SESSION_PREFIX . $roomId => [
                'is_admin' => $sessionData['is_admin'] ?? false,
                'user_id' => $userId,
                'display_id' => $displayId,
            ],
        ]);

        // Check if user was previously deleted and restore them
        $this->restoreUserIfDeleted($roomId, $userId);

        // Track user
        $userInfo = $this->getUserInfo($request, $userId, $sessionData['is_admin'] ?? false, $displayId);
        $activeUsers = $this->trackActiveUser($roomId, $userId, $userInfo);
        $isAdmin = $sessionData['is_admin'] ?? false;

        // Broadcast join event
        broadcast(new UserActivity($roomId, $activeUsers, 'join'));

        return view('room.show', compact('room', 'activeUsers', 'userId', 'isAdmin', 'displayId'));
    }

    private function restoreUserIfDeleted(string $roomId, string $userId): void
    {
        $roomUser = RoomUser::withTrashed()
            ->where('room_id', $roomId)
            ->where('user_id', $userId)
            ->first();

        if ($roomUser && $roomUser->trashed()) {
            $roomUser->restore();
            Log::info("User restored: {$userId} in room {$roomId}");
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
            $displayId = $this->generateDisplayId();

            // Store session
            session([
                self::USER_SESSION_PREFIX . $roomId => [
                    'is_admin' => false,
                    'user_id' => $userId,
                    'display_id' => $displayId,
                ],
            ]);

            // Track user
            $userInfo = $this->getUserInfo($request, $userId, false, $displayId);
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
        // if (empty($sessionData['is_admin'])) {
        //     abort(403, 'Only admin can update code');
        // }

        $room->code = $request->code;
        $room->save();

        // Get user info
        $userId = $sessionData['user_id'];
        $displayId = $sessionData['display_id'] ?? $this->generateDisplayId();
        $userInfo = $this->getUserInfo($request, $userId, true, $displayId);
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
        $displayId = $sessionData['display_id'] ?? $this->generateDisplayId();
        $userInfo = $this->getUserInfo($request, $validated['userId'], $sessionData['is_admin'] ?? false, $displayId);
        $activeUsers = $this->trackActiveUser($roomId, $validated['userId'], $userInfo);

        // Broadcast activity
        broadcast(new UserActivity($roomId, $activeUsers, 'update'));

        $this->removeInactiveUsers($roomId);

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

        Log::info("--------------------------------------------------------------");

        // Broadcast leave event
        broadcast(new UserActivity($roomId, $activeUsers, 'leave'));

        return response()->json([
            'success' => true,
            'activeUsers' => $activeUsers,
        ]);
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

    private function generateUserId(string $ip): string
    {
        return hash('sha256', $ip . Str::random(10));
    }

    private function generateDisplayId(): string
    {
        return strtoupper(Str::random(6));
    }

    private function getUserInfo(Request $request, string $userId, bool $isAdmin, string $displayId): array
    {
        return [
            'id' => $userId,
            'display_id' => $displayId,
            'ip' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'isAdmin' => $isAdmin,
            'lastActivity' => now()->timestamp,
        ];
    }

    private function trackActiveUser(string $roomId, string $userId, array $userInfo): array
    {
        $cacheKey = self::ACTIVE_USERS_CACHE_PREFIX . $roomId;
        $activeUsers = Cache::get($cacheKey, []);

        // Update user info
        $activeUsers[$userId] = $userInfo;

        // Store in cache
        Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);

        // Only save to DB if user info is valid
        if (!empty($userInfo)) {
            RoomUser::updateOrInsert(
                ['room_id' => $roomId, 'user_id' => $userId],
                [
                    'display_id' => $userInfo['display_id'] ?? null,
                    'ip' => $userInfo['ip'] ?? null,
                    'user_agent' => $userInfo['userAgent'] ?? null,
                    'is_admin' => $userInfo['isAdmin'] ?? false,
                    'updated_at' => now(),
                ]
            );
        }

        return $activeUsers;
    }

    private function removeActiveUser(string $roomId, string $userId): array
    {
        $cacheKey = self::ACTIVE_USERS_CACHE_PREFIX . $roomId;
        $activeUsers = Cache::get($cacheKey, []);

        // Remove user if exists
        if (isset($activeUsers[$userId])) {
            unset($activeUsers[$userId]);
            Cache::put($cacheKey, $activeUsers, self::CACHE_DURATION);

            Log::info("User Deleted: {$userId} in room {$roomId}");

            // // Delete from DB using Eloquent
            // RoomUser::where('room_id', $roomId)->where('user_id', $userId)->delete();

            // // If no active users left in DB, mark room as inactive
            // $remainingUsers = RoomUser::where('room_id', $roomId)->count();
            // if ($remainingUsers === 0) {
            //     Room::where('room_id', $roomId)->update(['is_active' => false]);
            //     broadcast(new RoomInactive($roomId));
            // }
        }

        return $activeUsers;
    }

    private function removeInactiveUsers($roomId): void
    {
        $inactiveRoom = Room::where('room_id', $roomId)
            ->where('expired_at', '<', now())
            ->first();

        if ($inactiveRoom) {
            // Step 1: Room ke saare users delete
            RoomUser::where('room_id', $inactiveRoom->room_id)->delete();

            // Step 2: Room inactive mark karo
            $inactiveRoom->is_active = false;
            $inactiveRoom->save();

            // Optional: broadcast event ki room inactive ho gaya
            broadcast(new RoomInactive($inactiveRoom->room_id));
        }
    }
}