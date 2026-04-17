<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class SetDatabaseByOrigin
{

    protected array $originConnectionMap = [
        'admin.makarios-church.org' => 'mysql_wol',
        'western-north.makarios-church.org' => 'mysql_western_north',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->getHost(); // or parse from Origin/Referer header

        $connection = $this->originConnectionMap[$origin] ?? 'mysql_wol'; // fallback to default

        // Set the default connection for this request lifecycle
        DB::setDefaultConnection($connection);
        return $next($request);
    }
}
