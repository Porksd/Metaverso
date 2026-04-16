## VSHF PHP Config Manager

A settings/configuration manager for PHP Applications

## Usage
Instantiate the Config instance:
```php
$settings = new \VSHF\Config\Config();
```

To hydrate with settings:
```php
$settings = new \VSHF\Config\Config([
   'setting1' =>'value1',
   'setting2' =>'value2'
]);
```
### Contexts

The main (default) setting context is the _app_ context. Hydrating with settings in the constructor will feed that context.

You can hydrate different context later on:

```php
$settings->hydrate(
    [
        'settingA' =>'valueA',
        'settingB' =>'valueB'
    ],
    'myContext'
);
```

This will produce an internal settings tree like the following:

```
[
    'app' => [
        'setting1' =>'value1',
        'setting2' =>'value2'
    ],
    'myContext' =>[
        'settingA' =>'valueA',
        'settingB' =>'valueB'
    ]
]
```

## Observers

Each setting must have its Observer (that implements ObserverInterface).

An observer can handle one or more settings.

To register an Observer:

```php

// In the main context:
$settings->registerObserver('settingId', MyObserver::class);


// In a custom context
$settings->registerObserver('settingId', MyObserver::class, 'myContext');

// Observing multiple settings with a single observer:
$settings->registerObserver('settingA', MyObserver::class);
$settings->registerObserver('settingB', MyObserver::class);
```

## Get and save

To retrieve a setting:

```php
// From the main context:
$settings->get('settingA');

// From a custom context:
$settings->get('settingA', 'myContext');
```

To save a setting:

```php
// In the main context:
$settings->save('settingA', 'newValue');

// In a custom context:
$settings->save('settingA', 'newValue', 'myContext');
```

## Setting dependencies

A setting can depend on one or more other settings, even from different context.

To set dependencies, the Observer's _dependencies_ method must return a _Dependency_ object:

```php
// Inside settingA's observer class
public static function dependencies(){
    $dependency = new \VSHF\Config\Dependency();
    $dependency
        ->on('settingB')
        ->beingEqualTo('certainValue')
        ;
    return $dependency;
}
```

In this example, if _settingB_ is equal to _certainValue_, then _settingA_ is properly returned. Otherwise, NULL is returned.

Note: carefully consider that NULL should never be a default/proper setting value.

### Complex dependencies

Dependencies can be complex:

```php
$dependency = new \VSHF\Config\Dependency();

// AND
$dependency
    ->on('settingB')
    ->beingEqualTo('certainValue')
    ->and('settingC')
    ->beingTruthy()
    ;
    
// OR
$dependency
    ->on('settingB')
    ->beingEqualTo('certainValue')
    ->or('settingC')
    ->beingTruthy()
    ;

//INVALID, triggers an error
$dependency
    ->on('settingB')
    ->beingEqualTo('certainValue')
    ->and('settingC')
    ->beingTruthy()
    ->or('settingD')
    ->beingFalsy()
    ;

/*
 * This is equal to:
 *      settingB === certainValue && (
 *          settingC || !settingD
 *      )
 */
$dependency
    ->on('settingB')
    ->beingEqualTo('certainValue')
    ->andGroup()
    ->on('settingC')
    ->beingTruthy()
    ->or('settingD')
    ->beingFalsy()
    ->endGroup()
    ;

/*
 * This is equal to:
 *      settingB === certainValue || (
 *          settingC && !settingD
 *      )
 */
$dependency
    ->on('settingB')
    ->beingEqualTo('certainValue')
    ->orGroup()
    ->on('settingC')
    ->beingTruthy()
    ->and('settingD')
    ->beingFalsy()
    ->endGroup()
    ;
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/MIT)