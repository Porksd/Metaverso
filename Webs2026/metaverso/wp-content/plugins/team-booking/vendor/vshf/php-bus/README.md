## VSHF PHP Command Bus

A very simple command bus for PHP

## Usage
Instantiate the Bus instance:
```php
$bus = new \VSHF\Bus\Bus();
```

To execute a command:
```php
$command = new MyCommand($someParamsIfAny);

$bus->dispatch($command);
```

Each command class should have a corresponding handler class located in the same directory/namespace.

For instance, if you have a custom command named *MyCommand.php*, you should also include a *MyCommandHandler.php* file in the same directory/namespace. This allows the bus to call the appropriate handler for the command.

## Middleware

To register a middleware class:
```php
$bus->addMiddleware(MyMiddleware::class);
```

The middleware class must implement *MiddlewareInterface*.

```php
class MyMiddleware implements \VSHF\Bus\MiddlewareInterface {

    public function before() : void
    {
        // Code that runs before executing the command. It has access to:
        //  $this->$command
        //  $this->agent_type
        //  $this->agent_id
        
        $this->next(); // If this call is omitted, the command execution is prevented.
    }
    
    public function after() : void
    {
        // Code that runs after executing the command. It has access to:
        //  $this->$command
        //  $this->agent_type
        //  $this->agent_id
    }
}
```

If you have multiple middleware classes and need to define their execution order, you can specify a priority for each middleware. This is useful, for example, when a middleware should be executed at the end of the middleware chain:

```php
// greater number means delayed execution, default is 0

$bus->addMiddleware(MyMiddleware::class, 99);
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/MIT)