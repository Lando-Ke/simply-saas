<?php

namespace Tests\Unit\Domain\Services;

use App\Domain\Services\TimeTrackingService;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Domain\ValueObjects\TimeDuration;
use App\Domain\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimeTrackingService $service;
    private User $user;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new TimeTrackingService();
        $this->user = User::factory()->create(['hourly_rate' => 50.0]);
        $this->task = Task::factory()->create();
    }

    public function test_can_start_time_tracking()
    {
        $timeEntry = $this->service->startTimeTracking($this->task, $this->user, 'Test description');

        $this->assertInstanceOf(TimeEntry::class, $timeEntry);
        $this->assertEquals($this->task->id, $timeEntry->task_id);
        $this->assertEquals($this->user->id, $timeEntry->user_id);
        $this->assertEquals('Test description', $timeEntry->description);
        $this->assertEquals(50.0, $timeEntry->rate);
        $this->assertNotNull($timeEntry->start_time);
        $this->assertNull($timeEntry->end_time);
        $this->assertTrue($timeEntry->isActive());
    }

    public function test_start_time_tracking_stops_existing_active_entries()
    {
        // Create an active time entry
        $existingEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subHour(),
            'end_time' => null,
        ]);

        $this->service->startTimeTracking($this->task, $this->user);

        // Check that existing entry was stopped
        $existingEntry->refresh();
        $this->assertNotNull($existingEntry->end_time);
        $this->assertFalse($existingEntry->isActive());
    }

    public function test_can_stop_time_tracking()
    {
        // Start time tracking
        $timeEntry = $this->service->startTimeTracking($this->task, $this->user);
        
        // Wait a bit to ensure different timestamps
        sleep(1);
        
        $stoppedEntry = $this->service->stopTimeTracking($this->task, $this->user);

        $this->assertInstanceOf(TimeEntry::class, $stoppedEntry);
        $this->assertNotNull($stoppedEntry->end_time);
        $this->assertFalse($stoppedEntry->isActive());
        $this->assertTrue($stoppedEntry->isCompleted());
        $this->assertGreaterThanOrEqual(0, $stoppedEntry->duration_minutes);
        $this->assertGreaterThanOrEqual(0, $stoppedEntry->cost);
    }

    public function test_stop_time_tracking_returns_null_when_no_active_entry()
    {
        $result = $this->service->stopTimeTracking($this->task, $this->user);

        $this->assertNull($result);
    }

    public function test_can_stop_all_active_time_entries()
    {
        // Create multiple active time entries
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subHour(),
            'end_time' => null,
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subMinutes(30),
            'end_time' => null,
        ]);

        $this->service->stopAllActiveTimeEntries($this->user);

        $activeEntries = TimeEntry::where('user_id', $this->user->id)
            ->whereNull('end_time')
            ->count();

        $this->assertEquals(0, $activeEntries);
    }

    public function test_can_complete_time_entry()
    {
        $timeEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'start_time' => now()->subHour(),
            'end_time' => null,
            'rate' => 50.0,
        ]);

        $this->service->completeTimeEntry($timeEntry);

        $timeEntry->refresh();
        $this->assertNotNull($timeEntry->end_time);
        $this->assertFalse($timeEntry->isActive());
        $this->assertTrue($timeEntry->isCompleted());
        $this->assertEquals(60, $timeEntry->duration_minutes);
        $this->assertEquals(50.0, $timeEntry->cost);
    }

    public function test_can_get_active_time_entry()
    {
        $activeEntry = TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subHour(),
            'end_time' => null,
        ]);

        $result = $this->service->getActiveTimeEntry($this->user);

        $this->assertEquals($activeEntry->id, $result->id);
    }

    public function test_get_active_time_entry_returns_null_when_none_active()
    {
        $result = $this->service->getActiveTimeEntry($this->user);

        $this->assertNull($result);
    }

    public function test_can_get_time_entries_for_task()
    {
        TimeEntry::factory()->count(3)->create([
            'task_id' => $this->task->id,
        ]);

        $timeEntries = $this->service->getTimeEntriesForTask($this->task);

        $this->assertCount(3, $timeEntries);
        $this->assertTrue($timeEntries->every(fn($entry) => $entry->task_id === $this->task->id));
    }

    public function test_can_get_time_entries_for_user_with_filters()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(5),
            'end_time' => now()->subDays(5)->addHour(),
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(10), // Outside range
            'end_time' => now()->subDays(10)->addHour(),
        ]);

        $timeEntries = $this->service->getTimeEntriesForUser($this->user, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $this->assertCount(1, $timeEntries);
    }

    public function test_can_calculate_task_duration()
    {
        TimeEntry::factory()->create([
            'task_id' => $this->task->id,
            'duration_minutes' => 60,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'task_id' => $this->task->id,
            'duration_minutes' => 90,
            'end_time' => now(),
        ]);

        $duration = $this->service->calculateTaskDuration($this->task);

        $this->assertInstanceOf(TimeDuration::class, $duration);
        $this->assertEquals(150, $duration->getMinutes());
        $this->assertEquals(2.5, $duration->getHours());
    }

    public function test_can_calculate_task_cost()
    {
        TimeEntry::factory()->create([
            'task_id' => $this->task->id,
            'cost' => 25.0,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'task_id' => $this->task->id,
            'cost' => 37.5,
            'end_time' => now(),
        ]);

        $cost = $this->service->calculateTaskCost($this->task);

        $this->assertInstanceOf(Money::class, $cost);
        $this->assertEquals(62.5, $cost->getAmount());
    }

    public function test_can_calculate_user_duration()
    {
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'duration_minutes' => 120,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'duration_minutes' => 180,
            'end_time' => now(),
        ]);

        $duration = $this->service->calculateUserDuration($this->user);

        $this->assertInstanceOf(TimeDuration::class, $duration);
        $this->assertEquals(300, $duration->getMinutes());
        $this->assertEquals(5.0, $duration->getHours());
    }

    public function test_can_calculate_user_cost()
    {
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'cost' => 50.0,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'cost' => 75.0,
            'end_time' => now(),
        ]);

        $cost = $this->service->calculateUserCost($this->user);

        $this->assertInstanceOf(Money::class, $cost);
        $this->assertEquals(125.0, $cost->getAmount());
    }

    public function test_can_get_time_tracking_statistics()
    {
        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'duration_minutes' => 60,
            'cost' => 50.0,
            'rate' => 50.0,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'duration_minutes' => 90,
            'cost' => 75.0,
            'rate' => 50.0,
            'end_time' => now(),
        ]);

        $statistics = $this->service->getTimeTrackingStatistics($this->user, $this->task);

        $this->assertEquals(2, $statistics['total_entries']);
        $this->assertInstanceOf(TimeDuration::class, $statistics['total_duration']);
        $this->assertEquals(150, $statistics['total_duration']->getMinutes());
        $this->assertInstanceOf(Money::class, $statistics['total_cost']);
        $this->assertEquals(125.0, $statistics['total_cost']->getAmount());
        $this->assertEquals(50.0, $statistics['average_rate']);
        $this->assertInstanceOf(TimeDuration::class, $statistics['average_duration_per_entry']);
        $this->assertEquals(75, $statistics['average_duration_per_entry']->getMinutes());
    }

    public function test_can_get_time_tracking_by_date_range()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(5),
            'end_time' => now()->subDays(5)->addHour(),
        ]);

        TimeEntry::factory()->create([
            'user_id' => $this->user->id,
            'start_time' => now()->subDays(10), // Outside range
            'end_time' => now()->subDays(10)->addHour(),
        ]);

        $timeEntries = $this->service->getTimeTrackingByDateRange($startDate, $endDate, $this->user);

        $this->assertCount(1, $timeEntries);
    }

    public function test_can_validate_time_entry()
    {
        $validEntry = TimeEntry::factory()->create([
            'start_time' => now()->subHour(),
            'end_time' => now(),
        ]);

        $errors = $this->service->validateTimeEntry($validEntry);

        $this->assertEmpty($errors);
    }

    public function test_validates_time_entry_with_invalid_times()
    {
        $invalidEntry = TimeEntry::factory()->create([
            'start_time' => now(),
            'end_time' => now()->subHour(), // End before start
        ]);

        $errors = $this->service->validateTimeEntry($invalidEntry);

        $this->assertContains('Start time cannot be after end time', $errors);
    }

    public function test_validates_time_entry_exceeding_24_hours()
    {
        $invalidEntry = TimeEntry::factory()->create([
            'start_time' => now()->subDays(2),
            'end_time' => now(),
        ]);

        $errors = $this->service->validateTimeEntry($invalidEntry);

        $this->assertContains('Time entry cannot exceed 24 hours', $errors);
    }
}
