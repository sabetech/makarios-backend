<?php

namespace Tests\Feature;

use App\Models\Bacenta;
use App\Models\Basonta;
use App\Models\Church;
use App\Models\Region;
use App\Models\Stream;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Bacenta $bacenta;

    private Basonta $basonta;

    protected function setUp(): void
    {
        parent::setUp();

        $church = Church::create(['name' => 'Test Church']);
        $stream = Stream::create([
            'name' => 'Test Stream',
            'meeting_day' => 'Sunday',
            'meeting_time' => '09:00',
            'church_id' => $church->id,
            'is_active' => true,
        ]);
        $region = Region::create(['name' => 'Region A', 'stream_id' => $stream->id]);

        $this->bacenta = Bacenta::create(['name' => 'Bacenta A1', 'region_id' => $region->id]);
        $this->basonta = Basonta::unguarded(fn () => Basonta::create(['name' => 'Basonta A']));

        $this->superAdmin = User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Sample New Member',
            'phone' => '0248893920',
            'gender' => ['male'],
            'marital_status' => ['single'],
        ];
    }

    public function test_create_accepts_scalar_bacenta_and_basonta(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')->postJson('/api/v2/members', $this->payload([
            'bacenta' => (string) $this->bacenta->id,
            'basonta' => (string) $this->basonta->id,
        ]));

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('members', [
            'name' => 'Sample New Member',
            'bacenta_id' => $this->bacenta->id,
            'basonta_id' => $this->basonta->id,
            'region_id' => $this->bacenta->region_id,
            'gender' => 'male',
            'marital_status' => 'single',
        ]);
    }

    public function test_create_accepts_array_bacenta(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')->postJson('/api/v2/members', $this->payload([
            'bacenta' => [$this->bacenta->id],
        ]));

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('members', [
            'name' => 'Sample New Member',
            'bacenta_id' => $this->bacenta->id,
        ]);
    }

    public function test_create_still_rejects_unknown_bacenta(): void
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')->postJson('/api/v2/members', $this->payload([
            'bacenta' => '999999',
        ]));

        $response->assertJsonValidationErrors('bacenta.0');
    }
}
