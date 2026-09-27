<?php

namespace Tests\Feature;

use App\Models\Bacenta;
use App\Models\Church;
use App\Models\Member;
use App\Models\Region;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $leadA;
    private User $leadB;
    private User $bacentaLeader;
    private User $plainUser;
    private Region $regionA;
    private Region $regionB;
    private Bacenta $bacentaA;
    private Bacenta $bacentaB;
    private Member $memberA1;
    private Member $memberA2;
    private Member $memberB1;
    private Member $memberOrphan;

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

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->leadA = User::factory()->create();
        $this->leadA->assignRole('Region Lead');

        $this->leadB = User::factory()->create();
        $this->leadB->assignRole('Region Lead');

        $this->bacentaLeader = User::factory()->create();
        $this->bacentaLeader->assignRole('Bacenta Leader');

        $this->plainUser = User::factory()->create();

        $this->regionA = Region::create(['name' => 'Region A', 'stream_id' => $stream->id, 'leader_id' => $this->leadA->id]);
        $this->regionB = Region::create(['name' => 'Region B', 'stream_id' => $stream->id, 'leader_id' => $this->leadB->id]);

        $this->bacentaA = Bacenta::create(['name' => 'Bacenta A1', 'region_id' => $this->regionA->id, 'leader_id' => $this->bacentaLeader->id]);
        $this->bacentaB = Bacenta::create(['name' => 'Bacenta B1', 'region_id' => $this->regionB->id]);

        $base = ['phone' => '123', 'stream_id' => $stream->id];
        $this->memberA1 = Member::create(['name' => 'Member A1', 'bacenta_id' => $this->bacentaA->id, 'region_id' => $this->regionA->id] + $base);
        $this->memberA2 = Member::create(['name' => 'Member A2', 'bacenta_id' => $this->bacentaA->id, 'region_id' => $this->regionA->id] + $base);
        $this->memberB1 = Member::create(['name' => 'Member B1', 'bacenta_id' => $this->bacentaB->id, 'region_id' => $this->regionB->id] + $base);
        // Bacenta-less: admin-only by design.
        $this->memberOrphan = Member::create(['name' => 'Orphan']);

        $serviceType = ServiceType::create(['service_type' => 'Sunday Service', 'role_id' => 1, 'church_id' => $church->id]);
        Service::create([
            'date' => now(),
            'church_id' => $church->id,
            'service_type_id' => $serviceType->id,
            'treasurers' => 'tester',
            'treasurer_photo' => 'photo',
            'service_photo' => 'photo',
            'stream_id' => $stream->id,
            'region_id' => $this->regionA->id,
            'bacenta_id' => $this->bacentaA->id,
        ]);
    }

    private function memberIds($response): array
    {
        return collect($response->json('data'))->pluck('id')->sort()->values()->all();
    }

    public function test_super_admin_sees_all_members_including_orphans(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')->getJson('/api/v2/members');

        $response->assertOk();
        $this->assertEquals(
            [$this->memberA1->id, $this->memberA2->id, $this->memberB1->id, $this->memberOrphan->id],
            $this->memberIds($response)
        );
    }

    public function test_region_lead_sees_only_own_members(): void
    {
        $response = $this->actingAs($this->leadA, 'sanctum')->getJson('/api/v2/members');

        $response->assertOk();
        $this->assertEquals([$this->memberA1->id, $this->memberA2->id], $this->memberIds($response));
    }

    public function test_region_lead_cannot_access_other_region_member(): void
    {
        $this->actingAs($this->leadA, 'sanctum')->getJson("/api/v2/members/{$this->memberB1->id}")->assertForbidden();
        $this->actingAs($this->leadA, 'sanctum')->putJson("/api/v2/members/{$this->memberB1->id}", ['name' => 'Hacked'])->assertForbidden();
        $this->actingAs($this->leadA, 'sanctum')->deleteJson("/api/v2/members/{$this->memberB1->id}")->assertForbidden();
        $this->actingAs($this->leadA, 'sanctum')->getJson('/api/v2/members/999999')->assertNotFound();

        $this->assertDatabaseHas('members', ['id' => $this->memberB1->id, 'name' => 'Member B1', 'deleted_at' => null]);
    }

    public function test_region_lead_cannot_move_member_out_of_scope(): void
    {
        $this->actingAs($this->leadA, 'sanctum')
            ->putJson("/api/v2/members/{$this->memberA1->id}", ['bacenta_id' => $this->bacentaB->id])
            ->assertForbidden();

        $this->assertDatabaseHas('members', ['id' => $this->memberA1->id, 'bacenta_id' => $this->bacentaA->id]);
    }

    public function test_region_lead_can_delete_own_member(): void
    {
        $this->actingAs($this->leadA, 'sanctum')->deleteJson("/api/v2/members/{$this->memberA1->id}")->assertOk();

        $this->assertSoftDeleted('members', ['id' => $this->memberA1->id]);
    }

    public function test_bacenta_leader_sees_only_own_bacenta(): void
    {
        $response = $this->actingAs($this->bacentaLeader, 'sanctum')->getJson('/api/v2/members');

        $response->assertOk();
        $this->assertEquals([$this->memberA1->id, $this->memberA2->id], $this->memberIds($response));

        $this->actingAs($this->bacentaLeader, 'sanctum')->getJson("/api/v2/members/{$this->memberB1->id}")->assertForbidden();
    }

    public function test_user_without_role_sees_nothing(): void
    {
        $response = $this->actingAs($this->plainUser, 'sanctum')->getJson('/api/v2/members');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/v2/members')->assertUnauthorized();
    }

    public function test_region_lead_without_region_gets_forbidden_not_error(): void
    {
        $leadless = User::factory()->create();
        $leadless->assignRole('Region Lead');

        // V1 list (role-filtered) and V2 index (deny-all scope) must not 500.
        $this->actingAs($leadless, 'sanctum')->getJson('/api/members')->assertForbidden();
        $response = $this->actingAs($leadless, 'sanctum')->getJson('/api/v2/members');
        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_region_members_endpoint_respects_ownership(): void
    {
        $own = $this->actingAs($this->leadA, 'sanctum')->getJson("/api/v2/regions/{$this->regionA->id}/members");
        $own->assertOk();
        $this->assertEquals(2, $own->json('data.total'));

        $this->actingAs($this->leadA, 'sanctum')->getJson("/api/v2/regions/{$this->regionB->id}/members")->assertForbidden();
        $this->actingAs($this->leadA, 'sanctum')->getJson('/api/v2/regions/999999/members')->assertNotFound();
    }

    public function test_region_services_endpoint_respects_ownership(): void
    {
        $own = $this->actingAs($this->leadA, 'sanctum')->getJson("/api/v2/regions/{$this->regionA->id}/services");
        $own->assertOk();
        $this->assertCount(1, $own->json('data'));

        $this->actingAs($this->leadA, 'sanctum')->getJson("/api/v2/regions/{$this->regionB->id}/services")->assertForbidden();
    }

    public function test_region_update_respects_ownership(): void
    {
        $this->actingAs($this->leadA, 'sanctum')
            ->putJson("/api/v2/regions/{$this->regionA->id}", ['name' => 'Region A Renamed'])
            ->assertOk();

        $this->assertDatabaseHas('regions', ['id' => $this->regionA->id, 'name' => 'Region A Renamed']);

        $this->actingAs($this->leadA, 'sanctum')
            ->putJson("/api/v2/regions/{$this->regionB->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_dashboard_counts_are_scoped(): void
    {
        $lead = $this->actingAs($this->leadA, 'sanctum')->getJson('/api/v2/dashboard');
        $lead->assertOk();
        $this->assertEquals(2, $lead->json('data.members'));

        $admin = $this->actingAs($this->superAdmin, 'sanctum')->getJson('/api/v2/dashboard');
        $admin->assertOk();
        $this->assertEquals(4, $admin->json('data.members'));
    }

    public function test_v1_list_matches_v2_scope_for_region_lead(): void
    {
        $response = $this->actingAs($this->leadA, 'sanctum')->getJson('/api/members');

        $response->assertOk();
        $this->assertEquals([$this->memberA1->id, $this->memberA2->id], $this->memberIds($response));
    }
}
