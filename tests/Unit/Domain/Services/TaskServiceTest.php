<?php

namespace Tests\Unit\Domain\Services;

use Tests\TestCase;
use App\Domain\Services\TaskService;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskService();
    }

    public function test_can_create_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $data = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending',
            'priority' => 'high',
            'project_id' => $project->id,
            'due_date' => now()->addDays(7),
            'estimated_hours' => 8.0,
            'cost' => 100.00,
        ];

        $task = $this->service->createTask($data, $user);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test Description', $task->description);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals($user->id, $task->created_by);
        $this->assertEquals(8.0, $task->estimated_hours);
        $this->assertEquals(100.00, $task->cost);
    }

    public function test_can_update_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);

        $data = [
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'status' => 'in_progress',
            'priority' => 'urgent',
        ];

        $updatedTask = $this->service->updateTask($task, $data);

        $this->assertEquals('Updated Task', $updatedTask->title);
        $this->assertEquals('Updated Description', $updatedTask->description);
        $this->assertEquals('in_progress', $updatedTask->status);
        $this->assertEquals('urgent', $updatedTask->priority);
    }

    public function test_can_assign_task_to_user()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $assignedUser = User::factory()->create();

        $result = $this->service->assignTask($task, $assignedUser);

        $this->assertTrue($result);
        $this->assertEquals($assignedUser->id, $task->fresh()->assigned_to);
    }

    public function test_can_complete_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => 'in_progress',
        ]);

        $result = $this->service->completeTask($task);

        $this->assertTrue($result);
        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_can_start_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'status' => 'pending',
        ]);

        $result = $this->service->startTask($task);

        $this->assertTrue($result);
        $this->assertEquals('in_progress', $task->fresh()->status);
    }

    public function test_can_add_tag_to_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $this->service->addTagToTask($task, 'bug');

        $this->assertTrue($task->fresh()->hasTag('bug'));
    }

    public function test_can_remove_tag_from_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'tags' => ['bug', 'feature'],
        ]);

        $this->service->removeTagFromTask($task, 'bug');

        $this->assertFalse($task->fresh()->hasTag('bug'));
        $this->assertTrue($task->fresh()->hasTag('feature'));
    }

    public function test_can_get_tasks_for_user()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => 'pending',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => 'completed',
        ]);

        $task3 = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'status' => 'pending',
        ]);

        $tasks = $this->service->getTasksForUser($user, ['status' => 'pending']);

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->contains($task1));
        $this->assertTrue($tasks->contains($task3));
        $this->assertFalse($tasks->contains($task2));
    }

    public function test_can_get_tasks_for_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $task1 = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $task2 = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
            'priority' => 'medium',
        ]);

        $task3 = Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $tasks = $this->service->getTasksForProject($project, ['priority' => 'high']);

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->contains($task1));
        $this->assertTrue($tasks->contains($task3));
        $this->assertFalse($tasks->contains($task2));
    }

    public function test_can_get_task_statistics()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'in_progress',
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
        ]);

        $statistics = $this->service->getTaskStatistics($project);

        $this->assertEquals(4, $statistics['total_tasks']);
        $this->assertEquals(2, $statistics['completed_tasks']);
        $this->assertEquals(1, $statistics['pending_tasks']);
        $this->assertEquals(1, $statistics['in_progress_tasks']);
        $this->assertEquals(50.0, $statistics['completion_rate']);
    }

    public function test_can_get_overdue_tasks()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $overdueTask = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'due_date' => now()->subDays(1),
            'status' => 'pending',
        ]);

        $normalTask = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'due_date' => now()->addDays(1),
            'status' => 'pending',
        ]);

        $overdueTasks = $this->service->getOverdueTasks($user);

        $this->assertCount(1, $overdueTasks);
        $this->assertTrue($overdueTasks->contains($overdueTask));
        $this->assertFalse($overdueTasks->contains($normalTask));
    }

    public function test_can_get_high_priority_tasks()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $highPriorityTask = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'priority' => 'high',
        ]);

        $urgentTask = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'priority' => 'urgent',
        ]);

        $normalTask = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'priority' => 'medium',
        ]);

        $highPriorityTasks = $this->service->getHighPriorityTasks($user);

        $this->assertCount(2, $highPriorityTasks);
        $this->assertTrue($highPriorityTasks->contains($highPriorityTask));
        $this->assertTrue($highPriorityTasks->contains($urgentTask));
        $this->assertFalse($highPriorityTasks->contains($normalTask));
    }

    public function test_can_start_time_tracking()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $timeEntry = $this->service->startTimeTracking($task, $user, 'Working on task');

        $this->assertInstanceOf(TimeEntry::class, $timeEntry);
        $this->assertEquals($task->id, $timeEntry->task_id);
        $this->assertEquals($user->id, $timeEntry->user_id);
        $this->assertTrue($timeEntry->isActive());
        $this->assertEquals('Working on task', $timeEntry->description);
    }

    public function test_can_stop_time_tracking()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $timeEntry = $this->service->startTimeTracking($task, $user);
        $stoppedEntry = $this->service->stopTimeTracking($task, $user);

        $this->assertInstanceOf(TimeEntry::class, $stoppedEntry);
        $this->assertTrue($stoppedEntry->isCompleted());
        $this->assertNotNull($stoppedEntry->end_time);
    }

    public function test_can_get_time_entries_for_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $timeEntry1 = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $timeEntry2 = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $timeEntries = $this->service->getTimeEntriesForTask($task);

        $this->assertCount(2, $timeEntries);
        $this->assertTrue($timeEntries->contains($timeEntry1));
        $this->assertTrue($timeEntries->contains($timeEntry2));
    }

    public function test_can_calculate_task_cost()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'cost' => 100.00,
            'end_time' => now(),
        ]);

        TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'cost' => 200.00,
            'end_time' => now(),
        ]);

        $cost = $this->service->calculateTaskCost($task);

        $this->assertEquals(300.00, $cost->getAmount());
    }

    public function test_can_get_task_timeline()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'due_date' => now()->addDays(7),
            'completed_at' => now()->addDays(3),
        ]);

        $timeline = $this->service->getTaskTimeline($task);

        $this->assertEquals($task->created_at, $timeline['created_at']);
        $this->assertEquals($task->due_date, $timeline['due_date']);
        $this->assertEquals($task->completed_at, $timeline['completed_at']);
        $this->assertFalse($timeline['is_overdue']);
        $this->assertEquals(3, $timeline['duration_days']);
    }

    public function test_can_get_task_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        Task::factory()->create([
            'project_id' => $project->id,
            'tags' => ['bug', 'feature'],
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'tags' => ['feature', 'urgent'],
        ]);

        $tags = $this->service->getTaskTags($project);

        $this->assertCount(3, $tags);
        $this->assertContains('bug', $tags);
        $this->assertContains('feature', $tags);
        $this->assertContains('urgent', $tags);
    }

    public function test_can_get_tasks_by_tag()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $bugTask1 = Task::factory()->create([
            'project_id' => $project->id,
            'tags' => ['bug', 'urgent'],
        ]);

        $bugTask2 = Task::factory()->create([
            'project_id' => $project->id,
            'tags' => ['bug'],
        ]);

        $featureTask = Task::factory()->create([
            'project_id' => $project->id,
            'tags' => ['feature'],
        ]);

        $bugTasks = $this->service->getTasksByTag('bug', $project);

        $this->assertCount(2, $bugTasks);
        $this->assertTrue($bugTasks->contains($bugTask1));
        $this->assertTrue($bugTasks->contains($bugTask2));
        $this->assertFalse($bugTasks->contains($featureTask));
    }
}
