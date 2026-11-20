<?php

namespace Database\Seeders;

use App\Actions\Access\AssignRole;
use App\Enums\ClientConstitution;
use App\Enums\EnquirySource;
use App\Enums\EnquiryStatus;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Enums\Portal;
use App\Enums\QuotationLineType;
use App\Enums\QuotationStatus;
use App\Enums\RecordStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\FeeSchedule;
use App\Models\FollowUp;
use App\Models\IndustryUnit;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\QuotationRevision;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Numbers\DocumentNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleOfficeSeeder extends Seeder
{
    private const MARKER = 'Sahyadri Chemicals Private Limited';

    public function run(): void
    {
        $actor = User::query()->where('portal', Portal::Admin)->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        $staff = $this->staff($actor);

        if (Client::query()->where('legal_name', self::MARKER)->exists()) {
            $this->command?->info('Sample office records are already present.');

            return;
        }

        DB::transaction(function () use ($actor, $staff): void {
            $numbers = app(DocumentNumber::class);
            $year = 2026;

            $services = $this->services($actor);
            $clients = $this->clients($actor);
            $units = $this->units($clients, $actor);

            $enquiries = [
                $this->enquiry($numbers, $year, $actor, $staff['executive'] ?? null, [
                    'contact_name' => 'Anil Pawar',
                    'mobile' => '9822011101',
                    'email' => 'anil.pawar@example.com',
                    'source' => EnquirySource::Phone,
                    'status' => EnquiryStatus::New,
                    'enquiry_date' => '2026-11-18',
                    'service_id' => $services['cte']->id,
                    'requirement' => 'New chemical unit near Chakan needs consent to establish before construction starts.',
                ]),
                $this->enquiry($numbers, $year, $actor, $staff['consultant'] ?? null, [
                    'client_id' => $clients['sahyadri']->id,
                    'industry_unit_id' => $units['sahyadri-plant']->id,
                    'contact_name' => 'Meera Joshi',
                    'mobile' => '9822011102',
                    'email' => 'meera.joshi@sahyadri.example',
                    'source' => EnquirySource::WhatsApp,
                    'status' => EnquiryStatus::FollowUp,
                    'enquiry_date' => '2026-11-12',
                    'service_id' => $services['cto']->id,
                    'requirement' => 'Consent to operate renewal for the Pimpri solvent plant. Current consent expires in January.',
                    'internal_notes' => 'Ask for the latest stack-monitoring report before quoting.',
                ]),
                $this->enquiry($numbers, $year, $actor, $staff['manager'] ?? null, [
                    'client_id' => $clients['godavari']->id,
                    'industry_unit_id' => $units['godavari-mill']->id,
                    'contact_name' => 'Ramesh Kulkarni',
                    'mobile' => '9822011103',
                    'email' => 'ramesh@godavari.example',
                    'source' => EnquirySource::Referral,
                    'status' => EnquiryStatus::Contacted,
                    'enquiry_date' => '2026-11-10',
                    'service_id' => $services['audit']->id,
                    'requirement' => 'Annual environmental statement and a site visit before the sugar season.',
                ]),
                $this->enquiry($numbers, $year, $actor, $staff['executive'] ?? null, [
                    'contact_name' => 'Sneha Patil',
                    'mobile' => '9822011104',
                    'source' => EnquirySource::WalkIn,
                    'status' => EnquiryStatus::OnHold,
                    'enquiry_date' => '2026-11-08',
                    'requirement' => 'Food-processing unit in Nashik asked about consent to establish. The promoter is waiting for land documents.',
                ]),
                $this->enquiry($numbers, $year, $actor, $staff['consultant'] ?? null, [
                    'client_id' => $clients['deccan']->id,
                    'industry_unit_id' => $units['deccan-works']->id,
                    'contact_name' => 'Imran Shaikh',
                    'mobile' => '9822011105',
                    'email' => 'imran@deccan.example',
                    'source' => EnquirySource::Website,
                    'status' => EnquiryStatus::Lost,
                    'enquiry_date' => '2026-10-28',
                    'service_id' => $services['hazard']->id,
                    'requirement' => 'Hazardous-waste authorisation for the engineering works. The client appointed another consultant.',
                ]),
            ];

            $this->followUp($enquiries[1], $staff['consultant'] ?? $actor, [
                'type' => FollowUpType::Call,
                'scheduled_at' => '2026-11-14 11:00:00',
                'status' => FollowUpStatus::Completed,
                'completed_at' => '2026-11-14 11:20:00',
                'notes' => 'Spoke with Meera. She will send the previous consent and the latest analysis reports.',
            ]);
            $this->followUp($enquiries[1], $staff['consultant'] ?? $actor, [
                'type' => FollowUpType::WhatsApp,
                'scheduled_at' => now()->subDay()->setTime(10, 30)->format('Y-m-d H:i:s'),
                'reminder_at' => now()->subDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'status' => FollowUpStatus::Pending,
                'notes' => 'Check whether the stack-monitoring report has arrived.',
            ]);
            $this->followUp($enquiries[2], $staff['manager'] ?? $actor, [
                'type' => FollowUpType::Meeting,
                'scheduled_at' => now()->addDays(2)->setTime(15, 0)->format('Y-m-d H:i:s'),
                'reminder_at' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'status' => FollowUpStatus::Pending,
                'notes' => 'Visit the mill and confirm the effluent-treatment capacity.',
            ]);
            $this->followUp($clients['sahyadri'], $staff['executive'] ?? $actor, [
                'type' => FollowUpType::Email,
                'scheduled_at' => now()->addDays(3)->setTime(11, 0)->format('Y-m-d H:i:s'),
                'status' => FollowUpStatus::Pending,
                'notes' => 'Send the document checklist for the consent renewal.',
            ]);

            $enquiries[1]->forceFill([
                'next_follow_up_on' => now()->subDay()->toDateString(),
            ])->save();
            $enquiries[2]->forceFill([
                'next_follow_up_on' => now()->addDays(2)->toDateString(),
            ])->save();

            $converted = $this->enquiry($numbers, $year, $actor, $staff['consultant'] ?? null, [
                'client_id' => $clients['western']->id,
                'industry_unit_id' => $units['western-pharma']->id,
                'contact_name' => 'Dr Kavita Deshmukh',
                'mobile' => '9822011106',
                'email' => 'kavita@westernpharma.example',
                'source' => EnquirySource::Phone,
                'status' => EnquiryStatus::Converted,
                'enquiry_date' => '2026-11-04',
                'service_id' => $services['cte']->id,
                'requirement' => 'Consent to establish for a new pharmaceutical formulation block at Chinchwad.',
            ]);

            $workOrder = $this->workOrder($numbers, $year, $actor, $converted, $clients['western'], $units['western-pharma'], $services['cte'], [
                'status' => WorkOrderStatus::InProgress,
                'work_description' => 'Prepare and file consent to establish for the formulation block.',
                'start_date' => '2026-11-06',
                'target_completion_date' => '2026-12-20',
            ]);

            $this->quotation($numbers, $year, $actor, $workOrder, $services['cte'], $services['cte']->feeSchedules()->first(), QuotationStatus::Sent);

            $second = $this->enquiry($numbers, $year, $actor, $staff['manager'] ?? null, [
                'client_id' => $clients['sahyadri']->id,
                'industry_unit_id' => $units['sahyadri-lab']->id,
                'contact_name' => 'Meera Joshi',
                'mobile' => '9822011102',
                'email' => 'meera.joshi@sahyadri.example',
                'source' => EnquirySource::Website,
                'status' => EnquiryStatus::Converted,
                'enquiry_date' => '2026-11-15',
                'service_id' => $services['audit']->id,
                'requirement' => 'Environmental audit of the quality-control laboratory.',
            ]);

            $auditOrder = $this->workOrder($numbers, $year, $actor, $second, $clients['sahyadri'], $units['sahyadri-lab'], $services['audit'], [
                'status' => WorkOrderStatus::Active,
                'work_description' => 'Carry out the laboratory environmental audit and issue the report.',
                'start_date' => '2026-11-17',
                'target_completion_date' => '2026-12-05',
            ]);

            $this->quotation($numbers, $year, $actor, $auditOrder, $services['audit'], $services['audit']->feeSchedules()->first(), QuotationStatus::Draft);
        });

        $this->command?->info('Sample clients, services, enquiries, work orders, follow-ups and quotations are ready.');
    }

    /**
     * @return array<string, User>
     */
    private function staff(?User $actor): array
    {
        $people = [
            'manager' => ['Asha Deshpande', 'manager@paryavaranmitra.test', 'office-manager'],
            'consultant' => ['Rahul Shinde', 'consultant@paryavaranmitra.test', 'consultant'],
            'executive' => ['Neha Bhosale', 'executive@paryavaranmitra.test', 'office-executive'],
        ];

        $users = [];

        foreach ($people as $key => [$name, $email, $roleSlug]) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if ($role === null) {
                continue;
            }

            $user = User::query()->firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->password = 'Office#Sample#2026';
            }

            $user->fill([
                'name' => $name,
                'portal' => Portal::Staff,
                'locale' => $user->locale ?? 'en',
                'is_active' => true,
            ]);
            $user->save();
            $user->load('roles');

            if (! $user->roles->contains('id', $role->id)) {
                app(AssignRole::class)->execute($user, $role, $actor);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * @return array<string, Service>
     */
    private function services(?User $actor): array
    {
        $rows = [
            'cte' => ['CTE', 'Consent to Establish', 'Consent', 'Prepare and file consent to establish with the pollution control board.', 45, ['chemical', 'pharmaceutical'], '25000.00', '40000.00'],
            'cto' => ['CTO', 'Consent to Operate', 'Consent', 'Renew or obtain consent to operate, including the supporting compliance papers.', 30, ['chemical', 'pharmaceutical', 'engineering'], '20000.00', '35000.00'],
            'audit' => ['ENV-AUDIT', 'Environmental audit', 'Compliance', 'Site visit, document review and the environmental audit report.', 20, ['chemical', 'sugar', 'pharmaceutical', 'food_processing'], '0.00', '30000.00'],
            'hazard' => ['HW-AUTH', 'Hazardous waste authorisation', 'Authorisation', 'Application and follow-up for hazardous-waste authorisation.', 40, ['chemical', 'engineering', 'metal'], '15000.00', '28000.00'],
        ];

        $services = [];

        foreach ($rows as $key => [$code, $name, $category, $description, $days, $industries, $government, $consultancy]) {
            $service = Service::query()->where('code', $code)->first() ?? new Service;

            if (! $service->exists) {
                $service->forceFill([
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'expected_processing_days' => $days,
                    'status' => RecordStatus::Active,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ])->save();
            }

            foreach ($industries as $industry) {
                $service->industries()->firstOrCreate(['industry_category' => $industry]);
            }

            if (! $service->feeSchedules()->exists()) {
                $fee = new FeeSchedule;
                $fee->service()->associate($service);
                $fee->forceFill([
                    'effective_from' => '2026-01-01',
                    'government_fee' => $government,
                    'consultancy_fee' => $consultancy,
                    'status' => RecordStatus::Active,
                    'created_by' => $actor?->id,
                    'updated_by' => $actor?->id,
                ])->save();
            }

            $services[$key] = $service;
        }

        return $services;
    }

    /**
     * @return array<string, Client>
     */
    private function clients(?User $actor): array
    {
        $rows = [
            'sahyadri' => [self::MARKER, 'Sahyadri Chemicals', ClientConstitution::PrivateLimited, 'Meera Joshi', 'meera.joshi@sahyadri.example', '02040001101', 'Pimpri', 'Pune', '411018'],
            'godavari' => ['Godavari Sugars Limited', 'Godavari Sugars', ClientConstitution::PublicLimited, 'Ramesh Kulkarni', 'ramesh@godavari.example', '02040001102', 'Baramati', 'Pune', '413102'],
            'deccan' => ['Deccan Engineering Works', null, ClientConstitution::Partnership, 'Imran Shaikh', 'imran@deccan.example', '02040001103', 'Nashik', 'Nashik', '422001'],
            'western' => ['Western Pharma Laboratories Pvt Ltd', 'Western Pharma', ClientConstitution::PrivateLimited, 'Dr Kavita Deshmukh', 'kavita@westernpharma.example', '02040001104', 'Chinchwad', 'Pune', '411019'],
        ];

        $clients = [];

        foreach ($rows as $key => [$legal, $trade, $constitution, $contact, $email, $phone, $city, $district, $pincode]) {
            $client = new Client;
            $client->forceFill([
                'legal_name' => $legal,
                'trade_name' => $trade,
                'constitution' => $constitution,
                'status' => RecordStatus::Active,
                'email' => $email,
                'phone' => $phone,
                'contact_name' => $contact,
                'address_line' => $city.' industrial area',
                'city' => $city,
                'district' => $district,
                'state' => 'Maharashtra',
                'pincode' => $pincode,
                'created_by' => $actor?->id,
            ])->save();

            $clients[$key] = $client;
        }

        return $clients;
    }

    /**
     * @param  array<string, Client>  $clients
     * @return array<string, IndustryUnit>
     */
    private function units(array $clients, ?User $actor): array
    {
        $rows = [
            'sahyadri-plant' => [$clients['sahyadri'], 'Solvent plant', 'chemical', 'Solvent manufacturing', 'Pimpri', 'Haveli', 'Pune', '411018'],
            'sahyadri-lab' => [$clients['sahyadri'], 'Quality-control laboratory', 'chemical', 'Laboratory testing', 'Bhosari', 'Haveli', 'Pune', '411026'],
            'godavari-mill' => [$clients['godavari'], 'Sugar mill', 'sugar', 'Sugar manufacturing', 'Baramati', 'Baramati', 'Pune', '413102'],
            'deccan-works' => [$clients['deccan'], 'Machine shop', 'engineering', 'Machining and fabrication', 'Satpur', 'Nashik', 'Nashik', '422007'],
            'western-pharma' => [$clients['western'], 'Formulation block', 'pharmaceutical', 'Tablet formulation', 'Chinchwad', 'Haveli', 'Pune', '411019'],
        ];

        $units = [];

        foreach ($rows as $key => [$client, $name, $category, $activity, $village, $taluka, $district, $pincode]) {
            $unit = new IndustryUnit;
            $unit->client()->associate($client);
            $unit->forceFill([
                'name' => $name,
                'status' => RecordStatus::Active,
                'industry_category' => $category,
                'activity' => $activity,
                'site_address_line' => $name.', '.$village,
                'site_village' => $village,
                'site_taluka' => $taluka,
                'site_district' => $district,
                'site_state' => 'Maharashtra',
                'site_pincode' => $pincode,
                'site_contact_name' => $client->contact_name,
                'site_contact_phone' => $client->phone,
                'created_by' => $actor?->id,
            ])->save();

            $units[$key] = $unit;
        }

        return $units;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function enquiry(DocumentNumber $numbers, int $year, ?User $actor, ?User $assignee, array $attributes): Enquiry
    {
        $enquiry = new Enquiry;
        $enquiry->forceFill(array_merge($attributes, [
            'number' => $numbers->allocate('enquiry', $year),
            'assigned_to' => $assignee?->id,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]))->save();

        return $enquiry;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function followUp(Client|Enquiry $record, ?User $assignee, array $attributes): void
    {
        $followUp = new FollowUp;
        $followUp->followable()->associate($record);
        $followUp->forceFill(array_merge([
            'assigned_to' => $assignee?->id,
            'created_by' => $assignee?->id,
            'updated_by' => $assignee?->id,
        ], $attributes))->save();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function workOrder(DocumentNumber $numbers, int $year, ?User $actor, Enquiry $enquiry, Client $client, IndustryUnit $unit, Service $service, array $attributes): WorkOrder
    {
        $order = new WorkOrder;
        $order->forceFill(array_merge($attributes, [
            'number' => $numbers->allocate('work_order', $year),
            'enquiry_id' => $enquiry->id,
            'client_id' => $client->id,
            'industry_unit_id' => $unit->id,
            'service_id' => $service->id,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ]))->save();

        return $order;
    }

    private function quotation(DocumentNumber $numbers, int $year, ?User $actor, WorkOrder $order, Service $service, ?FeeSchedule $fee, QuotationStatus $status): void
    {
        $government = $fee?->government_fee ?? '0.00';
        $consultancy = $fee?->consultancy_fee ?? '0.00';
        $grand = number_format((float) $government + (float) $consultancy, 2, '.', '');

        $quotation = new Quotation;
        $quotation->forceFill([
            'quotation_number' => $numbers->allocate('quotation', $year),
            'work_order_id' => $order->id,
            'status' => $status,
            'created_by' => $actor?->id,
            'updated_by' => $actor?->id,
        ])->save();

        $revision = new QuotationRevision;
        $revision->forceFill([
            'quotation_id' => $quotation->id,
            'revision_number' => 1,
            'issue_date' => '2026-11-18',
            'valid_until' => '2026-12-18',
            'government_total' => $government,
            'consultancy_total' => $consultancy,
            'subtotal' => $grand,
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'grand_total' => $grand,
            'payment_terms' => '50% on acceptance and the balance before filing.',
            'customer_notes' => 'Government fees are collected separately and paid to the authority.',
            'created_by' => $actor?->id,
        ])->save();

        $line = new QuotationLine;
        $line->forceFill([
            'quotation_revision_id' => $revision->id,
            'sort_order' => 1,
            'line_type' => QuotationLineType::Service,
            'description' => $service->code.' — '.$service->name,
            'quantity' => '1.00',
            'government_fee' => $government,
            'consultancy_fee' => $consultancy,
            'discount_amount' => '0.00',
            'fee_schedule_id' => $fee?->id,
            'schedule_government_fee' => $government,
            'schedule_consultancy_fee' => $consultancy,
            'amounts_adjusted' => false,
            'tax_amount' => '0.00',
            'government_total' => $government,
            'consultancy_total' => $consultancy,
            'line_total' => $grand,
        ])->save();

        $quotation->forceFill([
            'current_revision_id' => $revision->id,
            'accepted_revision_id' => $status === QuotationStatus::Accepted ? $revision->id : null,
        ])->save();
    }
}
