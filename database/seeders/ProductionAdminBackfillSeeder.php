<?php

namespace Database\Seeders;

use App\Enums\PaymentFlow;
use App\Models\Claim;
use App\Models\ClientProfile;
use App\Models\ErrandOrderDetails;
use App\Models\HandymanAssignment;
use App\Models\HandymanMaterialsEntry;
use App\Models\Moving\ExecutorProfile;
use App\Models\Order;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\RepairTeamMember;
use App\Models\User;
use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\WorkSpecification;
use App\Models\WorkWarranty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionAdminBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $clients = $this->seedClientProfiles();
        $executors = $this->seedExecutorProfiles();
        $projects = $this->seedRepairProjects($clients, $executors);

        $this->seedRepairStages($projects);
        $this->seedRepairTeamMembers($projects, $executors);
        $this->seedHandymanAssignments($projects, $executors);
        $this->seedHandymanMaterials($projects, $executors);
        $this->seedClaims($projects, $clients);
        $this->seedErrandOrderDetails($clients, $executors);
        $this->seedWorkSpecifications($projects, $executors);
        $this->seedWorkWarranties($projects);
        $this->seedVirtualOfficeAgents();
    }

    protected function seedClientProfiles(): array
    {
        $blueprints = [
            [
                'name' => 'Liv Andersen',
                'email' => 'liv.andersen@demo.no',
                'phone' => '+47 401 10 101',
                'address_line' => 'Dronningens gate 26',
                'postal_code' => '8514',
                'city' => 'Narvik',
                'mobility_notes' => 'Prefers a helper for shopping and stairs.',
                'health_notes' => 'Needs a slower handoff and verbal confirmation.',
            ],
            [
                'name' => 'Arne Pedersen',
                'email' => 'arne.pedersen@demo.no',
                'phone' => '+47 401 10 102',
                'address_line' => 'Ankenesveien 40',
                'postal_code' => '8520',
                'city' => 'Ankenes',
                'mobility_notes' => 'Uses a cane on longer walks.',
                'health_notes' => 'Appointment reminders should be repeated by SMS.',
            ],
            [
                'name' => 'Nina Johansen',
                'email' => 'nina.johansen@demo.no',
                'phone' => '+47 401 10 103',
                'address_line' => 'Fjellveien 55',
                'postal_code' => '8530',
                'city' => 'Bjerkvik',
                'mobility_notes' => 'Requires apartment access coordination in advance.',
                'health_notes' => 'Sensitive to loud environments.',
            ],
        ];

        $profiles = [];

        foreach ($blueprints as $blueprint) {
            $user = User::updateOrCreate(
                ['email' => $blueprint['email']],
                [
                    'name' => $blueprint['name'],
                    'password' => Hash::make('6636'),
                    'phone' => $blueprint['phone'],
                    'phone_e164' => $blueprint['phone'],
                    'is_active' => true,
                ]
            );

            $profiles[$blueprint['email']] = ClientProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $blueprint['name'],
                    'date_of_birth' => now()->subYears(rand(67, 82))->startOfDay(),
                    'phone' => $blueprint['phone'],
                    'email' => $blueprint['email'],
                    'address_line' => $blueprint['address_line'],
                    'postal_code' => $blueprint['postal_code'],
                    'city' => $blueprint['city'],
                    'mobility_notes' => $blueprint['mobility_notes'],
                    'health_notes' => $blueprint['health_notes'],
                    'communication_preferences' => [
                        'primary' => 'phone',
                        'secondary' => 'sms',
                        'language' => 'no',
                    ],
                    'is_active' => true,
                ]
            );
        }

        return $profiles;
    }

    protected function seedExecutorProfiles(): array
    {
        $blueprints = [
            [
                'name' => 'Andreas Johansen',
                'email' => 'andreas.elec@bikube.no',
                'phone' => '+47 400 10 201',
                'vehicle_type' => 'van',
                'skills' => ['lighting', 'breaker-box', 'sockets', 'inspection'],
                'role' => 'Electrical Lead',
                'languages' => ['no', 'en'],
            ],
            [
                'name' => 'Ole Kristian Nilsen',
                'email' => 'ole.plumber@bikube.no',
                'phone' => '+47 400 10 202',
                'vehicle_type' => 'van',
                'skills' => ['plumbing', 'appliances', 'leak-fix'],
                'role' => 'Plumbing Specialist',
                'languages' => ['no', 'en'],
            ],
            [
                'name' => 'Dmytro Yavorskyi',
                'email' => 'dmytro.handyman@bikube.no',
                'phone' => '+47 400 10 203',
                'vehicle_type' => 'van',
                'skills' => ['furniture', 'mounting', 'basic-electric', 'basic-plumbing'],
                'role' => 'Senior Handyman',
                'languages' => ['uk', 'ru', 'en'],
            ],
            [
                'name' => 'Lena Berg',
                'email' => 'lena.kitchen@bikube.no',
                'phone' => '+47 400 10 204',
                'vehicle_type' => 'van',
                'skills' => ['kitchen', 'ikea', 'facade', 'appliance-mount'],
                'role' => 'Kitchen Installer',
                'languages' => ['no', 'en'],
            ],
            [
                'name' => 'Marlene Hakonsen',
                'email' => 'marlene.errand@bikube.no',
                'phone' => '+47 400 10 205',
                'vehicle_type' => 'car',
                'skills' => ['errands', 'pharmacy', 'document_service'],
                'role' => 'Errand Runner',
                'languages' => ['no', 'en'],
            ],
        ];

        $profiles = [];

        foreach ($blueprints as $blueprint) {
            $user = User::updateOrCreate(
                ['email' => $blueprint['email']],
                [
                    'name' => $blueprint['name'],
                    'password' => Hash::make(Str::slug($blueprint['name']).'-6636'),
                    'phone' => $blueprint['phone'],
                    'phone_e164' => $blueprint['phone'],
                    'is_active' => true,
                ]
            );

            $profiles[$blueprint['email']] = ExecutorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'vehicle_type' => $blueprint['vehicle_type'],
                    'skills' => $blueprint['skills'],
                    'max_volume' => 12,
                    'max_weight' => 900,
                    'insurance_limit' => 150000,
                    'rating' => 4.8,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now()->subMinutes(rand(5, 90)),
                    'metadata' => [
                        'role' => $blueprint['role'],
                        'languages' => $blueprint['languages'],
                        'region' => 'Narvik +60km',
                    ],
                ]
            );
        }

        return $profiles;
    }

    protected function seedRepairProjects(array $clients, array $executors): array
    {
        $blueprints = [
            [
                'title' => 'Washer Install and Leak Check',
                'client_email' => 'liv.andersen@demo.no',
                'executor_email' => 'ole.plumber@bikube.no',
                'base_price' => 899,
                'estimated_time' => '45-90 min',
                'city' => 'Narvik',
                'status' => 'scheduled',
            ],
            [
                'title' => 'IKEA Wardrobe Assembly',
                'client_email' => 'arne.pedersen@demo.no',
                'executor_email' => 'dmytro.handyman@bikube.no',
                'base_price' => 1499,
                'estimated_time' => '3-5 hours',
                'city' => 'Ankenes',
                'status' => 'in_progress',
            ],
            [
                'title' => 'Kitchen Appliance Fit-Out',
                'client_email' => 'nina.johansen@demo.no',
                'executor_email' => 'lena.kitchen@bikube.no',
                'base_price' => 1899,
                'estimated_time' => '2-4 hours',
                'city' => 'Bjerkvik',
                'status' => 'assessment',
            ],
        ];

        $projects = [];

        foreach ($blueprints as $index => $blueprint) {
            $client = $clients[$blueprint['client_email']];
            $executor = $executors[$blueprint['executor_email']];

            $order = Order::firstOrCreate(
                ['order_number' => 'ADM-BF-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $client->user_id,
                    'service_type' => 'handyman_project',
                    'assigned_to' => $executor->user_id,
                    'status' => 'scheduled',
                    'priority' => 'high',
                    'notes' => $blueprint['title'].' seeded for admin backfill.',
                    'scheduled_at' => now()->addDays($index + 1)->setTime(10 + $index, 0),
                    'total_amount' => $blueprint['base_price'],
                    'currency' => 'NOK',
                    'payment_status' => 'paid',
                    'payment_flow' => PaymentFlow::AuthorizeCapture,
                    'estimated_total' => $blueprint['base_price'] * 100,
                    'buffer_total' => (int) round($blueprint['base_price'] * 120),
                    'metadata' => ['source' => 'production_admin_backfill'],
                ]
            );

            $projects[$blueprint['title']] = RepairProject::updateOrCreate(
                ['title' => $blueprint['title']],
                [
                    'order_id' => $order->id,
                    'client_profile_id' => $client->id,
                    'project_manager_id' => $executor->id,
                    'description' => $blueprint['title'].' for '.$client->full_name,
                    'status' => $blueprint['status'],
                    'address_line' => $client->address_line,
                    'postal_code' => $client->postal_code,
                    'city' => $blueprint['city'],
                    'planned_start_at' => now()->addDays($index + 1)->setTime(10, 0),
                    'planned_finish_at' => now()->addDays($index + 1)->setTime(13, 0),
                    'base_price' => $blueprint['base_price'],
                    'estimated_time' => $blueprint['estimated_time'],
                    'region' => 'Narvik +60km',
                    'budget_estimate_minor' => $blueprint['base_price'] * 100,
                    'notes' => 'Backfilled for production admin visibility.',
                    'overall_progress_percent' => 25 + ($index * 20),
                ]
            );
        }

        return $projects;
    }

    protected function seedRepairStages(array $projects): void
    {
        $definitions = [
            ['name' => 'Intake', 'sequence' => 1, 'status' => 'completed', 'progress_percent' => 100],
            ['name' => 'Planning', 'sequence' => 2, 'status' => 'in_progress', 'progress_percent' => 60],
            ['name' => 'Execution', 'sequence' => 3, 'status' => 'planned', 'progress_percent' => 0],
        ];

        foreach ($projects as $project) {
            foreach ($definitions as $definition) {
                RepairStage::updateOrCreate(
                    [
                        'repair_project_id' => $project->id,
                        'sequence' => $definition['sequence'],
                    ],
                    [
                        'name' => $definition['name'],
                        'description' => $definition['name'].' stage for '.$project->title,
                        'status' => $definition['status'],
                        'planned_start_at' => $project->planned_start_at,
                        'planned_finish_at' => $project->planned_finish_at,
                        'progress_percent' => $definition['progress_percent'],
                    ]
                );
            }
        }
    }

    protected function seedRepairTeamMembers(array $projects, array $executors): void
    {
        $matrix = [
            'Washer Install and Leak Check' => [
                ['email' => 'ole.plumber@bikube.no', 'role' => 'Lead plumber', 'is_lead' => true],
                ['email' => 'andreas.elec@bikube.no', 'role' => 'Electrical safety check', 'is_lead' => false],
            ],
            'IKEA Wardrobe Assembly' => [
                ['email' => 'dmytro.handyman@bikube.no', 'role' => 'Assembly lead', 'is_lead' => true],
                ['email' => 'lena.kitchen@bikube.no', 'role' => 'Finish and alignment', 'is_lead' => false],
            ],
            'Kitchen Appliance Fit-Out' => [
                ['email' => 'lena.kitchen@bikube.no', 'role' => 'Kitchen installer', 'is_lead' => true],
                ['email' => 'ole.plumber@bikube.no', 'role' => 'Plumbing hookup', 'is_lead' => false],
            ],
        ];

        foreach ($matrix as $projectTitle => $members) {
            $project = $projects[$projectTitle] ?? null;
            if (! $project) {
                continue;
            }

            foreach ($members as $member) {
                $executor = $executors[$member['email']] ?? null;
                if (! $executor) {
                    continue;
                }

                RepairTeamMember::updateOrCreate(
                    [
                        'repair_project_id' => $project->id,
                        'executor_profile_id' => $executor->id,
                    ],
                    [
                        'role' => $member['role'],
                        'is_lead' => $member['is_lead'],
                        'notes' => 'Backfilled for admin team visibility.',
                    ]
                );
            }
        }
    }

    protected function seedHandymanAssignments(array $projects, array $executors): void
    {
        foreach ($projects as $title => $project) {
            $executor = match ($title) {
                'Washer Install and Leak Check' => $executors['ole.plumber@bikube.no'] ?? null,
                'IKEA Wardrobe Assembly' => $executors['dmytro.handyman@bikube.no'] ?? null,
                default => $executors['lena.kitchen@bikube.no'] ?? null,
            };

            if (! $executor || ! $project->order_id) {
                continue;
            }

            HandymanAssignment::updateOrCreate(
                [
                    'order_id' => $project->order_id,
                    'executor_profile_id' => $executor->id,
                ],
                [
                    'repair_project_id' => $project->id,
                    'status' => 'accepted',
                    'planned_start_at' => $project->planned_start_at,
                    'planned_finish_at' => $project->planned_finish_at,
                    'is_primary' => true,
                    'meta' => [
                        'title' => $title,
                        'workflow_status' => 'scheduled',
                        'source' => 'production_admin_backfill',
                    ],
                ]
            );
        }
    }

    protected function seedHandymanMaterials(array $projects, array $executors): void
    {
        $materials = [
            'Washer Install and Leak Check' => ['description' => 'Leak-safe connector kit', 'unit_price_minor' => 24900],
            'IKEA Wardrobe Assembly' => ['description' => 'Wall anchor and mounting pack', 'unit_price_minor' => 8900],
            'Kitchen Appliance Fit-Out' => ['description' => 'Kitchen leveling and fitting set', 'unit_price_minor' => 15900],
        ];

        foreach ($materials as $projectTitle => $material) {
            $project = $projects[$projectTitle] ?? null;
            if (! $project) {
                continue;
            }

            $executor = $project->projectManager ?: ($executors['dmytro.handyman@bikube.no'] ?? null);
            if (! $executor) {
                continue;
            }

            HandymanMaterialsEntry::updateOrCreate(
                [
                    'order_id' => $project->order_id,
                    'repair_project_id' => $project->id,
                    'executor_profile_id' => $executor->id,
                    'description' => $material['description'],
                ],
                [
                    'quantity' => 1,
                    'unit' => 'set',
                    'unit_price_minor' => $material['unit_price_minor'],
                    'total_price_minor' => $material['unit_price_minor'],
                    'purchased_at' => now()->subDay(),
                    'meta' => [
                        'source' => 'production_admin_backfill',
                        'catalog' => false,
                    ],
                ]
            );
        }
    }

    protected function seedClaims(array $projects, array $clients): void
    {
        $claimBlueprints = [
            [
                'title' => 'Arrival window missed',
                'project_title' => 'Washer Install and Leak Check',
                'client_email' => 'liv.andersen@demo.no',
                'status' => 'open',
                'severity' => 'medium',
                'type' => 'delay',
            ],
            [
                'title' => 'Finish details need correction',
                'project_title' => 'IKEA Wardrobe Assembly',
                'client_email' => 'arne.pedersen@demo.no',
                'status' => 'in_progress',
                'severity' => 'high',
                'type' => 'quality',
            ],
        ];

        foreach ($claimBlueprints as $blueprint) {
            $project = $projects[$blueprint['project_title']] ?? null;
            $client = $clients[$blueprint['client_email']] ?? null;

            if (! $project || ! $client) {
                continue;
            }

            Claim::updateOrCreate(
                [
                    'user_id' => $client->user_id,
                    'title' => $blueprint['title'],
                ],
                [
                    'order_id' => $project->order_id,
                    'repair_project_id' => $project->id,
                    'opened_by_user_id' => $client->user_id,
                    'assigned_to_user_id' => null,
                    'type' => $blueprint['type'],
                    'status' => $blueprint['status'],
                    'severity' => $blueprint['severity'],
                    'description' => $blueprint['title'].' for '.$project->title,
                    'opened_at' => now()->subHours(12),
                    'responded_at' => $blueprint['status'] === 'open' ? null : now()->subHours(4),
                    'sla_response_due_at' => now()->addHours(12),
                    'sla_resolution_due_at' => now()->addDays(3),
                    'sla_response_breached' => false,
                    'sla_resolution_breached' => false,
                    'meta' => ['source' => 'production_admin_backfill'],
                ]
            );
        }
    }

    protected function seedErrandOrderDetails(array $clients, array $executors): void
    {
        $blueprints = [
            [
                'title' => 'Pharmacy pickup for evening delivery',
                'client_email' => 'liv.andersen@demo.no',
                'executor_email' => 'marlene.errand@bikube.no',
                'category' => 'pharmacy',
                'from_address' => 'Vitus Apotek Narvik',
                'to_address' => 'Dronningens gate 26, Narvik',
                'duration' => 45,
                'urgent' => true,
            ],
            [
                'title' => 'Document handoff and signature collection',
                'client_email' => 'arne.pedersen@demo.no',
                'executor_email' => 'marlene.errand@bikube.no',
                'category' => 'document_service',
                'from_address' => 'Narvik sentrum',
                'to_address' => 'Ankenesveien 40, Ankenes',
                'duration' => 55,
                'urgent' => false,
            ],
        ];

        foreach ($blueprints as $index => $blueprint) {
            $client = $clients[$blueprint['client_email']] ?? null;
            $executor = $executors[$blueprint['executor_email']] ?? null;

            if (! $client) {
                continue;
            }

            $order = Order::firstOrCreate(
                ['order_number' => 'ERR-BF-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $client->user_id,
                    'service_type' => 'personal_task',
                    'assigned_to' => $executor?->user_id,
                    'status' => $blueprint['urgent'] ? 'assigned' : 'scheduled',
                    'priority' => $blueprint['urgent'] ? 'high' : 'normal',
                    'notes' => $blueprint['title'],
                    'scheduled_at' => now()->addHours(4 + $index),
                    'total_amount' => 149 + ($index * 20),
                    'currency' => 'NOK',
                    'payment_status' => 'paid',
                    'payment_flow' => PaymentFlow::AuthorizeCapture,
                    'estimated_total' => (149 + ($index * 20)) * 100,
                    'buffer_total' => (int) round((149 + ($index * 20)) * 120),
                    'metadata' => ['source' => 'production_admin_backfill'],
                ]
            );

            ErrandOrderDetails::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'category' => $blueprint['category'],
                ],
                [
                    'description' => $blueprint['title'],
                    'from_address' => $blueprint['from_address'],
                    'to_address' => $blueprint['to_address'],
                    'from_lat' => 68.4380,
                    'from_lng' => 17.4270,
                    'to_lat' => 68.4400 + ($index * 0.01),
                    'to_lng' => 17.4300 + ($index * 0.01),
                    'waypoints' => [],
                    'contacts' => [
                        'customer' => [
                            'name' => $client->full_name,
                            'phone' => $client->phone,
                        ],
                    ],
                    'desired_start_at' => now()->addHours(4 + $index),
                    'desired_finish_at' => now()->addHours(5 + $index),
                    'is_urgent' => $blueprint['urgent'],
                    'requires_signature' => $blueprint['category'] === 'document_service',
                    'requires_trusted_helper' => false,
                    'involves_documents' => $blueprint['category'] === 'document_service',
                    'complexity_level' => $blueprint['urgent'] ? 3 : 2,
                    'expected_duration_minutes' => $blueprint['duration'],
                    'material_advance_amount' => 0,
                    'base_fee' => 7900,
                    'distance_fee' => 2500,
                    'time_fee' => 4200,
                    'complexity_fee' => $blueprint['urgent'] ? 1500 : 900,
                    'trusted_helper_fee' => 0,
                    'urgency_fee' => $blueprint['urgent'] ? 1800 : 0,
                    'total_estimated_price' => $blueprint['urgent'] ? 16400 : 15500,
                    'executor_profile_id' => $executor?->id,
                    'meta' => ['source' => 'production_admin_backfill'],
                ]
            );
        }
    }

    protected function seedWorkSpecifications(array $projects, array $executors): void
    {
        $blueprints = [
            [
                'title' => 'Lighting safety checklist for washer install',
                'project_title' => 'Washer Install and Leak Check',
                'executor_email' => 'andreas.elec@bikube.no',
                'status' => 'approved',
                'priority' => 'high',
            ],
            [
                'title' => 'Wardrobe anchoring and finish acceptance sheet',
                'project_title' => 'IKEA Wardrobe Assembly',
                'executor_email' => 'dmytro.handyman@bikube.no',
                'status' => 'on_review',
                'priority' => 'normal',
            ],
        ];

        foreach ($blueprints as $index => $blueprint) {
            $project = $projects[$blueprint['project_title']] ?? null;
            $executor = $executors[$blueprint['executor_email']] ?? null;

            if (! $project) {
                continue;
            }

            WorkSpecification::updateOrCreate(
                ['title' => $blueprint['title']],
                [
                    'public_id' => 'WS-2026-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'description' => '<p>'.$blueprint['title'].' with clear acceptance criteria, safety checks and customer handoff notes.</p>',
                    'status' => $blueprint['status'],
                    'priority' => $blueprint['priority'],
                    'order_id' => $project->order_id,
                    'ticket_id' => null,
                    'responsible_id' => $executor?->user_id,
                    'creator_id' => $project->clientProfile?->user_id,
                    'worker_acknowledged_at' => $blueprint['status'] === 'approved' ? now()->subHours(2) : null,
                    'metadata' => [
                        'source' => 'production_admin_backfill',
                        'project' => $project->title,
                    ],
                ]
            );
        }
    }

    protected function seedWorkWarranties(array $projects): void
    {
        foreach ($projects as $project) {
            WorkWarranty::updateOrCreate(
                ['repair_project_id' => $project->id],
                [
                    'order_id' => $project->order_id,
                    'title' => 'Standard workmanship warranty',
                    'description' => 'Covers workmanship defects, adjustment visits and documentation handoff.',
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(6),
                    'status' => 'active',
                    'terms_url' => 'https://glfmat.example/warranty',
                ]
            );
        }
    }

    protected function seedVirtualOfficeAgents(): void
    {
        $categories = [
            'vo-operations' => Category::updateOrCreate(
                ['slug' => 'vo-operations'],
                [
                    'name' => 'Operations',
                    'description' => 'Backfilled operations agents for admin panel.',
                    'color' => '#E11D48',
                    'icon' => 'ops',
                    'sector_x_min' => 0,
                    'sector_x_max' => 300,
                    'sector_y_min' => 0,
                    'sector_y_max' => 220,
                ]
            ),
            'vo-field' => Category::updateOrCreate(
                ['slug' => 'vo-field'],
                [
                    'name' => 'Field Crew',
                    'description' => 'Backfilled field crew agents for admin panel.',
                    'color' => '#F59E0B',
                    'icon' => 'field',
                    'sector_x_min' => 301,
                    'sector_x_max' => 800,
                    'sector_y_min' => 0,
                    'sector_y_max' => 220,
                ]
            ),
        ];

        $zones = [
            'dispatch' => OfficeZone::updateOrCreate(
                ['slug' => 'dispatch'],
                [
                    'name' => 'Dispatch Desk',
                    'icon' => 'desk',
                    'color' => '#FCE7F3',
                    'x_min' => 40,
                    'x_max' => 240,
                    'y_min' => 60,
                    'y_max' => 220,
                    'capacity' => 8,
                    'amenities' => ['wallboard', 'sla-monitor', 'headsets'],
                ]
            ),
            'ready-line' => OfficeZone::updateOrCreate(
                ['slug' => 'ready-line'],
                [
                    'name' => 'Ready Line',
                    'icon' => 'line',
                    'color' => '#FEF3C7',
                    'x_min' => 280,
                    'x_max' => 720,
                    'y_min' => 60,
                    'y_max' => 220,
                    'capacity' => 16,
                    'amenities' => ['loadout', 'tablet', 'routing-board'],
                ]
            ),
        ];

        $agents = [
            [
                'slug' => 'dispatch-lead',
                'name' => 'Dispatch Lead',
                'description' => 'Coordinates urgent claims and field handoffs.',
                'category' => 'vo-operations',
                'zone' => 'dispatch',
                'x' => 120,
                'y' => 120,
                'emoji' => 'DL',
                'color' => '#E11D48',
            ],
            [
                'slug' => 'sla-monitor',
                'name' => 'SLA Monitor',
                'description' => 'Tracks response and resolution deadlines.',
                'category' => 'vo-operations',
                'zone' => 'dispatch',
                'x' => 190,
                'y' => 155,
                'emoji' => 'SM',
                'color' => '#BE123C',
            ],
            [
                'slug' => 'field-planner',
                'name' => 'Field Planner',
                'description' => 'Balances assignment loads across crews.',
                'category' => 'vo-field',
                'zone' => 'ready-line',
                'x' => 420,
                'y' => 110,
                'emoji' => 'FP',
                'color' => '#F59E0B',
            ],
            [
                'slug' => 'kitchen-crew',
                'name' => 'Kitchen Crew',
                'description' => 'Tracks appliance fit-out readiness.',
                'category' => 'vo-field',
                'zone' => 'ready-line',
                'x' => 560,
                'y' => 160,
                'emoji' => 'KC',
                'color' => '#D97706',
            ],
        ];

        foreach ($agents as $agent) {
            Agent::updateOrCreate(
                ['slug' => $agent['slug']],
                [
                    'name' => $agent['name'],
                    'description' => $agent['description'],
                    'category_id' => $categories[$agent['category']]->id,
                    'zone_id' => $zones[$agent['zone']]->id,
                    'x_position' => $agent['x'],
                    'y_position' => $agent['y'],
                    'avatar' => null,
                    'emoji' => $agent['emoji'],
                    'color' => $agent['color'],
                    'is_active' => true,
                    'source_file' => 'production_admin_backfill',
                    'config' => ['source' => 'production_admin_backfill'],
                ]
            );
        }
    }
}
