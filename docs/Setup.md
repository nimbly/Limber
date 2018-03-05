# Setup


Example front controller:

```php

require 'vendor/autoload.php';

$router = new \Limber\Router\Router;
$router->get('books', '\App\Controllers\BooksController@list');

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$kernel = new \Limber\Kernel\HttpKernel($request, $router);
$response = $kernel->run();
$response->prepare();
$response->send();

```

