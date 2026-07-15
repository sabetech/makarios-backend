<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetDatabaseByOrigin
{

    protected array $originConnectionMap = [
        'https://admin.makarios-church.org' => 'mysql_wol',
        'https://western-north.makarios-church.org' => 'mysql_western_north',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        $origin = $request->header('Origin') ?? $request->header('Referer') ?? $request->getHost(); // or parse from Origin/Referer header

        Log::info("Incoming request from origin: {$origin}");

        $connection = $this->originConnectionMap[$origin] ?? config('database.default');

        // Set the default connection for this request lifecycle
        DB::setDefaultConnection($connection);
        return $next($request);
    }
}
