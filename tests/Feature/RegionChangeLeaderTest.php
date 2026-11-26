<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\Region;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegionChangeLeaderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $oldLeader;
    private User $newLeader;
    private Region $region;
    private Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'Region Lead', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $church = Church::create(['name' => 'Test Church']);
        $this->stream = Stream::create([
            'name' => 'Stream A',
            'meeting_day' => 'Sunday',
            'meeting_time' => '09:00',
            'church_id' => $church->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->oldLeader = User::factory()->create();
        $this->oldLeader->assignRole('Region Lead');

        $this->newLeader = User::factory()->create();
        $this->newLeader->assignRole('Bacenta Leader');

        $this->region = Region::create([
            'name' => 'Region A',
            'stream_id' => $this->stream->id,
            'leader_id' => $this->oldLeader->id,
        ]);
    }

    public function test_admin_can_change_region_leader_and_roles_follow(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/regions/{$this->region->id}/leader", ['leader_id' => $this->newLeader->id]);

        $response->assertOk()->assertJsonPath('data.leader.id', $this->newLeader->id);

        $this->assertEquals($this->newLeader->id, $this->region->fresh()->leader_id);
        $this->assertTrue($this->newLeader->fresh()->hasRole('Region Lead'));
        $this->assertTrue($this->newLeader->fresh()->hasRole('Bacenta Leader'));
        $this->assertFalse($this->oldLeader->fresh()->hasRole('Region Lead'));
    }

    public function test_region_lead_cannot_change_leader(): void
    {
        $this->actingAs($this->oldLeader, 'sanctum')
            ->putJson("/api/v2/regions/{$this->region->id}/leader", ['leader_id' => $this->newLeader->id])
            ->assertForbidden();

        $this->actingAs($this->oldLeader, 'sanctum')
            ->putJson("/api/v2/regions/{$this->region->id}", ['leader_id' => $this->newLeader->id])
            ->assertForbidden();

        $this->assertEquals($this->oldLeader->id, $this->region->fresh()->leader_id);
    }

    public function test_rejects_user_who_already_leads_another_region(): void
    {
        Region::create([
            'name' => 'Region B',
            'stream_id' => $this->stream->id,
            'leader_id' => $this->newLeader->id,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/regions/{$this->region->id}/leader", ['leader_id' => $this->newLeader->id])
            ->assertStatus(422);

        $this->assertEquals($this->oldLeader->id, $this->region->fresh()->leader_id);
    }
}
