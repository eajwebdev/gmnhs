<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $routeAccess = [
                'users*' => 'users',
                'view*' => 'view',
                'purchases*' => 'purchases',
                'properties*' => 'properties',
                'inventory*' => 'inventory',
                'report*' => 'reports',
                'technician*' => 'repair',
                'return-slips*' => 'return_slips',
                'settings*' => 'settings',
            ];

            foreach ($routeAccess as $pattern => $accessKey) {
                if ($request->is($pattern) && !user_has_access($accessKey, auth()->user())) {
                    return redirect()->route('dashboard')
                        ->with('error', 'You do not have permission to access this page');
                }
            }

            if (auth()->user()->hasRole('Staff')) {
                if ($request->is('users', 'office') || $request->is('users/*', 'office/*')) {
                    return redirect()->route('dashboard')
                        ->with('error1', 'You do not have permission to access this page');
                }
            }
        } else {
            return redirect()->route('getLogin')
                ->with('error', 'You have to Sign In first to access this page');
        }

        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }

}

