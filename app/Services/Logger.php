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

        return Log::create(array_merge($userData, [
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'changes' => !empty($changes) ? $changes : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]));
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
}

