# Router

The Limber router lets you define the endpoints for your application and supports both closure and Class@Method based route actions. Define route groups to

## Features
* All major HTTP methods
* Closure and Class@Method route actions
* Route groups with nesting
* Individual route and group applied middleware
* Built-in and custom path parameter patterns
* Dependency injection

```php

$router = new \Limber\Router;

```
## HTTP Methods
Limber supports all major HTTP methods: 

* GET
* POST
* PUT
* PATCH
* DELETE
* HEAD
* OPTIONS

```php

$router->get('books', function(){
    
    $books = \App\Models\Books::all();
    return json_encode($books);

});

$router->post('books', function(Request $request){

    $book = new \App\Models\Book($request);
    $book->save();

    return json_encode($book);

});

```

Respond to any number of HTTP methods

```php

$router->add(['get', 'post'], 'books', function(Request $request){

});

```

Use a standalone class and method to handle incoming request

```php

$router->get('books', '\\App\\Controllers\\BooksController@list');

```

## Path parameters
Add request URI (path) parameters:

```php

$router->get('books/{id}', function($id){

    $book = \App\Models\Books::find($id);
    return json_encode($book);

});

```

### Path parameter patterns
Restrict parameters to a pattern

```php

$router->get('books/{id:int}', function($id){

    $book = \App\Models\Books::find($id);
    return json_encode($book);

});

```

Available built-in patterns:

* ```int``` Integer
* ```alpha``` Alpha-character (a-z)
* ```hex``` Hex number (a-f and 0-9)
* ```alphanumeric``` Alpha and integer (a-z and 0-9 )
* ```uuid``` Universally Unique Identifier

### Define your own patterns

You can define your own path parameter patterns by using a regular expression:

```php

\Limber\Router::setPattern('zipcode', '[0-9]{5}');

$router->get('books/{id:int}/locations/{zip:zipcode}', function($id, $zip){

    echo("Looking for book {$id} in the {$zip} zipcode.");

});

```

## Dependency injection

Limber will do its best to inject parameters into your route action (both closure and Class@Method).

For example, if you have a URI path parameter called ```{id}``` and your route action has a parameter called ```$id```, Limber will inject the path parameter into the action method.

This work for path parameters as well as the ```Request``` object.

```php

$router->get('books/{id}', function(Request $request, $id){

    echo("You requested book ID {$id}");

});

```
## Route groups

Create groups of routes that share a common set of configuration. Configuration options are:

* ```hostname``` Hostname to match.
* ```scheme``` Scheme to match (http or https).
* ```namespace``` All Class@Method route actions will use this namespace.
* ```prefix``` All URI paths will be prepended with this value when matching.
* ```middleware``` An array of class names to use for middleware.


```php

$route->group([
    
    'namespace' => '\App\Controllers',
    'middleware' => [
        \App\Middleware\Authenticate::class,
    ]

], function(Router $router){

    $router->get('authors', 'AuthorsController@list');
    $route->post('authors', 'AuthorsController@create');
    
});

```

### Nesting route groups

You can nest route groups. Each nested route group will inherit its parent's group config unless you specify otherwise.

Middleware config will be *merged* with the parent's middleware config.

```php

$router->group([
    'namespace' => '\App\Controllers',
    'middleware' => [
        \App\Middleware\Authenticate::class,
    ]
], function(Router $router){

    $router->get('/', 'HomeController@homePage');

    $router->group([
        'prefix' => 'admin',
        'namespace' => '\App\Controllers\Admin',
        'middleware' => [
            \App\Middleware\Authorize::class,
        ]
    ], function(Router $router){

        /**
         *  Middleware applied:
         *      \App\Middleware\Authenticate
         *      \App\Middleware\Authorize
         *  URL: /admin/users
         *  Controller: \App\Controllers\Admin\UsersController
         *  Method: list
         */
        $router->get('users', 'UsersController@list');

    });

});