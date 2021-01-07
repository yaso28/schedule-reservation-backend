<?php

namespace Tests\Feature\Http\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Fortify\LoginRateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Permission;
use App\Services\PermissionService;

class LoginTest extends TestCase
{
    protected $url = '/api/v5k4pgi3-login';

    public function testEmailEmpty()
    {
        $response = $this->postJson($this->url, [
            'email' => '',
            'password' => 'b',
        ]);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'email' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testPasswordEmpty()
    {
        $response = $this->postJson($this->url, [
            'email' => 'a',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'errors',
            [
                'password' => [
                    __('validation.required')
                ]
            ]
        );
    }

    public function testTooManyAttempts()
    {
        $seconds = 59;

        $this->mock(LoginRateLimiter::class, function ($mock) use ($seconds) {
            $mock->shouldReceive('tooManyAttempts')
                ->once()
                ->andReturn(true);
            $mock->shouldReceive('availableIn')
                ->once()
                ->andReturn($seconds);
        });
        Event::fake();

        $response = $this->postJson($this->url, [
            'email' => 'a',
            'password' => 'b',
        ]);

        Event::assertDispatched(Lockout::class);
        $response->assertStatus(429);
        $this->assertResponseContent(
            $response,
            'custom_message',
            __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' =>   ceil($seconds / 60),
            ])
        );
    }

    public function testFail()
    {
        $this->mock(LoginRateLimiter::class, function ($mock) {
            $mock->shouldReceive('tooManyAttempts')
                ->once()
                ->andReturn(false);
            $mock->shouldReceive('increment')
                ->once();
        });
        $this->mock(StatefulGuard::class, function ($mock) {
            $mock->shouldReceive('attempt')
                ->once()
                ->andReturn(false);
        });

        $response = $this->postJson($this->url, [
            'email' => 'a',
            'password' => 'b',
        ]);

        $response->assertStatus(422);
        $this->assertResponseContent(
            $response,
            'custom_message',
            __('auth.failed')
        );
    }

    public function testSuccess()
    {
        $user = User::factory()->make();
        $user->id = 3;
        $permissions = Permission::factory()->count(3)->make();
        $permissions[0]->id = 4;
        $permissions[1]->id = 11;
        $permissions[2]->id = 7;

        $this->mock(LoginRateLimiter::class, function ($mock) {
            $mock->shouldReceive('tooManyAttempts')
                ->once()
                ->andReturn(false);
        });
        $this->mock(StatefulGuard::class, function ($mock) {
            $mock->shouldReceive('attempt')
                ->once()
                ->andReturn(true);
        });
        $this->mock(PrepareAuthenticatedSession::class, function ($mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturnUsing(function ($request, $next) {
                    return $next($request);
                });
        });
        Auth::shouldReceive('user')
            ->once()
            ->andReturn($user);
        $this->mock(PermissionService::class, function ($mock) use ($user, $permissions) {
            $mock->shouldReceive('getUserPermissions')
                ->once()
                ->with($user)
                ->andReturn($permissions);
        });

        $response = $this->postJson($this->url, [
            'email' => 'a',
            'password' => 'b',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'permissions' => $permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->toArray(),
                ],
            ]);
    }
}
