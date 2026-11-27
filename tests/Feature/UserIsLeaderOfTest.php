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

/**
 * isLeaderOf() feeds the dashboard header ("<role> - <what they lead>").
 */
class UserIsLeaderOfTest extends TestCase
{
    use RefreshDatabase;

    private array $streamDefaults;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Stream Lead', 'Region Lead', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $church = Church::create(['name' => 'Test Church']);
        $this->streamDefaults = ['meeting_day' => 'Sunday', 'meeting_time' => '09:00', 'church_id' => $church->id, 'is_active' => true];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_region_lead_shows_region_name(): void
    {
        $lead = $this->userWithRole('Region Lead');
        $stream = Stream::create(['name' => 'Stream A'] + $this->streamDefaults);
        Region::create(['name' => 'Kasoa Region', 'stream_id' => $stream->id, 'leader_id' => $lead->id]);

        $this->assertSame('Kasoa Region', $lead->fresh()->isLeaderOf());
    }

    public function test_region_lead_without_region_is_unassigned(): void
    {
        $this->assertSame('Unassigned Region', $this->userWithRole('Region Lead')->isLeaderOf());
    }

    public function test_stream_lead_shows_overseen_streams(): void
    {
        $lead = $this->userWithRole('Stream Lead');
        Stream::create(['name' => 'Stream A', 'stream_overseer_id' => $lead->id] + $this->streamDefaults);
        Stream::create(['name' => 'Stream B', 'stream_overseer_id' => $lead->id] + $this->streamDefaults);

        $this->assertSame('Stream A, Stream B', $lead->fresh()->isLeaderOf());
        $this->assertSame('Unassigned Stream', $this->userWithRole('Stream Lead')->isLeaderOf());
    }

    public function test_bacenta_leader_shows_every_bacenta(): void
    {
        $leader = $this->userWithRole('Bacenta Leader');
        Bacenta::create(['name' => 'Bacenta One', 'leader_id' => $leader->id]);
        Bacenta::create(['name' => 'Bacenta Two', 'leader_id' => $leader->id]);

        $this->assertSame('Bacenta One, Bacenta Two', $leader->fresh()->isLeaderOf());
        $this->assertSame('Unassigned Bacenta', $this->userWithRole('Bacenta Leader')->isLeaderOf());
    }
}
