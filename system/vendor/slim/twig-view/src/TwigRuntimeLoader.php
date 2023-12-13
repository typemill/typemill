<?php

/**
 * Slim Framework (http://slimframework.com)
 *
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */

declare(strict_types=1);

namespace Slim\Views;

use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteParserInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    protected RouteParserInterface $routeParser;

    protected UriInterface $uri;

    protected string $basePath = '';

    /**
     * TwigRuntimeLoader constructor.
     *
     * @param RouteParserInterface $routeParser
     * @param UriInterface         $uri
     * @param string               $basePath
     */
    public function __construct(RouteParserInterface $routeParser, UriInterface $uri, string $basePath = '')
    {
        $this->routeParser = $routeParser;
        $this->uri = $uri;
        $this->basePath = $basePath;
    }

    /**
     * Create the runtime implementation of a Twig element.
     *
     * @param string $class
     *
     * @return mixed
     */
    public function load(string $class)
    {
        if (TwigRuntimeExtension::class === $class) {
            return new $class($this->routeParser, $this->uri, $this->basePath);
        }

        return null;
    }
}
