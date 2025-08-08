<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 
        'room_id', 
        'password',
        'is_public', 
        'read_only',
        'is_active',
        'code'
    ];

    /**
     * Track active user in the room
     */
    public function trackActiveUser($userId, $userInfo)
    {
        $key = "room_{$this->id}_active_users";
        $usersByIp = Cache::get($key, []);
        
        // Get user's IP
        $userIp = $userInfo['ip'];
        
        // Initialize the IP entry if it doesn't exist
        if (!isset($usersByIp[$userIp])) {
            $usersByIp[$userIp] = [
                'sessions' => [],
                'lastActivity' => now()->timestamp,
            ];
        }
        
        // Update or add user session
        $usersByIp[$userIp]['sessions'][$userId] = $userInfo;
        $usersByIp[$userIp]['lastActivity'] = now()->timestamp;
        
        // Store with expiration (e.g., 5 minutes)
        Cache::put($key, $usersByIp, now()->addMinutes(5));
        
        // Flatten the structure for easier processing
        return $this->flattenUsersByIp($usersByIp);
    }

    /**
     * Remove user session from active users
     */
    public function removeActiveUser($userId)
    {
        $key = "room_{$this->id}_active_users";
        $usersByIp = Cache::get($key, []);
        
        // Find and remove the user session
        foreach ($usersByIp as $ip => $ipData) {
            if (isset($ipData['sessions'][$userId])) {
                unset($usersByIp[$ip]['sessions'][$userId]);
                
                // If no more sessions for this IP, remove the IP entry
                if (empty($ipData['sessions'])) {
                    unset($usersByIp[$ip]);
                }
                
                break;
            }
        }
        
        // Store with expiration
        Cache::put($key, $usersByIp, now()->addMinutes(5));
        
        // Flatten the structure for easier processing
        return $this->flattenUsersByIp($usersByIp);
    }

    /**
     * Get active users in the room
     */
    public function getActiveUsers()
    {
        $key = "room_{$this->id}_active_users";
        $usersByIp = Cache::get($key, []);
        
        // Flatten the structure for easier processing
        return $this->flattenUsersByIp($usersByIp);
    }

    /**
     * Flatten the users by IP structure
     */
    private function flattenUsersByIp($usersByIp)
    {
        $flattened = [];
        
        foreach ($usersByIp as $ip => $ipData) {
            foreach ($ipData['sessions'] as $sessionId => $userInfo) {
                // Add the IP to the user info for display
                $userInfo['ip'] = $ip;
                $flattened[$sessionId] = $userInfo;
            }
        }
        
        return $flattened;
    }

    /**
     * Get or create session ID for a user IP
     */
    public function getSessionIdForIp($ip)
    {
        $key = "room_{$this->id}_active_users";
        $usersByIp = Cache::get($key, []);
        
        // Check if this IP already has sessions
        if (isset($usersByIp[$ip]) && !empty($usersByIp[$ip]['sessions'])) {
            // Return the first session ID for this IP
            return array_key_first($usersByIp[$ip]['sessions']);
        }
        
        // Create a new session ID
        return uniqid();
    }
}