<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class LogActivity
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

        if (!in_array($request->method(), ['POST', 'PUT'])) {
            return $response;
        }

         // Cek route ada atau tidak
        $route = $request->route();
        
        if (!$route) {
            return $response;
        }

        // Hanya log jika request adalah AJAX atau JSON
        // if ($request->ajax() || $request->wantsJson()) {
        //     return $response;
        // }
        $routeMiddleware = $request->route()->gatherMiddleware();

        // Tentukan asal permintaan
        $origin = 'unknown';
        if (in_array('web', $routeMiddleware)) {
            $origin = 'web';
        } elseif (in_array('api', $routeMiddleware)) {
            $origin = 'api';
        }

        $idKode = null;
        $parameters = $route->parameters();
        if (!empty($parameters)) {
            $idKode = (string) array_values($parameters)[0];
        }

        $log = new ActivityLog();
        $log->user_id = Auth::id();
        $log->id_kode = $idKode;
        $log->url = $request->fullUrl();
        $log->method = $request->method();
        $log->request_data = json_encode($request->all());

        if ($response->headers->get('content-type') === 'application/json') {
            $responseData = $response->getContent();
        } elseif (is_array($response->original ?? null)) {
            $responseData = null; // Jika tidak ada JSON, set null
        } else {
            $responseData = null; // Jika tidak ada JSON, set null
        }
        
        $log->response_data = $responseData;

        $log->ip_address = $request->ip(); // Simpan IP address
        $log->created_at = now(); // Simpan waktu
        $log->origin = $origin; // Simpan asal permintaan
        $log->save();

        return $response;
    }
}

