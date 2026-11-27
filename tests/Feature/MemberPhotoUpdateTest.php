<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\CloudinaryEngine;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Editing a member's photo in the PWA sends a base64 data URL. It has to be
 * uploaded to Cloudinary; storing it in members.img_url (varchar 255) failed.
 */
class MemberPhotoUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const DATA_URL = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD';
    private const UPLOADED = 'https://example.test/members/new.jpg';

    private User $user;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->member = Member::create(['name' => 'Ama', 'img_url' => 'https://example.test/old.jpg']);
    }

    private function fakeUploads(): void
    {
        $engine = Mockery::mock(CloudinaryEngine::class);
        $engine->shouldReceive('getSecurePath')->andReturn(self::UPLOADED);
        Cloudinary::shouldReceive('upload')->once()->andReturn($engine);
    }

    public function test_new_photo_in_picture_is_uploaded(): void
    {
        $this->fakeUploads();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/members/{$this->member->id}", ['name' => 'Ama B', 'picture' => self::DATA_URL])
            ->assertOk();

        $this->member->refresh();
        $this->assertSame(self::UPLOADED, $this->member->img_url);
        $this->assertSame('Ama B', $this->member->name);
    }

    public function test_new_photo_in_img_url_from_older_clients_is_uploaded(): void
    {
        $this->fakeUploads();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/members/{$this->member->id}", [
                'picture' => 'https://example.test/old.jpg',
                'img_url' => self::DATA_URL,
            ])
            ->assertOk();

        $this->assertSame(self::UPLOADED, $this->member->fresh()->img_url);
    }

    public function test_unchanged_photo_is_kept_without_uploading(): void
    {
        Cloudinary::shouldReceive('upload')->never();

        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/members/{$this->member->id}", [
                'name' => 'Ama C',
                'picture' => 'https://example.test/old.jpg',
                'img_url' => 'https://example.test/old.jpg',
            ])
            ->assertOk();

        $this->member->refresh();
        $this->assertSame('https://example.test/old.jpg', $this->member->img_url);
        $this->assertSame('Ama C', $this->member->name);
    }
}
