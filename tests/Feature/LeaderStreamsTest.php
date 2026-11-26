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

class LeaderStreamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaders_endpoint_reports_streams_for_each_leader(): void
    {
        foreach (['Super Admin', 'Stream Lead', 'Region Lead', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $church = Church::create(['name' => 'Test Church']);
        $streamDefaults = ['meeting_day' => 'Sunday', 'meeting_time' => '09:00', 'church_id' => $church->id, 'is_active' => true];

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $streamLead = User::factory()->create();
        $streamLead->assignRole('Stream Lead');
        $regionLead = User::factory()->create();
        $regionLead->assignRole('Region Lead');
        $bacentaLeader = User::factory()->create();
        $bacentaLeader->assignRole('Bacenta Leader');
        $unassigned = User::factory()->create();

        $streamA = Stream::create(['name' => 'Stream A', 'stream_overseer_id' => $streamLead->id] + $streamDefaults);
        $streamB = Stream::create(['name' => 'Stream B'] + $streamDefaults);

        $regionA = Region::create(['name' => 'Region A', 'stream_id' => $streamA->id, 'leader_id' => $regionLead->id]);
        $regionB = Region::create(['name' => 'Region B', 'stream_id' => $streamB->id]);

        // A bacenta leader spanning two streams appears under both.
        Bacenta::create(['name' => 'Bacenta A', 'region_id' => $regionA->id, 'leader_id' => $bacentaLeader->id]);
        Bacenta::create(['name' => 'Bacenta B', 'region_id' => $regionB->id, 'leader_id' => $bacentaLeader->id]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v2/leaders');
        $response->assertOk();

        $streamIdsFor = fn (User $user) => collect($response->json('data'))
            ->firstWhere('id', $user->id)['streams'];

        $this->assertEquals([['id' => $streamA->id, 'name' => 'Stream A']], $streamIdsFor($streamLead));
        $this->assertEquals([['id' => $streamA->id, 'name' => 'Stream A']], $streamIdsFor($regionLead));
        $this->assertEqualsCanonicalizing(
            [['id' => $streamA->id, 'name' => 'Stream A'], ['id' => $streamB->id, 'name' => 'Stream B']],
            $streamIdsFor($bacentaLeader)
        );
        $this->assertEquals([], $streamIdsFor($unassigned));
    }
}
