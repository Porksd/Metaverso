<?php

namespace VSHF\Bus;

/**
 * Class Bus
 *
 * @package VSHF
 */
class Bus implements BusInterface
{

    /**
     * @var array
     */
    private $subscriptions = [];

    /**
     * @var array
     */
    private $middlewares = [];

    public const AGENT_SYSTEM = 'system';
    public const AGENT_USER   = 'user';
    public const AGENT_APP    = 'app';

    /**
     * @param string $commandClassName
     * @param string $handlerClassName
     */
    public function subscribe(string $commandClassName, string $handlerClassName): void
    {
        $this->subscriptions[ $commandClassName ] = $handlerClassName;
    }

    /**
     * @param string $middlewareClassName
     * @param int    $queue Indicates the desired order (priority) of execution.
     *                      If there is already a Middleware with the same priority,
     *                      the latter will
     */
    public function addMiddleware(string $middlewareClassName, int $queue = 0): void
    {
        if (isset($this->middlewares[ $middlewareClassName ])) {
            throw new \UnexpectedValueException(sprintf('Middleware %s is already registered', $middlewareClassName));
        }
        $this->middlewares[ $middlewareClassName ] = $queue;
    }

    /**
     * @param CommandInterface $command
     * @param string           $agent_type The type dispatcher (can be the system or user or API token or else)
     * @param string|null      $agent_id   the ID of the dispatcher
     *
     * @return bool TRUE if the command is dispatched, FALSE otherwise
     */
    public function dispatch(CommandInterface $command, string $agent_type = self::AGENT_SYSTEM, string $agent_id = NULL): bool
    {
        $commandName = get_class($command);
        $handlerName = $commandName . 'Handler';
        if (!class_exists($handlerName)) {
            if (isset($this->subscriptions[ $commandName ]) && class_exists($this->subscriptions[ $commandName ])) {
                $handlerName = $this->subscriptions[ $commandName ];
            } else {
                throw new \InvalidArgumentException(sprintf('Handler class %s not found for command %s', $handlerName, $commandName));
            }
        }
        $handler = new $handlerName();

        if ($handler instanceof HandlerInterface) {

            $sortedMiddlewares = self::sortArrayByPriority($this->middlewares);

            foreach ($sortedMiddlewares as $middlewareName) {
                if (!class_exists($middlewareName)) {
                    continue;
                }
                $middleware = new $middlewareName($command, $agent_type, $agent_id);
                if (!$middleware instanceof Middleware) {
                    continue;
                }
                $middleware->before();

                if (!$middleware->isNext()) {
                    // Preventing the command execution
                    return FALSE;
                }
            }

            $handler->dispatch($command);

            foreach ($sortedMiddlewares as $middlewareName) {
                if (!class_exists($middlewareName)) {
                    continue;
                }
                $middleware = new $middlewareName($command, $agent_type, $agent_id);
                if (!$middleware instanceof Middleware) {
                    continue;
                }
                $middleware->after();
            }

            return TRUE;

        }

        throw new \InvalidArgumentException(sprintf('Class %s not an instance of Handler for command %s', $handlerName, $commandName));
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function getMiddlewares(): array
    {
        return self::sortArrayByPriority($this->middlewares);
    }

    private static function sortArrayByPriority($array): array
    {
        $priorities = [];
        $strings    = [];

        // Separate the priorities and strings into two separate arrays
        foreach ($array as $string => $priority) {
            $priorities[] = $priority;
            $strings[]    = $string;
        }

        // Sort the strings based on their corresponding priorities
        array_multisort($priorities, $strings);

        // Return the sorted array of strings
        return $strings;
    }
}