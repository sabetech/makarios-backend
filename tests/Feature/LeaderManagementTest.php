<?php

namespace Tests\Feature;

use App\Models\Bacenta;
use App\Models\Church;
use App\Models\Region;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader'] as $role) {
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
    }

    private function regionLead(string $regionName = 'Region A'): array
    {
        $leader = User::factory()->create();
        $leader->assignRole('Region Lead');
        $region = Region::create(['name' => $regionName, 'stream_id' => $this->stream->id, 'leader_id' => $leader->id]);

        return [$leader, $region];
    }

    public function test_show_lists_what_the_leader_leads(): void
    {
        [$leader, $region] = $this->regionLead();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v2/leaders/{$leader->id}")
            ->assertOk()
            ->assertJsonPath('data.role', 'Region Lead')
            ->assertJsonPath('data.role_entity_type', 'region')
            ->assertJsonPath('data.leading.0.id', $region->id)
            ->assertJsonPath('data.leading.0.context', 'Stream A');
    }

    public function test_update_name(): void
    {
        [$leader] = $this->regionLead();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/leaders/{$leader->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertTrue($leader->fresh()->hasRole('Region Lead'));
    }

    public function test_changing_role_unassigns_entities_of_the_old_role(): void
    {
        [$leader, $region] = $this->regionLead();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/leaders/{$leader->id}", ['role' => 'Bacenta Leader'])
            ->assertOk()
            ->assertJsonPath('data.role', 'Bacenta Leader')
            ->assertJsonPath('data.unassigned.region', 1)
            ->assertJsonPath('data.leading', []);

        $this->assertNull($region->fresh()->leader_id);
    }

    public function test_removing_role_unassigns_entities(): void
    {
        [$leader, $region] = $this->regionLead();

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/leaders/{$leader->id}", ['role' => null])
            ->assertOk()
            ->assertJsonPath('data.role', null);

        $this->assertCount(0, $leader->fresh()->roles);
        $this->assertNull($region->fresh()->leader_id);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v2/leaders/{$this->admin->id}", ['role' => null])
            ->assertForbidden();

        $this->assertTrue($this->admin->fresh()->hasRole('Super Admin'));
    }

    public function test_move_leader_to_another_region_displaces_previous_leader(): void
    {
        [$leader, $regionA] = $this->regionLead('Region A');
        [$other, $regionB] = $this->regionLead('Region B');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$leader->id}/assignments", [
                'type' => 'region',
                'entity_id' => $regionB->id,
                'from_id' => $regionA->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.leading.0.id', $regionB->id);

        $this->assertNull($regionA->fresh()->leader_id);
        $this->assertEquals($leader->id, $regionB->fresh()->leader_id);
        $this->assertFalse($other->fresh()->hasRole('Region Lead'));
    }

    public function test_assigning_second_region_without_move_is_rejected(): void
    {
        [$leader] = $this->regionLead('Region A');
        $regionB = Region::create(['name' => 'Region B', 'stream_id' => $this->stream->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$leader->id}/assignments", ['type' => 'region', 'entity_id' => $regionB->id])
            ->assertStatus(422);
    }

    public function test_assign_requires_matching_role(): void
    {
        [$leader] = $this->regionLead();
        $bacenta = Bacenta::create(['name' => 'Bacenta A']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$leader->id}/assignments", ['type' => 'bacenta', 'entity_id' => $bacenta->id])
            ->assertStatus(422);
    }

    public function test_bacenta_leader_can_lead_several_bacentas(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('Bacenta Leader');
        Bacenta::create(['name' => 'Bacenta A', 'leader_id' => $leader->id]);
        $second = Bacenta::create(['name' => 'Bacenta B']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$leader->id}/assignments", ['type' => 'bacenta', 'entity_id' => $second->id])
            ->assertOk()
            ->assertJsonCount(2, 'data.leading');
    }

    public function test_unassign_keeps_role(): void
    {
        [$leader, $region] = $this->regionLead();

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v2/leaders/{$leader->id}/assignments/region/{$region->id}")
            ->assertOk()
            ->assertJsonPath('data.leading', []);

        $this->assertNull($region->fresh()->leader_id);
        $this->assertTrue($leader->fresh()->hasRole('Region Lead'));
    }

    public function test_assignable_lists_entities_with_current_leader(): void
    {
        [$leader, $region] = $this->regionLead();
        Region::create(['name' => 'Region B', 'stream_id' => $this->stream->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v2/leaders/assignable/region')
            ->assertOk()
            ->assertJsonPath('data.0.leader.id', $leader->id)
            ->assertJsonPath('data.1.leader', null);
    }

    public function test_non_admin_cannot_manage_leaders(): void
    {
        [$leader] = $this->regionLead();

        $this->actingAs($leader, 'sanctum')
            ->putJson("/api/v2/leaders/{$leader->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }
}
