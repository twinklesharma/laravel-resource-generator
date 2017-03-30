# laravel-resource-generator
Generates a model, repository, controller and services
# Installation
Add laravelresource/resourcemaker as a requirement to composer.json :

```php
{
    "require": {
        "laravelresource/resourcemaker": "0.*"
    }
}
```
Update your packages with composer update or install with composer install.

You can also add the package using `composer require laravelresource/resourcemaker` and later specifying the version you want (for now, dev-master is your best bet).

#### Service Provider
`LaravelResource\ResourceMaker\ResourceGeneratorServiceProvider::class,`

And that's it! Start working with a awesome resource Generator!

## Using the generator

From the command line, run: 

    php artisan make:resource ModelName "attributes"

For the simplest example, let's create a new ```users``` resource:

    php artisan make:resource Users
