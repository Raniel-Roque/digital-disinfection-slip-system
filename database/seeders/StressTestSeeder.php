<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Reason;
use App\Models\DisinfectionSlip;
use App\Models\Report;
use App\Models\Attachment;
use App\Models\Log;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StressTestSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the stress test seeder.
     * This seeds normal data first, then adds 5,000 entries for stress testing.
     */
    public function run(): void
    {
        $this->command->info('Starting stress test seeding...');
        
        // First, run the normal DatabaseSeeder
        $this->command->info('Running normal DatabaseSeeder...');
        $this->call(DatabaseSeeder::class);
        
        $this->command->info('Adding 5,000 stress test entries (processing in batches of 1,000)...');
        
        $batchSize = 1000;
        $totalBatches = 5;
        
        // Create 5,000 Users (Guards and Admins only, no Super Admins)
        // Create one at a time to ensure username uniqueness
        $this->command->info('Creating 5,000 users (guards and admins)...');
        
        // Helper function to generate unique username (matches system logic)
        $generateUniqueUsername = function($firstName, $lastName) {
            // Trim whitespace from names
            $firstName = trim($firstName);
            $lastName = trim($lastName);
            
            if (empty($firstName) || empty($lastName)) {
                // Fallback to unique username if names are empty
                return fake()->unique()->userName();
            }
            
            $firstLetter = strtoupper(substr($firstName, 0, 1));
            // Get first word of last name (handles cases like "De Guzman")
            $lastNameWords = preg_split('/\s+/', $lastName);
            $firstWordOfLastName = $lastNameWords[0];
            $baseUsername = $firstLetter . $firstWordOfLastName;
            $username = $baseUsername;
            $counter = 0;
            
            // Use case-insensitive comparison like the system does
            /** @phpstan-ignore-next-line */
            while (User::whereRaw('LOWER(username) = ?', [strtolower($username)])->exists()) {
                $counter++;
                $username = $baseUsername . $counter;
            }
            
            return $username;
        };
        
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                $firstName = fake()->firstName();
                $lastName = fake()->lastName();
                $username = $generateUniqueUsername($firstName, $lastName);
                
                User::factory()->create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username, // Pre-generate unique username
                    'user_type' => fake()->randomElement([0, 1]), // 0: Guard, 1: Admin (no SuperAdmin)
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 users');
        
        // Get user IDs for attachments (only IDs, not full models)
        /** @phpstan-ignore-next-line */
        $userIds = User::pluck('id')->toArray();
        
        // Create 5,000 Trucks
        $this->command->info('Creating 5,000 trucks...');
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            Truck::factory()->count($batchSize)->create();
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 trucks');
        
        // Create 5,000 Drivers
        $this->command->info('Creating 5,000 drivers...');
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            Driver::factory()->count($batchSize)->create();
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 drivers');
        
        // Create 5,000 Reasons
        $this->command->info('Creating 5,000 reasons...');
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                Reason::create([
                    'reason_text' => fake()->sentence(3),
                    'is_disabled' => fake()->boolean(10), // 10% disabled
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 reasons');
        
        // Create 5,000 Locations
        $this->command->info('Creating 5,000 locations...');
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                $hasLogo = fake()->boolean(70);
                $attachmentId = null;
                if ($hasLogo && !empty($userIds)) {
                    // Use existing user for logo attachment to avoid creating duplicate users
                    $attachmentId = Attachment::factory()->logo()->create([
                        'user_id' => fake()->randomElement($userIds),
                    ])->id;
                }
                
                Location::create([
                    'location_name' => fake()->randomElement(['BGC', 'Baliwag', 'San Rafael', 'Angeles', 'Tarlac', 'Pampanga', 'Bulacan', 'Manila', 'Laguna', 'Cavite', 'Batangas', 'Quezon', 'Nueva Ecija', 'Zambales']) . ' ' . fake()->randomElement(['Hatchery', 'Farm', 'Facility', 'Processing Plant', 'Distribution Center']),
                    'attachment_id' => $attachmentId,
                    'disabled' => false,
                    'create_slip' => fake()->boolean(80),
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 locations');
        
        // Get IDs only (not full models) to save memory - refresh after each batch
        $this->command->info('Loading relationship data...');
        /** @phpstan-ignore-next-line */
        $truckIds = Truck::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $driverIds = Driver::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $locationIds = Location::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $reasonIds = Reason::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $guardIds = User::where('user_type', 0)->pluck('id')->toArray();
        
        // Create 5,000 Disinfection Slips
        $this->command->info('Creating 5,000 disinfection slips...');
        
        // Helper function to generate slip ID for a specific year
        $generateSlipIdForYear = function($year) {
            $yearCode = date('y', strtotime("$year-01-01")); // Get 2-digit year
            
            // Get the last slip ID for this year
            $lastSlip = DisinfectionSlip::withTrashed()
                ->where('slip_id', 'like', $yearCode . '-%')
                ->orderBy('slip_id', 'desc')
                ->first();
            
            if ($lastSlip) {
                // Extract the number part and increment
                $lastNumber = (int) substr($lastSlip->slip_id, 3); // Get number after "YY-"
                $newNumber = $lastNumber + 1;
            } else {
                // First slip of the year
                $newNumber = 1;
            }
            
            // Format: YY-00001
            return $yearCode . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        };
        
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                $status = fake()->randomElement([0, 1, 2, 3]);
                $hasAttachment = fake()->boolean(60);
                $attachmentIds = null;
                if ($hasAttachment && !empty($userIds)) {
                    $attachmentIds = [Attachment::factory()->create([
                        'user_id' => fake()->randomElement($userIds),
                    ])->id];
                }
                
                // Randomize created_at across multiple years (2024, 2025, 2026, 2027)
                $createdAt = fake()->dateTimeBetween('2024-01-01', '2027-12-31');
                $createdYear = (int) $createdAt->format('Y');
                
                // Generate slip_id for the year of created_at
                $slipId = $generateSlipIdForYear($createdYear);
                
                // For completed slips, ensure completed_at is after created_at
                $completedAt = null;
                if ($status === 3) {
                    // completed_at should be after created_at, but within a reasonable timeframe
                    $completedAt = fake()->dateTimeBetween($createdAt, $createdAt->format('Y-m-d H:i:s') . ' +30 days');
                }
                
                // Use create() directly with explicit slip_id and timestamps
                DisinfectionSlip::create([
                    'slip_id' => $slipId,
                    'truck_id' => fake()->randomElement($truckIds),
                    'location_id' => fake()->randomElement($locationIds),
                    'destination_id' => fake()->randomElement($locationIds),
                    'driver_id' => fake()->randomElement($driverIds),
                    'hatchery_guard_id' => fake()->randomElement($guardIds),
                    'received_guard_id' => fake()->boolean(80) && !empty($guardIds) ? fake()->randomElement($guardIds) : null,
                    'reason_id' => fake()->boolean(70) && !empty($reasonIds) ? fake()->randomElement($reasonIds) : null,
                    'remarks_for_disinfection' => fake()->optional(0.7)->sentence(),
                    'attachment_ids' => $attachmentIds,
                    'status' => $status,
                    'completed_at' => $completedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $completedAt ?? $createdAt, // updated_at should be completed_at if completed, otherwise created_at
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 disinfection slips');
        
        // Create 5,000 Attachments
        $this->command->info('Creating 5,000 attachments...');
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                Attachment::factory()->create([
                    'user_id' => fake()->randomElement($userIds),
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 attachments');
        
        // Create 5,000 Reports
        $this->command->info('Creating 5,000 reports...');
        /** @phpstan-ignore-next-line */
        $slipIds = DisinfectionSlip::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $adminsAndSuperAdmins = User::whereIn('user_type', [1, 2])->pluck('id')->toArray();
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                $hasSlip = fake()->boolean(70);
                $isResolved = fake()->boolean(30);
                $resolvedAt = $isResolved ? fake()->dateTimeBetween('-6 months', 'now') : null;
                $resolvedBy = $isResolved && !empty($adminsAndSuperAdmins) 
                    ? fake()->randomElement($adminsAndSuperAdmins) 
                    : null;
                
                Report::create([
                    'user_id' => fake()->randomElement($userIds),
                    'slip_id' => $hasSlip && !empty($slipIds) ? fake()->randomElement($slipIds) : null,
                    'description' => fake()->paragraph(),
                    'resolved_at' => $resolvedAt,
                    'resolved_by' => $resolvedBy,
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 reports');
        
        // Create 5,000 Audit Logs
        $this->command->info('Creating 5,000 audit logs...');
        
        // Get all model IDs for creating realistic logs
        /** @phpstan-ignore-next-line */
        $allUserIds = User::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $allTruckIds = Truck::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $allDriverIds = Driver::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $allLocationIds = Location::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $allSlipIds = DisinfectionSlip::pluck('id')->toArray();
        /** @phpstan-ignore-next-line */
        $allReportIds = Report::pluck('id')->toArray();
        
        // Define model types and their corresponding IDs
        $modelTypes = [
            'App\Models\User' => $allUserIds,
            'App\Models\Truck' => $allTruckIds,
            'App\Models\Driver' => $allDriverIds,
            'App\Models\Location' => $allLocationIds,
            'App\Models\DisinfectionSlip' => $allSlipIds,
            'App\Models\Report' => $allReportIds,
        ];
        
        // Define actions and their probabilities
        $actions = [
            'create' => 40,  // 40% creates
            'update' => 35,  // 35% updates
            'delete' => 15,  // 15% deletes
            'restore' => 5,  // 5% restores
            'custom' => 5,   // 5% custom actions
        ];
        
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            for ($i = 0; $i < $batchSize; $i++) {
                // Select a random user to perform the action
                /** @phpstan-ignore-next-line */
                $user = User::find(fake()->randomElement($allUserIds));
                
                // Select a random model type
                $modelType = fake()->randomElement(array_keys($modelTypes));
                $modelIds = $modelTypes[$modelType];
                
                if (empty($modelIds)) {
                    continue; // Skip if no models of this type exist
                }
                
                $modelId = fake()->randomElement($modelIds);
                
                // Select action based on probabilities
                $actionRand = fake()->numberBetween(1, 100);
                $action = 'create';
                $cumulative = 0;
                foreach ($actions as $act => $prob) {
                    $cumulative += $prob;
                    if ($actionRand <= $cumulative) {
                        $action = $act;
                        break;
                    }
                }
                
                // Generate description based on action and model type
                $modelName = class_basename($modelType);
                $descriptions = [
                    'create' => "Created {$modelName}",
                    'update' => "Updated {$modelName}",
                    'delete' => "Deleted {$modelName}",
                    'restore' => "Restored {$modelName}",
                    'custom' => fake()->randomElement([
                        "Completed disinfection for {$modelName}",
                        "Started disinfection for {$modelName}",
                        "Reset password for {$modelName}",
                        "Changed status for {$modelName}",
                    ]),
                ];
                $description = $descriptions[$action] ?? "Performed action on {$modelName}";
                
                // Generate changes JSON based on action
                $changes = null;
                if ($action === 'create') {
                    $changes = [
                        'new_values' => [
                            'id' => $modelId,
                            'created_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),
                        ],
                    ];
                } elseif ($action === 'update') {
                    $changes = [
                        'old_values' => [
                            'status' => fake()->randomElement([0, 1, 2]),
                            'disabled' => fake()->boolean(),
                        ],
                        'new_values' => [
                            'status' => fake()->randomElement([0, 1, 2, 3]),
                            'disabled' => fake()->boolean(),
                        ],
                        'field_changes' => [
                            'status' => [
                                'old' => fake()->randomElement([0, 1, 2]),
                                'new' => fake()->randomElement([0, 1, 2, 3]),
                            ],
                        ],
                    ];
                } elseif ($action === 'delete') {
                    $changes = [
                        'old_values' => [
                            'id' => $modelId,
                            'deleted_at' => null,
                        ],
                    ];
                } elseif ($action === 'restore') {
                    $changes = [
                        'old_values' => [
                            'id' => $modelId,
                            'deleted_at' => fake()->dateTimeBetween('-6 months', '-1 day')->format('Y-m-d H:i:s'),
                        ],
                    ];
                }
                
                // Add location context if user is a guard (30% chance)
                if ($user && $user->user_type === 0 && fake()->boolean(30)) {
                    if ($changes === null) {
                        $changes = [];
                    }
                    $changes['location_context'] = [
                        'location_id' => fake()->randomElement($allLocationIds),
                    ];
                }
                
                // Randomize created_at across multiple years (2024-2027)
                $createdAt = fake()->dateTimeBetween('2024-01-01', '2027-12-31');
                
                // Create the audit log
                Log::create([
                    'user_id' => $user->id,
                    'user_first_name' => $user->first_name,
                    'user_middle_name' => $user->middle_name,
                    'user_last_name' => $user->last_name,
                    'user_username' => $user->username,
                    'user_type' => $user->user_type,
                    'action' => $action,
                    'model_type' => $modelType,
                    'model_id' => $modelId,
                    'description' => $description,
                    'changes' => $changes,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
            $this->command->info("  Batch " . ($batch + 1) . "/{$totalBatches} completed");
        }
        $this->command->info('✓ Created 5,000 audit logs');
        
        $this->command->info('✓ Stress test seeding completed successfully!');
        $this->command->info('Total entries created:');
        $this->command->info('  - Users (guards/admins): 5,000');
        $this->command->info('  - Trucks: 5,000');
        $this->command->info('  - Drivers: 5,000');
        $this->command->info('  - Locations: 5,000');
        $this->command->info('  - Reasons: 5,000');
        $this->command->info('  - Disinfection Slips: 5,000');
        $this->command->info('  - Attachments: 5,000');
        $this->command->info('  - Reports: 5,000');
        $this->command->info('  - Audit Logs: 5,000');
    }
}
