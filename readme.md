![Laravel Factory Enhanced - Supercharge your tests](https://raw.githubusercontent.com/makeabledk/laravel-factory-enhanced/master/banner-1.png)

# Laravel Factory Enhanced 🔥

[![Latest Version on Packagist](https://img.shields.io/packagist/v/makeabledk/laravel-factory-enhanced.svg)](https://packagist.org/packages/makeabledk/laravel-factory-enhanced)
[![Build Status](https://img.shields.io/github/workflow/status/makeabledk/laravel-factory-enhanced/Run%20tests?label=Tests)](https://github.com/makeabledk/laravel-factory-enhanced/actions)
[![StyleCI](https://styleci.io/repos/117680722/shield?branch=master)](https://styleci.io/repos/117680722)

Bring the magic of eloquent relationships into the Laravel Factory. 

Traditionally if you wanted to factory a team with some users, you'd have to manually create the individual team and users and then tie them together afterwards. This can easily lead to very verbose tests.

**Laravel 7.x and earlier**

```php
$team = factory(Team::class)->create();
$users = factory(User::class)->times(2)->create();
foreach ($users as $user) {
    factory(TeamMember::class)->create([
        'team_id' => $team,
        'user_id' => $user,
        'role' => 'admin'
    ]);
}
```

**Laravel 8.0 and later**

```php
$team = Team::factory()
    ->hasAttached(
        User::factory()->count(2),
        ['active' => true]
    )
    ->create();

```

**Laravel Factory Enhanced**

```php
$team = Team::factory()
    ->with(2, 'users', ['pivot.role' => 'admin'])
    ->create();
```

## Installation

You may install the package via composer

```bash
composer require makeabledk/laravel-factory-enhanced
```


### Versions

**Laravel 8+ class based factories**

Version 4 and later of this package is compatible with the new class-based syntax introduced with Laravel 8.

**Pre-Laravel 7 factories**

Version 3 and earlier of this package is compatible with the legacy `$factory->define()` syntax. Please find docs here [v3 documentation](https://github.com/makeabledk/laravel-factory-enhanced/tree/3.x):

## Upgrade guide to v4

The majority of the refactoring needed to upgrade to v4 of this package, lies in rewriting factories to be compatible with Laravel class-based factories. 

If you use [Laravel Shift](https://laravelshift.com) when upgrading to Laravel 8, a lot of this work will be automated, and you will be well on you way.

### Rewriting to class based factories

Please use [Laravel Shift](https://laravelshift.com) for upgrading Laravel versions, or refer to the [official documentation](https://laravel.com/docs/8.x/database-testing) on how to write factories using the class-based approach.


### Applying states

Replace all occurrences of `->state('some-state')` with `->someState()` in your test suite.


### Presets

The concept of presets which was introduced by this package may now simply be rewritten to states.

As such, replace all occurrences of `->preset('some-preset')` with `->somePreset()` in your test suite.


### Times method

Replace all occurrences of `->times(x)` with `->count(x)` in your test suite.


### Factory helper syntax

This change is completely optional. If you wish, you may change all occurrence of `factory(SomeModel::class)` to `SomeModel::factory()` in your test suite. 

_If you choose to do so_, remember to add `use \Makeable\LaravelFactory\Factory;` to all models.


### Other breaking changes

The `odds()` method has been removed from the Factory instance.


## Usage

Once the package is installed, your factories should extend `Makeable\LaravelFactory\Factory` rather than the native Laravel `Factory` class. 

Additionally, please make sure to use the corresponding `Makeable\LaravelFactory\HasFactory` trait on your models.

For example:

**app/Models/User.php**

```php
<?php

namespace App\Models;

use Makeable\LaravelFactory\HasFactory;

class User 
{
    use HasFactory;
    
    // ...
}
```

**database/factories/UserFactory.php**

```php
<?php

namespace Database\Factories;

use Makeable\LaravelFactory\Factory;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            // ...
        ];
    }
}
```

You may now use all the native Laravel features you are used to, along with the additional functionality this package provides.

If you're not familiar with Laravel factories, please refer to the official documentation: https://laravel.com/docs/master/database-testing

### Simple relationships

You may use the enhanced factory to create any additional relationships defined on the model.

Example: A `Server` model with a `sites()` relationship (has-many)

```php
Server::factory()->with(3, 'sites')->create();
```

Note that you may still use any native functionality that you are used to, such as states and attributes:

```php
Server::factory()->online()->with(3, 'sites')->create([
    'name' => 'production-1'
]);
```

### Multiple relationships

You are free to specify multiple relationships on the same factory. 

Given our previous `Server` model has another relationship called `team` (belongs-to), you may do:

```php
Server::factory()
    ->with('team')
    ->with(3, 'sites')
    ->create();
```

Now you would have `1 team` that has `1 server` which has `3 sites`.


### Nested relationships

Moving on to the more advanced use-cases, you may also do nested relationships. 

For instance we could rewrite our example from before using nesting:

```php
Team::factory()
    ->with(3, 'servers.sites')
    ->create();
```
Please note that the count '3' applies to *the final relationship*, in this case `sites`.

If you are wanting 2 servers each of which has 3 sites, you may write it as following:

```php
Team::factory()    
    ->with(2, 'servers')
    ->with(3, 'servers.sites')
    ->create();
```

### States in relationships

Just as you may specify pre-defined states on the factoring model ([see official documentation](https://laravel.com/docs/master/database-testing#factory-states)), you may also apply the very same states on the relation.

```php
Team::factory()    
    ->with(2, 'online', 'servers')
    ->create();
```

You may finding yourself wanting a relationship in multiple states. In this case you may use the `andWith()` method.

```php
Team::factory()    
    ->with(2, 'online', 'servers')
    ->andWith(1, 'offline', 'servers')
    ->create();
```

By using the `andWith` we will create a 'clean cut', so that no further calls to `with` can interfere with relations specified prior to the `andWith`. 

In the above example any further nesting of relations will apply to the 'offline' server.

```php
Team::factory()    
    ->with(2, 'online', 'servers')
    ->andWith(1, 'offline', 'servers')
    ->with(3, 'servers.sites')
    ->create();
```

The above example will create 1x team that has

- 2x online servers
- 1x offline servers with 3 sites

### Filling attributes in relationships

You may fill attributes on a relationship by passing them as an argument to the `with()` method.

```php
factory(Team::class)    
    ->with(2, 'servers', ['name' => 'laravel.com'])
    ->create();
```

If the relation is a belongs-to-many relationship, you may also fill attributes on the pivot model by prefixing the attribute name with `pivot.`.

```php
Team::factory()    
    ->with(2, 'users', ['pivot.role' => 'admin'])
    ->create();
```

### Using apply()

All of the above examples of how you might configure a relationship using the `with()` method, can also be applied on the base model using the `apply()` method.

For example:

```php
Server::factory()
    ->apply(2, 'online', ['name' => 'laravel.com'])
    ->with(3, 'mysql', 'databases')
    ->create();
```

This would create 2 online servers each with 3 mysql databases.

In fact, by using the `HasFactory` trait from this package, you can even pass these arguments to the `::factory()` method itself:

```php
Server::factory(2, 'online', ['name' => 'laravel.com'])
    ->with(3, 'mysql', 'databases')
    ->create();
```

### Using closures for customization

In addition to passing *count* and *state* directly into the `with` function, you may also pass a closure that will receive the `FactoryBuilder` instance directly.

In the closure you can do everything you are used to on the `FactoryBuilder` - including nesting further relationships should you wish to.

```php
Team::factory()    
    ->with('servers', fn (ServerFactory $servers) => $servers
        ->count(2)
        ->active()
        ->with(3, 'sites')
    )
    ->create();
```


### Factoring models with no defined factory

Traditionally trying to use `Model::factory()` on a model with no defined factory would throw an exception. Not anymore!

After installing this package, you are completely free to use the static `Model::factory()` method on any Eloquent model that uses the `Makeable\LaravelFactory\HasFactory` trait.

Furthermore, this package brings back the good old `factory(Model::class)` helper function which you may use on any model, whether or not the model has a defined factory or uses the `HasFactory` trait.

**Example**

```php
factory(2, Server::class)->with(1, 'sites')->create();
```

## Available methods

These are the provided methods on the `Factory` instance in addition to the core methods.

- apply
- fill
- fillPivot (only applicable on BelongsToMany
- pipe
- tap
- with
- andWith

## Testing

You can run the tests with:

```bash
composer test
```

## Contributing

We are happy to receive pull requests for additional functionality. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Rasmus Christoffer Nielsen](https://github.com/rasmuscnielsen)
- [All Contributors](../../contributors)

## License

Attribution-ShareAlike 4.0 International. Please see [License File](LICENSE.md) for more information.
