<?php

namespace App\Http\Middleware;

use App\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShiftMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentShift = Shift::where('end_at', null)->where('closed', false)->first();

        if (!$currentShift) {
            // Redirect to start shift page if no active shift
            return redirect()->route('shifts.start');
        }
        return $next($request);
    }
}
