<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class Logger
{
    /**
     * Log an action with detailed information.
     *
     * @param string $action The action performed (create, update, delete, restore, etc.)
     * @param string|null $modelType The model class name (e.g., App\Models\DisinfectionSlip)
     * @param int|null $modelId The ID of the affected model
     * @param string|null $description Human-readable description
     * @param array|null $oldValues Previous values (for updates/deletes)
     * @param array|null $newValues New values (for updates/creates)
     * @param array|null $additionalInfo Additional information to include in changes JSON
     * @return Log
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $additionalInfo = null
    ): Log {
        $user = Auth::user();
        
        // Prepare detailed changes information
        $changes = [];
        
        if ($oldValues !== null || $newValues !== null) {
            // Calculate field-level changes for updates
            if ($oldValues !== null && $newValues !== null) {
                $fieldChanges = [];
                $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                
                foreach ($allKeys as $key) {
                    $oldValue = $oldValues[$key] ?? null;
                    $newValue = $newValues[$key] ?? null;
                    
                    // Only include fields that actually changed
                    if ($oldValue !== $newValue) {
                        $fieldChanges[$key] = [
                            'old' => $oldValue,
                            'new' => $newValue,
                        ];
                    }
                }
                
                $changes['field_changes'] = $fieldChanges;
                $changes['old_values'] = $oldValues;
                $changes['new_values'] = $newValues;
            } elseif ($oldValues !== null) {
                // For deletes, store old values
                $changes['old_values'] = $oldValues;
            } elseif ($newValues !== null) {
                // For creates, store new values
                $changes['new_values'] = $newValues;
            }
        }
        
        // Add location context if available
        $locationId = Session::get('location_id');
        if ($locationId) {
            $changes['location_context'] = ['location_id' => $locationId];
        }
        
        // Add super_guard information to changes for filtering/display purposes
        if ($user && $user->user_type === 0 && $user->super_guard) {
            $changes['user_super_guard'] = true;
        }
        
        // Add any additional information
        if ($additionalInfo !== null) {
            $changes = array_merge($changes, $additionalInfo);
        }

        // Get user information
        $userData = [
            'user_id' => $user?->id,
            'user_first_name' => $user?->first_name,
            'user_middle_name' => $user?->middle_name,
            'user_last_name' => $user?->last_name,
            'user_username' => $user?->username,
            'user_type' => $user?->user_type,
        ];

        try {
            // Get real client IP address, handling proxies and load balancers correctly
            $ipAddress = self::getRealClientIp();
            
            return Log::create(array_merge($userData, [
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'description' => $description,
                'changes' => !empty($changes) ? $changes : null,
                'ip_address' => $ipAddress,
                'user_agent' => Request::userAgent(),
            ]));
        } catch (\Exception $e) {
            // Log the error to Laravel's log system but don't fail the operation
            Log::error('Failed to create audit log', [
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);
            // Return a dummy log object to prevent errors
            return new Log();
        }
    }

    /**
     * Log a create action.
     */
    public static function create(string $modelType, ?int $modelId, string $description, ?array $newValues = null, ?array $additionalInfo = null): Log
    {
        return self::log('create', $modelType, $modelId, $description, null, $newValues, $additionalInfo);
    }

    /**
     * Log an update action.
     */
    public static function update(string $modelType, ?int $modelId, string $description, ?array $oldValues = null, ?array $newValues = null, ?array $additionalInfo = null): Log
    {
        return self::log('update', $modelType, $modelId, $description, $oldValues, $newValues, $additionalInfo);
    }

    /**
     * Log a delete action.
     */
    public static function delete(string $modelType, ?int $modelId, string $description, ?array $oldValues = null, ?array $additionalInfo = null): Log
    {
        return self::log('delete', $modelType, $modelId, $description, $oldValues, null, $additionalInfo);
    }

    /**
     * Log a restore action.
     */
    public static function restore(string $modelType, ?int $modelId, string $description, ?array $additionalInfo = null): Log
    {
        return self::log('restore', $modelType, $modelId, $description, null, null, $additionalInfo);
    }

    /**
     * Log a custom action.
     */
    public static function custom(string $action, string $description, ?string $modelType = null, ?int $modelId = null, ?array $additionalInfo = null): Log
    {
        return self::log($action, $modelType, $modelId, $description, null, null, $additionalInfo);
    }

    /**
     * Get the real client IP address, handling proxies and load balancers correctly.
     * 
     * This method checks multiple headers in order of preference:
     * 1. X-Forwarded-For (most common, first IP is usually the real client)
     * 2. X-Real-IP (used by Nginx and some proxies)
     * 3. CF-Connecting-IP (Cloudflare)
     * 4. Laravel's Request::ip() (uses TrustProxies middleware)
     * 5. REMOTE_ADDR (direct connection, no proxy)
     * 
     * @return string|null
     */
    private static function getRealClientIp(): ?string
    {
        // Check X-Forwarded-For header first (most common for proxies/load balancers)
        $forwardedFor = Request::header('X-Forwarded-For');
        if ($forwardedFor) {
            // X-Forwarded-For can contain multiple IPs: "client, proxy1, proxy2"
            // The first IP is usually the real client IP
            $ips = array_map('trim', explode(',', $forwardedFor));
            $ip = $ips[0];
            
            // Validate IP address (accept both public and private IPs)
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        // Check X-Real-IP header (used by some proxies like Nginx)
        $realIp = Request::header('X-Real-IP');
        if ($realIp) {
            $ip = trim($realIp);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        // Check CF-Connecting-IP header (Cloudflare)
        $cfIp = Request::header('CF-Connecting-IP');
        if ($cfIp) {
            $ip = trim($cfIp);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        // Fallback to Laravel's Request::ip() which uses TrustProxies middleware
        // This will return the real IP if proxies are trusted, or REMOTE_ADDR otherwise
        // The TrustProxies middleware is configured to trust all proxies and read X-Forwarded-For
        $ip = Request::ip();
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        // Last resort: get from $_SERVER['REMOTE_ADDR']
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($remoteAddr && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return $remoteAddr;
        }
        
        return null;
    }
}

