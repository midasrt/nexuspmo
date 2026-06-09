<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MainSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        // --- 0. STATUS DEFINITIONS ---
        $statusDefinitions = [
            ['status' => 'on-track', 'label' => 'ON TRACK', 'criteria' => 'Tracking to plan. No critical risks open. Velocity ≥ 90% of baseline.'],
            ['status' => 'at-risk', 'label' => 'AT RISK', 'criteria' => 'Identifiable threat to scope/time/cost. Mitigation in flight. Slip < 2 weeks.'],
            ['status' => 'blocked', 'label' => 'BLOCKED', 'criteria' => 'Progress halted pending external dependency, approval, or unresolved issue.'],
            ['status' => 'delayed', 'label' => 'DELAYED', 'criteria' => 'Confirmed slip ≥ 2 weeks. Re-baseline required and communicated.'],
            ['status' => 'backlog', 'label' => 'BACKLOG', 'criteria' => 'Approved but not yet started. Awaiting scheduled start date or resourcing.'],
        ];
        $db->table('status_definitions')->emptyTable();
        $db->table('status_definitions')->insertBatch($statusDefinitions);

        // --- 1. SQUADS ---
        $squads = [
            ['name' => 'Mapclub', 'mission' => 'Location-based loyalty for retail.', 'lead' => 'K. Tanaka'],
            ['name' => 'Loyalty', 'mission' => 'Points engine + redemption flows.', 'lead' => 'P. Schwartz'],
            ['name' => 'O2O', 'mission' => 'Online-to-offline conversion stack.', 'lead' => 'H. Larsen'],
            ['name' => 'Digital Flagship', 'mission' => 'Brand digital flagship properties.', 'lead' => 'M. Reyes'],
            ['name' => 'Custom Web', 'mission' => 'Bespoke client web builds.', 'lead' => 'K. Tanaka'],
            ['name' => 'MaaS', 'mission' => 'Mobility-as-a-Service platform.', 'lead' => 'A. Kumar'],
            ['name' => 'CRM', 'mission' => 'Unified CRM + segmentation.', 'lead' => 'S. Park'],
            ['name' => 'Speedwork', 'mission' => 'Rapid-delivery internal tools.', 'lead' => 'T. Adeyemi'],
            ['name' => 'Mekaniq', 'mission' => 'Workshop & service ops platform.', 'lead' => 'T. Adeyemi'],
            ['name' => 'Pandora (SBUX)', 'mission' => 'Coffee chain ordering & rewards.', 'lead' => 'R. Müller'],
        ];
        $db->table('squads')->emptyTable();
        $db->table('squads')->insertBatch($squads);

        // --- 2. RESOURCES ---
        $resources = [
            [
                'id' => 1,
                'name' => 'M. Reyes',
                'department' => 'Platform',
                'role' => 'BE',
                'utilization' => 92,
                'status' => 'employee',
                'email' => 'm.reyes@pmo.io',
                'location' => 'Jakarta',
                'skills' => 'Go,Kafka,Kubernetes',
                'manager' => 'D. Iversen'
            ],
            [
                'id' => 2,
                'name' => 'S. Park',
                'department' => 'Identity',
                'role' => 'BE',
                'utilization' => 88,
                'status' => 'employee',
                'email' => 's.park@pmo.io',
                'location' => 'Singapore',
                'skills' => 'OAuth,Java,Postgres',
                'manager' => 'D. Iversen'
            ],
            [
                'id' => 3,
                'name' => 'A. Kumar',
                'department' => 'Data',
                'role' => 'BE',
                'utilization' => 70,
                'status' => 'employee',
                'email' => 'a.kumar@pmo.io',
                'location' => 'Bengaluru',
                'skills' => 'Spark,Airflow,Python',
                'manager' => 'H. Larsen'
            ],
            [
                'id' => 4,
                'name' => 'K. Tanaka',
                'department' => 'Mobile',
                'role' => 'FE',
                'utilization' => 95,
                'status' => 'employee',
                'email' => 'k.tanaka@pmo.io',
                'location' => 'Tokyo',
                'skills' => 'Swift,Kotlin,React Native',
                'manager' => 'P. Schwartz'
            ],
            [
                'id' => 5,
                'name' => 'R. Müller',
                'department' => 'Finance Tech',
                'role' => 'BA',
                'utilization' => 60,
                'status' => 'employee',
                'email' => 'r.muller@pmo.io',
                'location' => 'Berlin',
                'skills' => 'Revenue Rec,SAP,Stripe',
                'manager' => 'H. Larsen'
            ],
            [
                'id' => 6,
                'name' => 'T. Adeyemi',
                'department' => 'SRE',
                'role' => 'BE',
                'utilization' => 35,
                'status' => 'employee',
                'email' => 't.adeyemi@pmo.io',
                'location' => 'Lagos',
                'skills' => 'Prometheus,Grafana,Terraform',
                'manager' => 'D. Iversen'
            ],
            [
                'id' => 7,
                'name' => 'P. Schwartz',
                'department' => 'Product',
                'role' => 'BA',
                'utilization' => 80,
                'status' => 'employee',
                'email' => 'p.schwartz@pmo.io',
                'location' => 'Tel Aviv',
                'skills' => 'Roadmapping,SQL,Figma',
                'manager' => 'C. Whitaker'
            ],
            [
                'id' => 8,
                'name' => 'L. Okafor',
                'department' => 'Security',
                'role' => 'BE',
                'utilization' => 50,
                'status' => 'employee',
                'email' => 'l.okafor@pmo.io',
                'location' => 'London',
                'skills' => 'AppSec,Threat Modeling',
                'manager' => 'C. Whitaker'
            ],
            [
                'id' => 9,
                'name' => 'J. Chen',
                'department' => 'Identity',
                'role' => 'FE',
                'utilization' => 78,
                'status' => 'outsource',
                'email' => 'j.chen@contractor.io',
                'location' => 'Taipei',
                'skills' => 'React,TypeScript,Tailwind',
                'manager' => 'S. Park'
            ],
            [
                'id' => 10,
                'name' => 'N. Volkov',
                'department' => 'Data',
                'role' => 'QA',
                'utilization' => 65,
                'status' => 'outsource',
                'email' => 'n.volkov@contractor.io',
                'location' => 'Warsaw',
                'skills' => 'Playwright,k6,PyTest',
                'manager' => 'A. Kumar'
            ],
            [
                'id' => 11,
                'name' => 'F. Costa',
                'department' => 'Mobile',
                'role' => 'QA',
                'utilization' => 90,
                'status' => 'employee',
                'email' => 'f.costa@pmo.io',
                'location' => 'Lisbon',
                'skills' => 'Appium,XCUITest,Espresso',
                'manager' => 'K. Tanaka'
            ],
            [
                'id' => 12,
                'name' => 'H. Larsen',
                'department' => 'Data',
                'role' => 'BA',
                'utilization' => 45,
                'status' => 'employee',
                'email' => 'h.larsen@pmo.io',
                'location' => 'Copenhagen',
                'skills' => 'Analytics,Stakeholder Mgmt',
                'manager' => 'C. Whitaker'
            ],
            [
                'id' => 13,
                'name' => 'Y. Demir',
                'department' => 'Platform',
                'role' => 'FE',
                'utilization' => 82,
                'status' => 'outsource',
                'email' => 'y.demir@contractor.io',
                'location' => 'Istanbul',
                'skills' => 'Next.js,Design Systems',
                'manager' => 'M. Reyes'
            ],
            [
                'id' => 14,
                'name' => 'G. Rossi',
                'department' => 'Finance Tech',
                'role' => 'QA',
                'utilization' => 55,
                'status' => 'employee',
                'email' => 'g.rossi@pmo.io',
                'location' => 'Milan',
                'skills' => 'TestRail,Postman,SQL',
                'manager' => 'R. Müller'
            ],
            [
                'id' => 15,
                'name' => 'B. N\'Diaye',
                'department' => 'SRE',
                'role' => 'FE',
                'utilization' => 25,
                'status' => 'outsource',
                'email' => 'b.ndiaye@contractor.io',
                'location' => 'Dakar',
                'skills' => 'Vue,D3,Dashboards',
                'manager' => 'T. Adeyemi'
            ]
        ];
        $db->table('resources')->emptyTable();
        $db->table('resources')->insertBatch($resources);

        // --- 3. SQUAD_MEMBERS ---
        $squadMembers = [
            // Mapclub (1)
            ['squad_id' => 1, 'resource_id' => 5], // R. Müller
            ['squad_id' => 1, 'resource_id' => 1], // M. Reyes
            ['squad_id' => 1, 'resource_id' => 4], // K. Tanaka
            ['squad_id' => 1, 'resource_id' => 10], // N. Volkov
            ['squad_id' => 1, 'resource_id' => 13], // Y. Demir
            // Loyalty (2)
            ['squad_id' => 2, 'resource_id' => 7], // P. Schwartz
            ['squad_id' => 2, 'resource_id' => 2], // S. Park
            ['squad_id' => 2, 'resource_id' => 9], // J. Chen
            ['squad_id' => 2, 'resource_id' => 11], // F. Costa
            // O2O (3)
            ['squad_id' => 3, 'resource_id' => 12], // H. Larsen
            ['squad_id' => 3, 'resource_id' => 3], // A. Kumar
            ['squad_id' => 3, 'resource_id' => 13], // Y. Demir
            ['squad_id' => 3, 'resource_id' => 14], // G. Rossi
            ['squad_id' => 3, 'resource_id' => 8], // L. Okafor
            // Digital Flagship (4)
            ['squad_id' => 4, 'resource_id' => 5], // R. Müller
            ['squad_id' => 4, 'resource_id' => 6], // T. Adeyemi
            ['squad_id' => 4, 'resource_id' => 15], // B. N'Diaye
            ['squad_id' => 4, 'resource_id' => 10], // N. Volkov
            ['squad_id' => 4, 'resource_id' => 1], // M. Reyes
            // Custom Web (5)
            ['squad_id' => 5, 'resource_id' => 7], // P. Schwartz
            ['squad_id' => 5, 'resource_id' => 8], // L. Okafor
            ['squad_id' => 5, 'resource_id' => 4], // K. Tanaka
            ['squad_id' => 5, 'resource_id' => 11], // F. Costa
            // MaaS (6)
            ['squad_id' => 6, 'resource_id' => 12], // H. Larsen
            ['squad_id' => 6, 'resource_id' => 1], // M. Reyes
            ['squad_id' => 6, 'resource_id' => 9], // J. Chen
            ['squad_id' => 6, 'resource_id' => 14], // G. Rossi
            ['squad_id' => 6, 'resource_id' => 3], // A. Kumar
            // CRM (7)
            ['squad_id' => 7, 'resource_id' => 7], // P. Schwartz
            ['squad_id' => 7, 'resource_id' => 2], // S. Park
            ['squad_id' => 7, 'resource_id' => 13], // Y. Demir
            ['squad_id' => 7, 'resource_id' => 10], // N. Volkov
            // Speedwork (8)
            ['squad_id' => 8, 'resource_id' => 5], // R. Müller
            ['squad_id' => 8, 'resource_id' => 3], // A. Kumar
            ['squad_id' => 8, 'resource_id' => 15], // B. N'Diaye
            ['squad_id' => 8, 'resource_id' => 11], // F. Costa
            ['squad_id' => 8, 'resource_id' => 6], // T. Adeyemi
            // Mekaniq (9)
            ['squad_id' => 9, 'resource_id' => 12], // H. Larsen
            ['squad_id' => 9, 'resource_id' => 6], // T. Adeyemi
            ['squad_id' => 9, 'resource_id' => 4], // K. Tanaka
            ['squad_id' => 9, 'resource_id' => 14], // G. Rossi
            // Pandora (SBUX) (10)
            ['squad_id' => 10, 'resource_id' => 5], // R. Müller
            ['squad_id' => 10, 'resource_id' => 8], // L. Okafor
            ['squad_id' => 10, 'resource_id' => 9], // J. Chen
            ['squad_id' => 10, 'resource_id' => 10], // N. Volkov
        ];
        $db->table('squad_members')->emptyTable();
        $db->table('squad_members')->insertBatch($squadMembers);

        // --- 4. PROJECTS ---
        $projects = [
            [
                'id' => 'atlas',
                'code' => 'PRJ-001',
                'name' => 'ATLAS // Core Platform Rebuild',
                'owner' => 'M. Reyes',
                'squad' => 'Digital Flagship',
                'status' => 'on-track',
                'health' => 'Green — velocity stable, no critical risks open.',
                'startDate' => '2026-01-15',
                'endDate' => '2026-11-30',
                'progress' => 32,
                'description' => 'Rebuild of the legacy core platform onto an event-driven service mesh. Cross-team initiative spanning Platform, Data, and Security.'
            ],
            [
                'id' => 'vega',
                'code' => 'PRJ-002',
                'name' => 'VEGA // Identity Service',
                'owner' => 'S. Park',
                'squad' => 'CRM',
                'status' => 'at-risk',
                'health' => 'Amber — staffing gap in IAM team, mitigations underway.',
                'startDate' => '2026-02-01',
                'endDate' => '2026-09-30',
                'progress' => 41,
                'description' => 'Unified identity, SSO, and entitlements platform across all internal tools.'
            ],
            [
                'id' => 'nova',
                'code' => 'PRJ-003',
                'name' => 'NOVA // Data Pipeline v2',
                'owner' => 'A. Kumar',
                'squad' => 'MaaS',
                'status' => 'blocked',
                'health' => 'Red — blocked on data residency legal review.',
                'startDate' => '2026-03-01',
                'endDate' => '2027-02-28',
                'progress' => 18,
                'description' => 'Streaming pipeline with multi-region replication and PII tokenization.'
            ],
            [
                'id' => 'orion',
                'code' => 'PRJ-004',
                'name' => 'ORION // Mobile App Refresh',
                'owner' => 'K. Tanaka',
                'squad' => 'Mapclub',
                'status' => 'delayed',
                'health' => 'Red — slipped Q3 ship date by 6 weeks.',
                'startDate' => '2026-01-05',
                'endDate' => '2026-10-15',
                'progress' => 55,
                'description' => 'Full redesign of consumer mobile app with new design system and offline-first sync.'
            ],
            [
                'id' => 'lyra',
                'code' => 'PRJ-005',
                'name' => 'LYRA // Billing Modernization',
                'owner' => 'R. Müller',
                'squad' => 'Loyalty',
                'status' => 'on-track',
                'health' => 'Green — invoicing migration on plan.',
                'startDate' => '2026-04-01',
                'endDate' => '2027-03-31',
                'progress' => 22,
                'description' => 'Migration of invoicing, dunning, and revenue recognition onto a new billing core.'
            ],
            [
                'id' => 'halo',
                'code' => 'PRJ-006',
                'name' => 'HALO // Observability Stack',
                'owner' => 'T. Adeyemi',
                'squad' => 'Speedwork',
                'status' => 'backlog',
                'health' => 'Not started — scheduled for Q3.',
                'startDate' => '2026-08-01',
                'endDate' => '2027-04-30',
                'progress' => 0,
                'description' => 'Unified logs, metrics, and traces across all production services with budget alerting.'
            ],
            [
                'id' => 'echo',
                'code' => 'PRJ-007',
                'name' => 'ECHO // Customer 360',
                'owner' => 'P. Schwartz',
                'squad' => 'O2O',
                'status' => 'at-risk',
                'health' => 'Amber — dependency on NOVA pipeline.',
                'startDate' => '2026-05-01',
                'endDate' => '2027-01-31',
                'progress' => 12,
                'description' => 'Single customer view aggregating product, billing, and support data.'
            ]
        ];
        $db->table('projects')->emptyTable();
        $db->table('projects')->insertBatch($projects);

        // --- 5. RESOURCE_PROJECTS (Mappings based on resources-data.ts) ---
        $resourceProjects = [
            ['resource_id' => 1, 'project_id' => 'atlas'], // M. Reyes -> ATLAS
            ['resource_id' => 2, 'project_id' => 'vega'],  // S. Park -> VEGA
            ['resource_id' => 3, 'project_id' => 'nova'],  // A. Kumar -> NOVA
            ['resource_id' => 4, 'project_id' => 'orion'], // K. Tanaka -> ORION
            ['resource_id' => 5, 'project_id' => 'lyra'],  // R. Müller -> LYRA
            ['resource_id' => 6, 'project_id' => 'halo'],  // T. Adeyemi -> HALO
            ['resource_id' => 7, 'project_id' => 'echo'],  // P. Schwartz -> ECHO
            ['resource_id' => 8, 'project_id' => 'atlas'], // L. Okafor -> ATLAS
            ['resource_id' => 9, 'project_id' => 'vega'],  // J. Chen -> VEGA
            ['resource_id' => 10, 'project_id' => 'nova'], // N. Volkov -> NOVA
            ['resource_id' => 11, 'project_id' => 'orion'],// F. Costa -> ORION
            ['resource_id' => 12, 'project_id' => 'echo'], // H. Larsen -> ECHO
            ['resource_id' => 12, 'project_id' => 'nova'], // H. Larsen -> NOVA
            ['resource_id' => 13, 'project_id' => 'atlas'],// Y. Demir -> ATLAS
            ['resource_id' => 14, 'project_id' => 'lyra'], // G. Rossi -> LYRA
            ['resource_id' => 15, 'project_id' => 'halo'], // B. N'Diaye -> HALO
        ];
        $db->table('resource_projects')->emptyTable();
        $db->table('resource_projects')->insertBatch($resourceProjects);

        // --- 6. PROJECT_PHASES ---
        $phases = [
            // ATLAS (PRJ-001)
            ['project_id' => 'atlas', 'name' => 'Discovery', 'start' => '2026-01-15', 'end' => '2026-03-15', 'status' => 'on-track'],
            ['project_id' => 'atlas', 'name' => 'Architecture', 'start' => '2026-03-15', 'end' => '2026-05-30', 'status' => 'on-track'],
            ['project_id' => 'atlas', 'name' => 'Build Phase 1', 'start' => '2026-06-01', 'end' => '2026-08-31', 'status' => 'at-risk'],
            ['project_id' => 'atlas', 'name' => 'Build Phase 2', 'start' => '2026-09-01', 'end' => '2026-10-31', 'status' => 'backlog'],
            ['project_id' => 'atlas', 'name' => 'Cutover', 'start' => '2026-11-01', 'end' => '2026-11-30', 'status' => 'backlog'],
            
            // VEGA (PRJ-002)
            ['project_id' => 'vega', 'name' => 'Requirements', 'start' => '2026-02-01', 'end' => '2026-03-31', 'status' => 'on-track'],
            ['project_id' => 'vega', 'name' => 'Design', 'start' => '2026-04-01', 'end' => '2026-05-31', 'status' => 'on-track'],
            ['project_id' => 'vega', 'name' => 'Build', 'start' => '2026-06-01', 'end' => '2026-08-15', 'status' => 'at-risk'],
            ['project_id' => 'vega', 'name' => 'Rollout', 'start' => '2026-08-15', 'end' => '2026-09-30', 'status' => 'backlog'],

            // NOVA (PRJ-003)
            ['project_id' => 'nova', 'name' => 'Legal & Compliance', 'start' => '2026-03-01', 'end' => '2026-06-30', 'status' => 'blocked'],
            ['project_id' => 'nova', 'name' => 'Infra Buildout', 'start' => '2026-07-01', 'end' => '2026-10-31', 'status' => 'backlog'],
            ['project_id' => 'nova', 'name' => 'Pipeline Build', 'start' => '2026-11-01', 'end' => '2027-01-31', 'status' => 'backlog'],
            ['project_id' => 'nova', 'name' => 'Launch', 'start' => '2027-02-01', 'end' => '2027-02-28', 'status' => 'backlog'],

            // ORION (PRJ-004)
            ['project_id' => 'orion', 'name' => 'Research', 'start' => '2026-01-05', 'end' => '2026-02-28', 'status' => 'on-track'],
            ['project_id' => 'orion', 'name' => 'Design System', 'start' => '2026-03-01', 'end' => '2026-05-31', 'status' => 'delayed'],
            ['project_id' => 'orion', 'name' => 'Build', 'start' => '2026-06-01', 'end' => '2026-09-15', 'status' => 'delayed'],
            ['project_id' => 'orion', 'name' => 'Beta + Launch', 'start' => '2026-09-15', 'end' => '2026-10-15', 'status' => 'backlog'],

            // LYRA (PRJ-005)
            ['project_id' => 'lyra', 'name' => 'Vendor Selection', 'start' => '2026-04-01', 'end' => '2026-06-30', 'status' => 'on-track'],
            ['project_id' => 'lyra', 'name' => 'Integration Build', 'start' => '2026-07-01', 'end' => '2026-12-31', 'status' => 'on-track'],
            ['project_id' => 'lyra', 'name' => 'Parallel Run', 'start' => '2027-01-01', 'end' => '2027-02-28', 'status' => 'backlog'],
            ['project_id' => 'lyra', 'name' => 'Cutover', 'start' => '2027-03-01', 'end' => '2027-03-31', 'status' => 'backlog'],

            // HALO (PRJ-006)
            ['project_id' => 'halo', 'name' => 'Discovery', 'start' => '2026-08-01', 'end' => '2026-09-30', 'status' => 'backlog'],
            ['project_id' => 'halo', 'name' => 'Build', 'start' => '2026-10-01', 'end' => '2027-02-28', 'status' => 'backlog'],
            ['project_id' => 'halo', 'name' => 'Rollout', 'start' => '2027-03-01', 'end' => '2027-04-30', 'status' => 'backlog'],

            // ECHO (PRJ-007)
            ['project_id' => 'echo', 'name' => 'Data Modeling', 'start' => '2026-05-01', 'end' => '2026-07-31', 'status' => 'on-track'],
            ['project_id' => 'echo', 'name' => 'Ingestion', 'start' => '2026-08-01', 'end' => '2026-11-30', 'status' => 'at-risk'],
            ['project_id' => 'echo', 'name' => 'App Build', 'start' => '2026-12-01', 'end' => '2027-01-31', 'status' => 'backlog'],
        ];
        $db->table('project_phases')->emptyTable();
        $db->table('project_phases')->insertBatch($phases);

        // --- 7. PROJECT_DEPENDENCIES ---
        $dependencies = [
            ['project_id' => 'atlas', 'dep_project_id' => 'vega', 'dep_project_name' => 'VEGA // Identity Service', 'type' => 'depends-on', 'status' => 'at-risk'],
            ['project_id' => 'atlas', 'dep_project_id' => 'nova', 'dep_project_name' => 'NOVA // Data Pipeline', 'type' => 'blocks', 'status' => 'on-track'],
            ['project_id' => 'vega', 'dep_project_id' => 'atlas', 'dep_project_name' => 'ATLAS // Core Platform', 'type' => 'blocks', 'status' => 'on-track'],
            ['project_id' => 'nova', 'dep_project_id' => 'atlas', 'dep_project_name' => 'ATLAS // Core Platform', 'type' => 'depends-on', 'status' => 'on-track'],
            ['project_id' => 'halo', 'dep_project_id' => 'atlas', 'dep_project_name' => 'ATLAS // Core Platform', 'type' => 'depends-on', 'status' => 'on-track'],
            ['project_id' => 'echo', 'dep_project_id' => 'nova', 'dep_project_name' => 'NOVA // Data Pipeline', 'type' => 'depends-on', 'status' => 'blocked'],
        ];
        $db->table('project_dependencies')->emptyTable();
        $db->table('project_dependencies')->insertBatch($dependencies);

        // --- 8. PROJECT_ACTION_ITEMS ---
        $actionItems = [
            // ATLAS
            ['id' => 'a1', 'project_id' => 'atlas', 'title' => 'Finalize event schema registry', 'owner' => 'S. Park', 'due' => '2026-06-12', 'done' => 0],
            ['id' => 'a2', 'project_id' => 'atlas', 'title' => 'Approve vendor for service mesh', 'owner' => 'M. Reyes', 'due' => '2026-05-30', 'done' => 1],
            ['id' => 'a3', 'project_id' => 'atlas', 'title' => 'Security review of cutover plan', 'owner' => 'L. Okafor', 'due' => '2026-10-10', 'done' => 0],
            // VEGA
            ['id' => 'v1', 'project_id' => 'vega', 'title' => 'Hire 2 senior IAM engineers', 'owner' => 'S. Park', 'due' => '2026-07-01', 'done' => 0],
            ['id' => 'v2', 'project_id' => 'vega', 'title' => 'Define entitlements model', 'owner' => 'J. Chen', 'due' => '2026-05-20', 'done' => 1],
            // NOVA
            ['id' => 'n1', 'project_id' => 'nova', 'title' => 'Escalate legal review to GC', 'owner' => 'A. Kumar', 'due' => '2026-06-01', 'done' => 0],
            // ORION
            ['id' => 'o1', 'project_id' => 'orion', 'title' => 'Recover design system timeline', 'owner' => 'K. Tanaka', 'due' => '2026-06-30', 'done' => 0],
            ['id' => 'o2', 'project_id' => 'orion', 'title' => 'Re-baseline ship date with leadership', 'owner' => 'K. Tanaka', 'due' => '2026-06-10', 'done' => 1],
            // LYRA
            ['id' => 'l1', 'project_id' => 'lyra', 'title' => 'Sign vendor MSA', 'owner' => 'R. Müller', 'due' => '2026-06-25', 'done' => 0],
            // ECHO
            ['id' => 'e1', 'project_id' => 'echo', 'title' => 'Define fallback ingestion path if NOVA slips', 'owner' => 'P. Schwartz', 'due' => '2026-07-15', 'done' => 0],
        ];
        $db->table('project_action_items')->emptyTable();
        $db->table('project_action_items')->insertBatch($actionItems);

        // --- 9. PROJECT_STATUS_HISTORY ---
        $statusHistory = [
            ['project_id' => 'atlas', 'date' => '2026-02-01', 'status' => 'on-track', 'note' => 'Kickoff complete.'],
            ['project_id' => 'atlas', 'date' => '2026-04-12', 'status' => 'at-risk', 'note' => 'Vendor delay on mesh decision.'],
            ['project_id' => 'atlas', 'date' => '2026-05-30', 'status' => 'on-track', 'note' => 'Vendor signed; back on plan.'],
            
            ['project_id' => 'vega', 'date' => '2026-02-15', 'status' => 'on-track', 'note' => 'Project initiated.'],
            ['project_id' => 'vega', 'date' => '2026-05-01', 'status' => 'at-risk', 'note' => 'Two senior engineers resigned.'],

            ['project_id' => 'nova', 'date' => '2026-03-10', 'status' => 'on-track', 'note' => 'Project started.'],
            ['project_id' => 'nova', 'date' => '2026-04-22', 'status' => 'at-risk', 'note' => 'Legal flagged data residency.'],
            ['project_id' => 'nova', 'date' => '2026-05-15', 'status' => 'blocked', 'note' => 'Blocked pending GC review.'],

            ['project_id' => 'orion', 'date' => '2026-02-01', 'status' => 'on-track', 'note' => 'Research wrapped.'],
            ['project_id' => 'orion', 'date' => '2026-04-15', 'status' => 'at-risk', 'note' => 'Design system behind.'],
            ['project_id' => 'orion', 'date' => '2026-05-22', 'status' => 'delayed', 'note' => 'Re-baselined to October.'],

            ['project_id' => 'lyra', 'date' => '2026-04-10', 'status' => 'on-track', 'note' => 'Kickoff.'],

            ['project_id' => 'halo', 'date' => '2026-05-01', 'status' => 'backlog', 'note' => 'Approved for Q3 start.'],

            ['project_id' => 'echo', 'date' => '2026-05-10', 'status' => 'on-track', 'note' => 'Started.'],
            ['project_id' => 'echo', 'date' => '2026-05-20', 'status' => 'at-risk', 'note' => 'NOVA blocked — upstream risk.'],
        ];
        $db->table('project_status_history')->emptyTable();
        $db->table('project_status_history')->insertBatch($statusHistory);

        // --- 10. PROJECT_ESCALATIONS ---
        $escalations = [
            ['project_id' => 'atlas', 'date' => '2026-04-20', 'level' => 'L2', 'note' => 'Vendor procurement delay.', 'to_recipient' => 'CTO Office'],
            ['project_id' => 'vega', 'date' => '2026-05-05', 'level' => 'L2', 'note' => 'Staffing gap — request reallocation.', 'to_recipient' => 'VP Engineering'],
            ['project_id' => 'nova', 'date' => '2026-05-15', 'level' => 'L3', 'note' => 'Blocking issue, request exec sponsor.', 'to_recipient' => 'Chief Legal Officer'],
            ['project_id' => 'echo', 'date' => '2026-05-22', 'level' => 'L1', 'note' => 'Tracking NOVA blockage impact.', 'to_recipient' => 'Project Office'],
        ];
        $db->table('project_escalations')->emptyTable();
        $db->table('project_escalations')->insertBatch($escalations);

        // --- 11. PROJECT_RISKS ---
        $risks = [
            ['id' => 'r1', 'project_id' => 'atlas', 'title' => 'Service mesh learning curve', 'severity' => 'med', 'type' => 'risk', 'mitigation' => 'External SME engaged for 8 weeks.', 'owner' => 'M. Reyes'],
            ['id' => 'i1', 'project_id' => 'atlas', 'title' => 'Schema registry tooling unstable', 'severity' => 'high', 'type' => 'issue', 'mitigation' => 'Pinned to v2.1; eval alternative.', 'owner' => 'S. Park'],
            
            ['id' => 'vr1', 'project_id' => 'vega', 'title' => 'Senior IAM attrition', 'severity' => 'high', 'type' => 'issue', 'mitigation' => 'Open reqs + contractor backfill.', 'owner' => 'S. Park'],
            ['id' => 'vr2', 'project_id' => 'vega', 'title' => 'SSO migration window', 'severity' => 'med', 'type' => 'risk', 'mitigation' => 'Phased rollout per BU.', 'owner' => 'J. Chen'],
            
            ['id' => 'ni1', 'project_id' => 'nova', 'title' => 'EU residency unresolved', 'severity' => 'high', 'type' => 'issue', 'mitigation' => 'GC engaged; awaiting opinion.', 'owner' => 'A. Kumar'],
            
            ['id' => 'oi1', 'project_id' => 'orion', 'title' => 'Design system slip cascading', 'severity' => 'high', 'type' => 'issue', 'mitigation' => 'Cut scope on tertiary screens.', 'owner' => 'K. Tanaka'],
            ['id' => 'or1', 'project_id' => 'orion', 'title' => 'Offline sync edge cases', 'severity' => 'med', 'type' => 'risk', 'mitigation' => 'Spike + 2-week buffer added.', 'owner' => 'F. Costa'],
            
            ['id' => 'lr1', 'project_id' => 'lyra', 'title' => 'Revenue rec mapping ambiguity', 'severity' => 'med', 'type' => 'risk', 'mitigation' => 'Finance workshop scheduled.', 'owner' => 'R. Müller'],
            
            ['id' => 'hr1', 'project_id' => 'halo', 'title' => 'Tool sprawl across BUs', 'severity' => 'low', 'type' => 'risk', 'mitigation' => 'Standardize on OTel.', 'owner' => 'T. Adeyemi'],
            
            ['id' => 'er1', 'project_id' => 'echo', 'title' => 'Upstream NOVA blockage', 'severity' => 'high', 'type' => 'risk', 'mitigation' => 'Defined fallback ingestion path.', 'owner' => 'P. Schwartz'],
        ];
        $db->table('project_risks')->emptyTable();
        $db->table('project_risks')->insertBatch($risks);

        // --- 12. PROJECT_DOCUMENTS ---
        $documents = [
            ['id' => 'd1', 'project_id' => 'atlas', 'name' => 'Charter_v3.pdf', 'size' => 184320, 'uploaded_at' => '2026-02-04', 'file_path' => 'uploads/Charter_v3.pdf'],
            ['id' => 'd2', 'project_id' => 'atlas', 'name' => 'Architecture-Decision-Record.docx', 'size' => 56210, 'uploaded_at' => '2026-03-22', 'file_path' => 'uploads/Architecture-Decision-Record.docx'],
        ];
        $db->table('project_documents')->emptyTable();
        $db->table('project_documents')->insertBatch($documents);
    }
}
