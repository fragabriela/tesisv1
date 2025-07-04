<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DebugController;

class TrackFormSubmissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only track POST, PUT, PATCH requests (form submissions)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH']) && !$this->isApiRequest($request)) {
            try {
                // Log the form submission
                $debugController = new DebugController();
                $data = [
                    'route' => $request->route() ? ($request->route()->getName() ?? $request->path()) : $request->path(),
                    'method' => $request->method(),
                    'time' => now()->format('Y-m-d H:i:s'),
                    'ip' => $request->ip(),
                    'data' => $this->sanitizeRequestData($request->all()),
                    'user_id' => $request->user() ? $request->user()->id : null,
                ];
                
                // Call the method that stores form submissions
                $debugController->storeFormSubmission($data);
                
            } catch (\Exception $e) {
                Log::error('Error tracking form submission: ' . $e->getMessage());
            }
        }
        
        return $response;
    }
    
    /**
     * Check if the request is an API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isApiRequest(Request $request)
    {
        return $request->is('api/*') || $request->wantsJson() || $request->expectsJson();
    }
    
    /**
     * Sanitize sensitive data from the request.
     *
     * @param  array  $data
     * @return array
     */
    protected function sanitizeRequestData(array $data)
    {
        // List of sensitive fields to mask
        $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'token'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '******';
            }
        }
        
        return $data;
    }
}
