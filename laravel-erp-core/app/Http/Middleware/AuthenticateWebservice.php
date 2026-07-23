<?php

namespace App\Http\Middleware;

use App\Core\Configuration\Configuration;
use App\Models\WebserviceKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWebservice
{
    public function handle(Request $request, Closure $next, ?string $resource = null): Response
    {
        $enabled = (string) Configuration::get('ERP_WEBSERVICE', Configuration::get('PS_WEBSERVICE', '0'));
        if ($enabled !== '1') {
            return response()->json([
                'errors' => [['code' => 503, 'message' => 'Webservice is disabled.']],
            ], 503);
        }

        $keyValue = $request->query('ws_key')
            ?: $request->header('Ws-Key')
            ?: $request->bearerToken();

        if (! is_string($keyValue) || $keyValue === '') {
            return response()->json([
                'errors' => [['code' => 401, 'message' => 'Webservice key is missing.']],
            ], 401);
        }

        $webserviceKey = WebserviceKey::query()
            ->where('key', $keyValue)
            ->where('active', true)
            ->first();

        if (! $webserviceKey) {
            return response()->json([
                'errors' => [['code' => 401, 'message' => 'Invalid webservice key.']],
            ], 401);
        }

        $resource ??= (string) $request->route('resource');
        $method = strtoupper($request->method());

        if ($resource !== '' && ! $webserviceKey->allows($resource, $method)) {
            return response()->json([
                'errors' => [['code' => 403, 'message' => "Method {$method} is not allowed on resource {$resource}."]],
            ], 403);
        }

        $webserviceKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('webservice_key', $webserviceKey);

        return $next($request);
    }
}
