<?php

namespace Tests\Unit\Domain\Services;

use Tests\TestCase;
use App\Domain\Services\ProjectService;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectTeamMember;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectService();
    }

    public function test_can_create_project()
    {
        $user = User::factory()->create();
        $data = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'active',
            'budget' => 1000.00,
            'is_public' => true,
        ];

        $project = $this->service->createProject($data, $user);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('Test Project', $project->name);
        $this->assertEquals('Test Description', $project->description);
        $this->assertEquals('active', $project->status);
        $this->assertEquals(1000.00, $project->budget);
        $this->assertTrue($project->is_public);
        $this->assertEquals($user->id, $project->user_id);
    }

    public function test_can_update_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $data = [
            'name' => 'Updated Project',
            'description' => 'Updated Description',
        ];

        $updatedProject = $this->service->updateProject($project, $data);

        $this->assertEquals('Updated Project', $updatedProject->name);
        $this->assertEquals('Updated Description', $updatedProject->description);
        $this->assertEquals('active', $updatedProject->status);
    }

    public function test_can_add_team_member()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $newMember = User::factory()->create();

        $teamMember = $this->service->addTeamMember($project, $newMember, 'admin');

        $this->assertInstanceOf(ProjectTeamMember::class, $teamMember);
        $this->assertEquals($project->id, $teamMember->project_id);
        $this->assertEquals($newMember->id, $teamMember->user_id);
        $this->assertEquals('admin', $teamMember->role);
        $this->assertTrue($teamMember->is_active);
    }

    public function test_can_remove_team_member()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $teamMember = User::factory()->create();
        
        $this->service->addTeamMember($project, $teamMember);
        
        $result = $this->service->removeTeamMember($project, $teamMember);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('project_team_members', [
            'project_id' => $project->id,
            'user_id' => $teamMember->id,
        ]);
    }

    public function test_can_update_team_member_role()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $teamMember = User::factory()->create();
        
        $this->service->addTeamMember($project, $teamMember, 'member');
        
        $result = $this->service->updateTeamMemberRole($project, $teamMember, 'admin');
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('project_team_members', [
            'project_id' => $project->id,
            'user_id' => $teamMember->id,
            'role' => 'admin',
        ]);
    }

    public function test_can_get_project_statistics()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'budget' => 1000.00,
        ]);

        // Create tasks
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
            'cost' => 100.00,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
            'cost' => 50.00,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'status' => 'completed',
            'cost' => 200.00,
        ]);

        $statistics = $this->service->getProjectStatistics($project);

        $this->assertEquals(3, $statistics['total_tasks']);
        $this->assertEquals(2, $statistics['completed_tasks']);
        $this->assertEquals(0, $statistics['overdue_tasks']);
        $this->assertEquals(66.67, $statistics['completion_rate']);
        $this->assertEquals(350.00, $statistics['total_cost']);
        $this->assertEquals(35.00, $statistics['budget_usage_percentage']);
    }

    public function test_can_get_projects_for_user()
    {
        $user = User::factory()->create();
        $project1 = Project::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $project2 = Project::factory()->create(['user_id' => $user->id, 'status' => 'completed']);
        $project3 = Project::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $projects = $this->service->getProjectsForUser($user, ['status' => 'active']);

        $this->assertCount(2, $projects);
        $this->assertTrue($projects->contains($project1));
        $this->assertTrue($projects->contains($project3));
        $this->assertFalse($projects->contains($project2));
    }

    public function test_can_get_public_projects()
    {
        $user = User::factory()->create();
        $publicProject = Project::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'status' => 'active',
        ]);

        $privateProject = Project::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
            'status' => 'active',
        ]);

        $projects = $this->service->getPublicProjects();

        $this->assertCount(1, $projects);
        $this->assertTrue($projects->contains($publicProject));
        $this->assertFalse($projects->contains($privateProject));
    }

    public function test_can_check_user_access_to_project()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $nonMember = User::factory()->create();
        
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $this->service->addTeamMember($project, $member, 'member');

        $this->assertTrue($this->service->canUserAccessProject($owner, $project));
        $this->assertTrue($this->service->canUserAccessProject($member, $project));
        $this->assertFalse($this->service->canUserAccessProject($nonMember, $project));
    }

    public function test_can_check_user_edit_permission()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $viewer = User::factory()->create();
        
        $project = Project::factory()->create(['user_id' => $owner->id]);
        $this->service->addTeamMember($project, $admin, 'admin');
        $this->service->addTeamMember($project, $member, 'member');
        $this->service->addTeamMember($project, $viewer, 'viewer');

        $this->assertTrue($this->service->canUserEditProject($owner, $project));
        $this->assertTrue($this->service->canUserEditProject($admin, $project));
        $this->assertFalse($this->service->canUserEditProject($member, $project));
        $this->assertFalse($this->service->canUserEditProject($viewer, $project));
    }

    public function test_can_calculate_project_timeline()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
        ]);

        $timeline = $this->service->calculateProjectTimeline($project);

        $this->assertEquals(30, $timeline['duration_days']);
        $this->assertGreaterThanOrEqual(9, $timeline['elapsed_days']);
        $this->assertLessThanOrEqual(11, $timeline['elapsed_days']);
        $this->assertGreaterThanOrEqual(19, $timeline['remaining_days']);
        $this->assertLessThanOrEqual(21, $timeline['remaining_days']);
        $this->assertGreaterThanOrEqual(30, $timeline['progress_percentage']);
        $this->assertLessThanOrEqual(37, $timeline['progress_percentage']);
    }

    public function test_can_get_project_budget_analysis()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'budget' => 1000.00,
        ]);

        // Create tasks with costs
        Task::factory()->create([
            'project_id' => $project->id,
            'cost' => 300.00,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'cost' => 200.00,
        ]);

        $analysis = $this->service->getProjectBudgetAnalysis($project);

        $this->assertEquals(1000.00, $analysis['budget']->getAmount());
        $this->assertEquals(500.00, $analysis['total_spent']->getAmount());
        $this->assertEquals(500.00, $analysis['remaining_budget']->getAmount());
        $this->assertEquals(50.00, $analysis['budget_usage_percentage']);
        $this->assertFalse($analysis['is_over_budget']);
    }

    public function test_budget_analysis_with_over_budget()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'budget' => 1000.00,
        ]);

        Task::factory()->create([
            'project_id' => $project->id,
            'cost' => 1200.00,
        ]);

        $analysis = $this->service->getProjectBudgetAnalysis($project);

        $this->assertTrue($analysis['is_over_budget']);
        $this->assertEquals(0.00, $analysis['remaining_budget']->getAmount());
        $this->assertEquals(120.00, $analysis['budget_usage_percentage']);
    }

    public function test_project_creation_adds_owner_as_team_member()
    {
        $user = User::factory()->create();
        $data = ['name' => 'Test Project'];

        $project = $this->service->createProject($data, $user);

        $this->assertDatabaseHas('project_team_members', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }
}
