<?php

namespace Tests\Feature;

use App\Models\Bacenta;
use App\Models\Church;
use App\Models\Region;
use App\Models\Stream;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Region Leads see (read-only) everyone leading a zone or bacenta in their
 * region, and the dashboard leaders count matches that list.
 */
class RegionLeadLeadersTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $leadA;
    private User $zoneLeadA;
    private User $bacentaLeadA1;
    private User $bacentaLeadA2;
    private User $zoneLeadB;
    private User $bacentaLeadB;
    private User $unassigned;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'General Admin', 'Bishop', 'Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $church = Church::create(['name' => 'Test Church']);
        $stream = Stream::create([
            'name' => 'Test Stream',
            'meeting_day' => 'Sunday',
            'meeting_time' => '09:00',
            'church_id' => $church->id,
            'is_active' => true,
        ]);

        $user = function (string $role): User {
            $u = User::factory()->create();
            $u->assignRole($role);
            return $u;
        };

        $this->superAdmin = $user('Super Admin');
        $this->leadA = $user('Region Lead');
        $leadB = $user('Region Lead');
        $this->zoneLeadA = $user('Zone Lead');
        $this->bacentaLeadA1 = $user('Bacenta Leader');
        $this->bacentaLeadA2 = $user('Bacenta Leader');
        $this->zoneLeadB = $user('Zone Lead');
        $this->bacentaLeadB = $user('Bacenta Leader');
        $this->unassigned = $user('Bacenta Leader');

        $regionA = Region::create(['name' => 'Region A', 'stream_id' => $stream->id, 'leader_id' => $this->leadA->id]);
        $regionB = Region::create(['name' => 'Region B', 'stream_id' => $stream->id, 'leader_id' => $leadB->id]);

        $zoneA = Zone::create(['name' => 'Zone A', 'region_id' => $regionA->id, 'leader_id' => $this->zoneLeadA->id]);
        Zone::create(['name' => 'Zone B', 'region_id' => $regionB->id, 'leader_id' => $this->zoneLeadB->id]);

        Bacenta::create(['name' => 'Bacenta A1', 'region_id' => $regionA->id, 'zone_id' => $zoneA->id, 'leader_id' => $this->bacentaLeadA1->id]);
        Bacenta::create(['name' => 'Bacenta A2', 'region_id' => $regionA->id, 'leader_id' => $this->bacentaLeadA2->id]);
        // Same leader on two bacentas must only be listed once.
        Bacenta::create(['name' => 'Bacenta A3', 'region_id' => $regionA->id, 'leader_id' => $this->bacentaLeadA2->id]);
        Bacenta::create(['name' => 'Bacenta A4', 'region_id' => $regionA->id]);
        Bacenta::create(['name' => 'Bacenta B1', 'region_id' => $regionB->id, 'leader_id' => $this->bacentaLeadB->id]);
    }

    private function regionALeaderIds(): array
    {
        return collect([$this->zoneLeadA, $this->bacentaLeadA1, $this->bacentaLeadA2])->pluck('id')->sort()->values()->all();
    }

    public function test_region_lead_lists_only_zone_and_bacenta_leaders_in_their_region(): void
    {
        $response = $this->actingAs($this->leadA, 'sanctum')
            ->getJson('/api/v2/leaders')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $this->assertSame($this->regionALeaderIds(), $ids);

        $zoneLead = collect($response->json('data'))->firstWhere('id', $this->zoneLeadA->id);
        $this->assertSame(['name' => 'Zone A', 'type' => 'Zone'], $zoneLead['leading']);
    }

    public function test_dashboard_leaders_count_matches_the_list(): void
    {
        $this->actingAs($this->leadA, 'sanctum')
            ->getJson('/api/v2/dashboard')
            ->assertOk()
            ->assertJsonPath('data.leaders', count($this->regionALeaderIds()));
    }

    public function test_region_lead_can_view_profiles_only_in_their_region(): void
    {
        $this->actingAs($this->leadA, 'sanctum')
            ->getJson("/api/v2/leaders/{$this->bacentaLeadA1->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->bacentaLeadA1->id);

        foreach ([$this->bacentaLeadB, $this->zoneLeadB, $this->unassigned, $this->superAdmin] as $other) {
            $this->actingAs($this->leadA, 'sanctum')
                ->getJson("/api/v2/leaders/{$other->id}")
                ->assertForbidden();
        }
    }

    public function test_region_lead_without_region_is_forbidden(): void
    {
        $lead = User::factory()->create();
        $lead->assignRole('Region Lead');

        $this->actingAs($lead, 'sanctum')->getJson('/api/v2/leaders')->assertForbidden();
        $this->actingAs($lead, 'sanctum')->getJson("/api/v2/leaders/{$this->bacentaLeadA1->id}")->assertForbidden();
    }

    public function test_region_lead_still_cannot_manage_leaders(): void
    {
        $id = $this->bacentaLeadA1->id;
        $this->actingAs($this->leadA, 'sanctum');

        $this->putJson("/api/v2/leaders/{$id}", ['name' => 'X'])->assertForbidden();
        $this->deleteJson("/api/v2/leaders/{$id}")->assertForbidden();
        $this->getJson('/api/v2/leaders/assignable/bacenta')->assertForbidden();
        $this->postJson("/api/v2/leaders/{$id}/assignments", [])->assertForbidden();
    }

    public function test_other_leads_cannot_list_leaders(): void
    {
        foreach ([$this->zoneLeadA, $this->bacentaLeadA1] as $user) {
            $this->actingAs($user, 'sanctum')->getJson('/api/v2/leaders')->assertForbidden();
        }
    }

    public function test_admin_still_sees_everyone(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/leaders')
            ->assertOk();

        $this->assertCount(User::count(), $response->json('data'));

        $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/leaders/{$this->bacentaLeadB->id}")
            ->assertOk();
    }
}
