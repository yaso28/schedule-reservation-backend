<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Fortify\Http\Requests\LoginRequest;
use Illuminate\Routing\Pipeline;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use App\Actions\Fortify\AttemptToAuthenticate;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use App\Actions\Fortify\DestroyAuthenticatedSession;
use Illuminate\Support\Facades\Auth;
use App\Services\PermissionService;
use App\Http\Resources\UserResource;
use App\Http\Resources\PermissionResource;

class LoginController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function login(LoginRequest $request)
    {
        return $this->loginPipeline($request)->then(function () {
            $user = Auth::user();
            $permissions = $this->permissionService->getUserPermissions($user);

            return $this->success([
                'user' => new UserResource($user),
                'permissions' => PermissionResource::collection($permissions),
            ]);
        });
    }

    protected function loginPipeline(LoginRequest $request)
    {
        return (new Pipeline(app()))->send($request)->through(array_filter([
            EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }

    public function logout(Request $request)
    {
        return $this->logoutPipeline($request)->then(function () {
            return $this->success();
        });
    }

    protected function logoutPipeline(Request $request)
    {
        return (new Pipeline(app()))->send($request)->through(array_filter([
            DestroyAuthenticatedSession::class,
        ]));
    }
}
