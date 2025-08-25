<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $response = $next($request);

        // Only apply cache headers to successful responses
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        // Don't cache responses with errors
        if ($response->headers->get('content-type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (isset($content['error']) || isset($content['errors'])) {
                return $this->setNoCacheHeaders($response);
            }
        }

        return match ($type) {
            'static' => $this->setLongCacheHeaders($response),
            'short' => $this->setShortCacheHeaders($response),
            'private' => $this->setPrivateCacheHeaders($response),
            'no-cache' => $this->setNoCacheHeaders($response),
            default => $this->setDefaultCacheHeaders($response)
        };
    }

    /**
     * Set default cache headers (1 hour)
     */
    private function setDefaultCacheHeaders(Response $response): Response
    {
        return $response->withHeaders([
            'Cache-Control' => 'public, max-age=3600',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 3600),
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Set short cache headers (5 minutes)
     */
    private function setShortCacheHeaders(Response $response): Response
    {
        return $response->withHeaders([
            'Cache-Control' => 'public, max-age=300',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 300),
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Set long cache headers (24 hours)
     */
    private function setLongCacheHeaders(Response $response): Response
    {
        return $response->withHeaders([
            'Cache-Control' => 'public, max-age=86400',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 86400),
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Set private cache headers (browser only, 1 hour)
     */
    private function setPrivateCacheHeaders(Response $response): Response
    {
        return $response->withHeaders([
            'Cache-Control' => 'private, max-age=3600',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 3600),
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Set no-cache headers
     */
    private function setNoCacheHeaders(Response $response): Response
    {
        return $response->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}