<?php

namespace Tests\Feature;

use App\Models\ImpersonationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leader;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'General Admin', 'Bishop', 'Bacenta Leader'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->leader = User::factory()->create();
        $this->leader->assignRole('Bacenta Leader');
    }

    private function startGhost(): string
    {
        $token = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$this->leader->id}/impersonate")
            ->assertOk()
            ->json('data.token');

        // Drop the actingAs user so the next request authenticates by token.
        $this->app['auth']->forgetGuards();

        return $token;
    }

    public function test_super_admin_gets_a_token_that_acts_as_the_target(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$this->leader->id}/impersonate")
            ->assertOk()
            ->assertJsonPath('data.impersonating', true)
            ->assertJsonPath('data.user.id', $this->leader->id)
            ->assertJsonPath('data.user.roles.0.name', 'Bacenta Leader');

        $this->app['auth']->forgetGuards();

        // A Bacenta Leader may not see admin-only routes, proving the token
        // carries the target's identity rather than the admin's.
        $this->withToken($response->json('data.token'))
            ->getJson('/api/v2/leaders')
            ->assertForbidden();

        $log = ImpersonationLog::sole();
        $this->assertSame($this->admin->id, $log->impersonator_id);
        $this->assertSame($this->leader->id, $log->impersonated_id);
        $this->assertNull($log->ended_at);

        $token = PersonalAccessToken::find($log->token_id);
        $this->assertSame("impersonated-by:{$this->admin->id}", $token->name);
        $this->assertNotNull($token->expires_at);
    }

    public function test_only_super_admins_can_ghost(): void
    {
        foreach (['General Admin', 'Bishop', 'Bacenta Leader'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v2/leaders/{$this->leader->id}/impersonate")
                ->assertForbidden();
        }

        $this->assertSame(0, ImpersonationLog::count());
    }

    public function test_cannot_ghost_another_super_admin_or_self(): void
    {
        $other = User::factory()->create();
        $other->assignRole('Super Admin');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$other->id}/impersonate")
            ->assertForbidden();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$this->admin->id}/impersonate")
            ->assertForbidden();
    }

    public function test_missing_or_deleted_user_is_not_found(): void
    {
        $this->leader->delete();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/leaders/{$this->leader->id}/impersonate")
            ->assertNotFound();
    }

    public function test_stop_revokes_only_the_ghost_token(): void
    {
        $ownToken = $this->leader->createToken('makarios-pwa');
        $ghostToken = $this->startGhost();

        $this->withToken($ghostToken)
            ->postJson('/api/v2/impersonate/stop')
            ->assertOk();

        $this->assertNotNull(ImpersonationLog::sole()->ended_at);
        $this->assertSame(1, $this->leader->tokens()->count());
        $this->assertNotNull(PersonalAccessToken::find($ownToken->accessToken->id));
    }

    public function test_stop_with_a_normal_token_is_rejected(): void
    {
        $ownToken = $this->leader->createToken('makarios-pwa')->plainTextToken;

        $this->withToken($ownToken)
            ->postJson('/api/v2/impersonate/stop')
            ->assertStatus(400);

        $this->assertSame(1, $this->leader->tokens()->count());
    }

    public function test_expired_ghost_token_is_rejected(): void
    {
        $ghostToken = $this->startGhost();
        $this->travel(\App\Http\Controllers\API\V2\ImpersonationController::TTL_MINUTES + 1)->minutes();

        $this->withToken($ghostToken)
            ->getJson('/api/v2/members')
            ->assertUnauthorized();
    }
}
