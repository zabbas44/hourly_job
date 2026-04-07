<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Payment;
use App\Models\TimeEntry;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('scheduler.username', 'admin');
        config()->set('scheduler.password', 'Admin@12345');
        config()->set('scheduler.access_key', 'gondal');
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('Learning System')
            ->assertSeeText('Access key');
    }

    public function test_user_can_log_in_with_fixed_credentials(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin@12345',
            'access_key' => 'gondal',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_user_can_log_in_with_auto_login_token(): void
    {
        config()->set('scheduler.auto_login_token', 'secret-auto-login-token');

        $this->get('/auto-login/secret-auto-login-token?key=gondal')
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_invalid_login_is_rejected(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrong',
            'access_key' => 'gondal',
        ])->assertSessionHasErrors('username');
    }

    public function test_dashboard_shows_worker_month_chart_for_selected_month(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Chart Worker',
            'phone' => '+34 699 000 000',
            'email' => 'chart@example.com',
            'bank_title' => 'Chart Bank',
            'account_number' => 'CHART-1',
            'hourly_rate' => '10.00',
        ]);
        $project = Project::create(['name' => 'Chart Project']);
        $project->workers()->attach($worker);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-08',
            'hours' => 80,
            'hourly_rate_override' => '12.00',
        ]);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-05-08',
            'hours' => 20,
        ]);

        $this->get('/dashboard?month=2026-04')
            ->assertOk()
            ->assertSeeText('Chart Worker')
            ->assertSeeText('€960.00')
            ->assertSeeText('80.00 / 160h')
            ->assertSeeText('50.0% of target');
    }

    public function test_dashboard_can_filter_chart_by_custom_date_range(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Range Worker',
            'phone' => '+34 698 000 000',
            'email' => 'range@example.com',
            'bank_title' => 'Range Bank',
            'account_number' => 'RANGE-1',
            'hourly_rate' => '11.00',
        ]);
        $project = Project::create(['name' => 'Range Project']);
        $project->workers()->attach($worker);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-05',
            'hours' => 10,
        ]);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-25',
            'hours' => 30,
        ]);

        $this->get('/dashboard?month=2026-04&from_date=2026-04-01&to_date=2026-04-10')
            ->assertOk()
            ->assertSeeText('Range Worker')
            ->assertSeeText('€110.00')
            ->assertSeeText('10.00 / 160h')
            ->assertSeeText('6.3% of target')
            ->assertSeeText('01 Apr 2026 - 10 Apr 2026');
    }

    public function test_dashboard_can_filter_chart_by_preset_range(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Preset Worker',
            'phone' => '+34 697 000 000',
            'email' => 'preset@example.com',
            'bank_title' => 'Preset Bank',
            'account_number' => 'PRESET-1',
            'hourly_rate' => '9.00',
        ]);
        $project = Project::create(['name' => 'Preset Project']);
        $project->workers()->attach($worker);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => now()->toDateString(),
            'hours' => 5,
        ]);

        $this->get('/dashboard?preset=last_10_days')
            ->assertOk()
            ->assertSeeText('Preset Worker')
            ->assertSeeText('€45.00')
            ->assertSeeText('5.00 / 160h');
    }

    public function test_worker_crud_requires_authentication(): void
    {
        $this->get('/workers')->assertRedirect('/');
    }

    public function test_authenticated_user_can_record_partial_payment_and_see_outstanding_balance(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Paid Worker',
            'phone' => '+34 611 000 111',
            'email' => 'paid@example.com',
            'bank_title' => 'Bank',
            'account_number' => 'PAID-1',
            'hourly_rate' => '10.00',
        ]);
        $project = Project::create(['name' => 'Paid Project']);
        $project->workers()->attach($worker);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-01',
            'hours' => 10,
        ]);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-02',
            'hours' => 5,
        ]);

        $this->post('/payments', [
            'worker_id' => $worker->id,
            'paid_on' => '2026-04-15',
            'amount' => '120.00',
            'method' => 'bank_transfer',
        ])->assertRedirect('/payments');

        $this->assertDatabaseHas('payments', [
            'worker_id' => $worker->id,
            'amount' => '120.00',
            'method' => 'bank_transfer',
        ]);

        $this->get('/payments')
            ->assertOk()
            ->assertSeeText('€150.00')
            ->assertSeeText('€120.00')
            ->assertSeeText('€30.00')
            ->assertSeeText('April 2026');

        $this->get("/workers/{$worker->id}/schedule?month=2026-04")
            ->assertOk()
            ->assertSee('calendar-day-paid', false);
    }

    public function test_overpayment_creates_credit_and_marks_all_days_paid(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Credit Worker',
            'phone' => '+34 611 000 222',
            'email' => 'credit@example.com',
            'bank_title' => 'Bank',
            'account_number' => 'CREDIT-1',
            'hourly_rate' => '10.00',
        ]);
        $project = Project::create(['name' => 'Credit Project']);
        $project->workers()->attach($worker);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-01',
            'hours' => 10,
        ]);

        $this->post('/payments', [
            'worker_id' => $worker->id,
            'paid_on' => '2026-04-15',
            'amount' => '120.00',
            'method' => 'cash',
        ])->assertRedirect('/payments');

        $this->get('/payments')
            ->assertOk()
            ->assertSeeText('€100.00')
            ->assertSeeText('€120.00')
            ->assertSeeText('€20.00');
    }

    public function test_payment_save_keeps_selected_worker_filter(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Filtered Payment Worker',
            'phone' => '+34 611 000 223',
            'email' => 'filtered-payment@example.com',
            'bank_title' => 'Bank',
            'account_number' => 'FILTER-1',
            'hourly_rate' => '10.00',
        ]);

        $this->post('/payments', [
            'worker_id' => $worker->id,
            'filter_worker_id' => $worker->id,
            'paid_on' => '2026-04-15',
            'amount' => '50.00',
            'method' => 'cash',
        ])->assertRedirect("/payments?worker_id={$worker->id}");
    }

    public function test_payments_can_be_exported_as_csv_for_selected_worker(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'CSV Worker',
            'phone' => '+34 611 000 224',
            'email' => 'csv@example.com',
            'bank_title' => 'Bank',
            'account_number' => 'CSV-1',
            'hourly_rate' => '10.00',
        ]);
        $project = Project::create(['name' => 'CSV Project']);

        TimeEntry::create([
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-01',
            'hours' => 8,
        ]);

        Payment::create([
            'worker_id' => $worker->id,
            'paid_on' => '2026-04-02',
            'amount' => '50.00',
            'method' => 'cash',
        ]);

        $response = $this->get("/payments/export/csv?worker_id={$worker->id}");

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('CSV Worker', $content);
        $this->assertStringContainsString('CSV Project · 8h', $content);
        $this->assertStringContainsString('50.00', $content);
    }

    public function test_authenticated_user_can_create_update_and_delete_project(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Assigned Worker',
            'phone' => '+34 612 000 000',
            'email' => 'assigned@example.com',
            'bank_title' => 'Bank',
            'account_number' => 'ACC-1',
            'hourly_rate' => '12.00',
        ]);

        $this->post('/projects', [
            'name' => 'Main Project',
            'location' => 'Madrid Address',
            'client_name' => 'Client A',
            'client_phone' => '+34 700 000 000',
            'worker_ids' => [$worker->id],
        ])->assertRedirect('/projects');

        $project = Project::firstOrFail();
        $this->assertTrue($project->workers->contains($worker));

        $this->put("/projects/{$project->id}", [
            'name' => 'Updated Project',
            'location' => 'Barcelona Address',
            'client_name' => 'Client B',
            'client_phone' => '+34 711 111 111',
            'worker_ids' => [$worker->id],
        ])->assertRedirect('/projects');

        $project->refresh();
        $this->assertSame('Updated Project', $project->name);

        $this->delete("/projects/{$project->id}")
            ->assertRedirect('/projects');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_authenticated_user_can_create_update_and_delete_worker(): void
    {
        $this->login();

        $this->post('/workers', [
            'name' => 'Jane Doe',
            'phone' => '+34 600 111 222',
            'email' => 'jane@example.com',
            'bank_title' => 'Santander',
            'account_number' => 'ES120001234567890',
            'hourly_rate' => '18.50',
        ])->assertRedirect('/workers');

        $worker = Worker::firstOrFail();

        $this->put("/workers/{$worker->id}", [
            'name' => 'Jane Smith',
            'phone' => '+34 600 111 999',
            'email' => 'jane.smith@example.com',
            'bank_title' => 'BBVA',
            'account_number' => 'ES120009999999999',
            'hourly_rate' => '22.75',
        ])->assertRedirect('/workers');

        $worker->refresh();

        $this->assertSame('Jane Smith', $worker->name);
        $this->assertSame('22.75', $worker->hourly_rate);

        $this->delete("/workers/{$worker->id}")
            ->assertRedirect('/workers');

        $this->assertDatabaseMissing('workers', ['id' => $worker->id]);
    }

    public function test_authenticated_user_can_save_and_remove_daily_hours(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Ali Khan',
            'phone' => '+34 611 222 333',
            'email' => 'ali@example.com',
            'bank_title' => 'ING',
            'account_number' => 'PK120000123456789',
            'hourly_rate' => '15.00',
        ]);
        $project = Project::create(['name' => 'Site Alpha']);
        $project->workers()->attach($worker);

        $this->post("/workers/{$worker->id}/schedule", [
            'work_date' => '2026-04-10',
            'project_id' => $project->id,
            'hours' => 12,
            'hourly_rate_override' => '12.00',
            'month' => '2026-04',
        ])->assertRedirect("/workers/{$worker->id}/schedule?month=2026-04");

        $this->assertDatabaseHas('time_entries', [
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-10',
            'hours' => 12,
            'hourly_rate_override' => '12.00',
        ]);

        $this->get("/workers/{$worker->id}/schedule?month=2026-04")
            ->assertOk()
            ->assertSeeText('12h')
            ->assertSeeText('144.00')
            ->assertSeeText('12.00')
            ->assertSeeText('€144.00')
            ->assertSeeText('Site Alpha');

        $entry = TimeEntry::firstOrFail();

        $this->delete("/workers/{$worker->id}/schedule/{$entry->id}", [
            'month' => '2026-04',
        ])->assertRedirect("/workers/{$worker->id}/schedule?month=2026-04");

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_daily_hours_are_updated_instead_of_duplicated(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Sara Noor',
            'phone' => '+34 611 888 444',
            'email' => 'sara@example.com',
            'bank_title' => 'HSBC',
            'account_number' => 'AE123451234512345',
            'hourly_rate' => '20.00',
        ]);
        $project = Project::create(['name' => 'Site Beta']);
        $project->workers()->attach($worker);

        $this->post("/workers/{$worker->id}/schedule", [
            'work_date' => '2026-04-10',
            'project_id' => $project->id,
            'hours' => 8,
            'hourly_rate_override' => '12.00',
            'month' => '2026-04',
        ]);

        $this->post("/workers/{$worker->id}/schedule", [
            'work_date' => '2026-04-10',
            'project_id' => $project->id,
            'hours' => 14,
            'hourly_rate_override' => '12.00',
            'month' => '2026-04',
        ]);

        $this->assertSame(1, TimeEntry::count());
        $this->assertDatabaseHas('time_entries', [
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-10',
            'hours' => 14,
            'hourly_rate_override' => '12.00',
        ]);

        $this->get("/workers/{$worker->id}/schedule?month=2026-04")
            ->assertOk()
            ->assertSeeText('168.00')
            ->assertSeeText('14.00')
            ->assertSeeText('€168.00');
    }

    public function test_worker_can_save_time_against_any_project_without_assignment(): void
    {
        $this->login();

        $worker = Worker::create([
            'name' => 'Open Project Worker',
            'phone' => '+34 611 444 555',
            'email' => 'open-project@example.com',
            'bank_title' => 'Santander',
            'account_number' => 'ES00998877665544',
            'hourly_rate' => '15.00',
        ]);
        $project = Project::create(['name' => 'Unassigned Project']);

        $this->post("/workers/{$worker->id}/schedule", [
            'work_date' => '2026-04-07',
            'project_id' => $project->id,
            'hours' => 8,
            'month' => '2026-04',
        ])->assertRedirect("/workers/{$worker->id}/schedule?month=2026-04");

        $this->assertDatabaseHas('time_entries', [
            'worker_id' => $worker->id,
            'project_id' => $project->id,
            'work_date' => '2026-04-07',
            'hours' => 8,
        ]);
    }

    private function login(): void
    {
        $this->post('/login', [
            'username' => 'admin',
            'password' => 'Admin@12345',
            'access_key' => 'gondal',
        ]);
    }
}
