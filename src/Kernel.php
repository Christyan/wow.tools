<?php

namespace App;

use Symfony\Component\Routing\RouteCollection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;
use Roave\BetterReflection\BetterReflection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class Kernel
{
    private RouteCollection $routes;
    
    public function __construct($classLoader)
    {
        $routes = new RouteCollection();
        // Scan controller directory
        foreach (glob(WORK_DIR . '/src/Controller/*.php') as $file) {
            $baseName = basename($file, ".php");
            $astLocator = (new BetterReflection())->astLocator();
            $reflector = new DefaultReflector(new ComposerSourceLocator($classLoader, $astLocator));
            $class = $reflector->reflectClass('App\\Controller\\' . $baseName);

            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $path = '';
                $classRouteAttribute = $class->getAttributes(Route::class)[0] ?? null;
                if ($classRouteAttribute) {
                    $path .= $classRouteAttribute->getArguments()[0];
                }
                $routeAttribute = $method->getAttributes(Route::class)[0] ?? null;
                if ($routeAttribute) {
                    $path .= $routeAttribute->getArguments()[0];
                    $defaults = ['controller' => $class->getName(), 'action' => $method->getName()];
                    $requirements = $routeAttribute->getArguments()['requirements'] ?? [];

                    $route = new Route($path, $defaults, $requirements);
                    $routes->add($class->getName() . '.' . $method->getName(), $route);
                }
            }
        }
        
        $this->routes = $routes;
    }
    
    public function handleRequest()
    {
        $request = Request::createFromGlobals();
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $attributes = $matcher->match($request->getPathInfo());
            $controller = $attributes['controller'];
            $action = $attributes['action'];
            unset($attributes['_route'], $attributes['action'], $attributes['controller']);

            // Instantiate controller and call action
            $controllerInstance = new $controller();
            $response = $controllerInstance->$action(...array_values($attributes));
            $response->send();
        } catch (ResourceNotFoundException $exception) {
            http_response_code(404);
            exit;
        } catch (Exception $exception) {
            http_response_code(500);
            print defined('DEV') ? $exception->getMessage() : '';
            exit;
        }
    }

}