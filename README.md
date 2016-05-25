# Treelogger

[![StyleCI](https://styleci.io/repos/57818067/shield)](https://styleci.io/repos/59567682)

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

```
php artisan log:controller
```

Append --v to get a verbose output.

Append --rm to remove all the loglines.

## Credits

### Contributors

- Thanks to Evert Arnould (Contact@evertarnould.be)



