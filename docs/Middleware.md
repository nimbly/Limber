# Middleware

Limber uses a "Before & After" middleware approach. You can interact with both the ```Request``` and ```Response``` objects (or just one or the other) within the same middleware layer.

```php

class MyMiddleware implements \Limber\Middleware\MiddlewareLayerInterface
{
    public function handle($request, \Closure $next)
    {
        // Do something with the request...


        $response = $next($request);

        
        // Do something with the reposnse...



        return $response;
    }
}

```