<?php

namespace DI\Bridge\Slim;

use Invoker\Exception\NotCallableException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\AdvancedCallableResolverInterface;

/**
 * Resolve middleware and route callables using PHP-DI.
 */
class CallableResolver implements AdvancedCallableResolverInterface
{
    /**
     * @var \Invoker\CallableResolver
     */
    private $callableResolver;

    public function __construct(\Invoker\CallableResolver $callableResolver)
    {
        $this->callableResolver = $callableResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($toResolve): callable
    {
        return $this->callableResolver->resolve($this->translateNotation($toResolve));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRoute($toResolve): callable
    {
        return $this->resolvePossibleSignature($toResolve, 'handle', RequestHandlerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMiddleware($toResolve): callable
    {
        return $this->resolvePossibleSignature($toResolve, 'process', MiddlewareInterface::class);
    }

    /**
     * Translate Slim string callable notation ('nameOrKey:method') to PHP-DI notation ('nameOrKey::method').
     */
    private function translateNotation($toResolve)
    {
        if (is_string($toResolve) && preg_match(\Slim\CallableResolver::$callablePattern, $toResolve)) {
            $toResolve = str_replace(':', '::', $toResolve);
        }

        return $toResolve;
    }

    private function resolvePossibleSignature($toResolve, string $method, string $typeName): callable
    {
        if (is_string($toResolve)) {
            $toResolve = $this->translateNotation($toResolve);

            try {
                $callable = $this->callableResolver->resolve([$toResolve, $method]);

                if (is_array($callable) && $callable[0] instanceof $typeName) {
                    return $callable;
                }
            } catch (NotCallableException $e) {
                // Fall back to looking for a generic callable.
            }
        }

        return $this->callableResolver->resolve($toResolve);
    }
}
