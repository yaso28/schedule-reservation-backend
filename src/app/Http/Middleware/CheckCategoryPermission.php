<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CategoryPermissionService;
use Illuminate\Contracts\Auth\Access\Gate;
use App\Models\CategoryPermission;

class CheckCategoryPermission
{
    protected $categoryPermissionService;

    protected $gate;

    public function __construct(CategoryPermissionService $categoryPermissionService, Gate $gate)
    {
        $this->categoryPermissionService = $categoryPermissionService;
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param string $readOnly
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $readOnly)
    {
        $permissions = $this->categoryPermissionService->getPermissionListForCategory(
            $request->category,
            $readOnly == CategoryPermission::READ
        );
        foreach ($permissions as $permission) {
            $this->gate->authorize($permission);
        }

        return $next($request);
    }
}
