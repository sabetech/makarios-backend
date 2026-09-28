<?php

namespace Tests\Feature;

use App\Models\Bacenta;
use App\Models\Church;
use App\Models\Region;
use App\Models\ServiceType;
use App\Models\Stream;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\CloudinaryEngine;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Church $church;
    private Stream $stream;
    private Stream $otherStream;
    private Region $region;
    private Bacenta $bacenta;
    private ServiceType $streamServiceType;
    private ServiceType $bacentaServiceType;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'General Admin', 'Bishop', 'Stream Lead', 'Region Lead', 'Zone Lead', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $this->church = Church::create(['name' => 'Test Church']);
        $otherChurch = Church::create(['name' => 'Other Church']);

        $this->stream = Stream::create([
            'name' => 'Test Stream',
            'meeting_day' => 'Sunday',
            'meeting_time' => '09:00',
            'church_id' => $this->church->id,
            'is_active' => true,
        ]);
        $this->otherStream = Stream::create([
            'name' => 'Other Stream',
            'meeting_day' => 'Sunday',
            'meeting_time' => '10:00',
            'church_id' => $otherChurch->id,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->region = Region::create([
            'name' => 'Region A',
            'stream_id' => $this->stream->id,
            'leader_id' => $this->admin->id,
        ]);
        $this->bacenta = Bacenta::create([
            'name' => 'Bacenta A1',
            'region_id' => $this->region->id,
            'leader_id' => $this->admin->id,
        ]);

        $this->streamServiceType = ServiceType::create([
            'service_type' => 'Stream Service',
            'role_id' => 1,
            'church_id' => $this->church->id,
        ]);
        $this->bacentaServiceType = ServiceType::create([
            'service_type' => 'Bacenta Service',
            'role_id' => 1,
            'church_id' => $this->church->id,
        ]);
    }

    private function fakeUploads(): void
    {
        $engine = Mockery::mock(CloudinaryEngine::class);
        $engine->shouldReceive('upload')->andReturn($engine);
        $engine->shouldReceive('getSecurePath')->andReturn('https://example.test/photo.jpg');
        Cloudinary::shouldReceive('upload')->andReturn($engine);
    }

    private function basePayload(): array
    {
        return [
            'service_date' => '2026-09-27',
            'attendance' => 500,
            'offering' => 1000,
            'treasures' => ['Sample Treasurer1', 'Sample Treasurer2'],
            'treasures_picture' => 'data:image/webp;base64,AAA',
            'service_img' => 'data:image/webp;base64,BBB',
        ];
    }

    public function test_stream_service_saves_without_bacenta_and_derives_church(): void
    {
        $this->fakeUploads();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->streamServiceType->id,
            'stream_id' => $this->stream->id,
            'stream_name' => 'Spiritual Encounter Service',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('services', [
            'service_type_id' => $this->streamServiceType->id,
            'stream_id' => $this->stream->id,
            'church_id' => $this->church->id,
            'bacenta_id' => null,
            'attendance' => 500,
        ]);
    }

    public function test_stream_service_rejects_bacenta_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->streamServiceType->id,
            'stream_id' => $this->stream->id,
            'bacenta_id' => $this->bacenta->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('bacenta_id');
    }

    public function test_bacenta_service_derives_stream_and_church_when_stream_missing(): void
    {
        $this->fakeUploads();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->bacentaServiceType->id,
            'bacenta_id' => $this->bacenta->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('services', [
            'service_type_id' => $this->bacentaServiceType->id,
            'bacenta_id' => $this->bacenta->id,
            'stream_id' => $this->stream->id,
            'region_id' => $this->region->id,
            'church_id' => $this->church->id,
        ]);
    }

    public function test_bacenta_service_accepts_matching_stream_id(): void
    {
        $this->fakeUploads();

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->bacentaServiceType->id,
            'bacenta_id' => $this->bacenta->id,
            'stream_id' => $this->stream->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('services', [
            'bacenta_id' => $this->bacenta->id,
            'stream_id' => $this->stream->id,
        ]);
    }

    public function test_bacenta_service_rejects_mismatched_stream_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->bacentaServiceType->id,
            'bacenta_id' => $this->bacenta->id,
            'stream_id' => $this->otherStream->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('stream_id');
    }

    public function test_bacenta_service_requires_bacenta_id(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v2/services', $this->basePayload() + [
            'service_type_id' => $this->bacentaServiceType->id,
            'stream_id' => $this->stream->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('bacenta_id');
    }
}
