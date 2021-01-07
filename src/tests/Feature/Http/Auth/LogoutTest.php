<?php

namespace Tests\Feature\Http\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Actions\Fortify\DestroyAuthenticatedSession;

class LogoutTest extends TestCase
{
    protected $url = '/api/logout';

    public function testSuccess()
    {
        $this->mock(DestroyAuthenticatedSession::class, function ($mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturnUsing(function ($request, $next) {
                    return $next($request);
                });
        });

        $response = $this->postJson($this->url);

        $response->assertStatus(204);
    }
}
