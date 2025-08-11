<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserRedirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginRedirectController extends Controller
{
    /**
     * Handle post-login redirect based on user role and permissions
     */
    public function redirect(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if there's an intended URL
        if ($request->session()->has('url.intended')) {
            $intendedUrl = $request->session()->get('url.intended');
            
            // Extract route name from intended URL if possible
            $routeName = $this->getRouteNameFromUrl($intendedUrl);
            
            // Check if user can access the intended route
            if ($routeName && UserRedirectService::canAccessRoute($routeName, $user)) {
                return redirect()->intended();
            }
        }

        // Get appropriate dashboard route for user
        $dashboardRoute = UserRedirectService::getDashboardRoute($user);
        
        return redirect()->route($dashboardRoute)->with('success', 'Welcome back!');
    }

    /**
     * Extract route name from URL (basic implementation)
     */
    private function getRouteNameFromUrl(string $url): ?string
    {
        // Simple URL to route name mapping
        $urlParts = parse_url($url);
        $path = trim($urlParts['path'] ?? '', '/');
        
        return match ($path) {
            'dashboard' => 'dashboard',
            'projects' => 'projects',
            'tasks' => 'tasks',
            'billing' => 'billing',
            'branding' => 'branding',
            'profile' => 'profile',
            default => null,
        };
    }
}