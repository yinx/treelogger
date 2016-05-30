# Treelogger

[![StyleCI](https://styleci.io/repos/57818067/shield)](https://styleci.io/repos/59567682)

# For laravel 4 switch to the laravel-4 branch.

## Installation

Require this package with composer:

```sh
composer require yinx/treelogger
```

Next add the ServiceProvider to the providers array in config/app.php

```sh
Yinx\TreeLogger\TreeLoggerServiceProvider::class,
```


## Usage

### Adding the log-lines

```
php artisan controller:logs:add
```

Append --v to get a verbose output.


### Removing the log-lines

```
php artisan controller:logs:remove
```

Append --v to get a verbose output.

## Credits

### Contributors

- Thanks to Evert Arnould (Contact@evertarnould.be)



