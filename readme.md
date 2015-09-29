# Settings - Laravel Package

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

This package allows to easy manage global / user settings. Initial data structure is located in config files
and is mirrored to db after first usage.

## Install

Via Composer

``` bash
composer require code4interactive/settings
php artisan vendor:publish --provider="Code4\Settings\SettingsServiceProvider"
php artisan migrate
```

## Usage

``` php
// config/global.php
return [
    foo: "bar";
];

// config/global_user.php
return [
    foo: "baz";
];

$settings = new Code4/Settings/SettingsFactory(['global', 'global_user'], $user_id, $prefix, (bool) $lazyLoading);
$settings->get('global.foo');  //returns: baz

//Without inheritance
$settings->get('global.foo', false);  //returns: bar
```


### Inheritance

Use null or 'inherit' keyword in user settings to inherit from parent.

User config file has to have _user suffix.

## Testing

``` bash
composer test
```

## Credits

- [CODE4][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/code4interactive/settings.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/code4interactive/settings.svg?style=flat-square
[ico-license]: https://img.shields.io/github/license/mashape/apistatus.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/code4interactive/settings/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/code4interactive/settings.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/code4interactive/settings

[link-travis]: https://travis-ci.org/code4interactive/settings
[link-downloads]: https://packagist.org/packages/code4interactive/settings
[link-author]: https://github.com/code4interactive
[link-contributors]: ../../contributors

