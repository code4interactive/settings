# Settings - Laravel Package

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

**Note:** Replace ```:author_name``` ```:author_username``` ```:author_website``` ```:author_email``` ```settings``` ```:package_description``` with their correct values in [README.md](README.md), [CHANGELOG.md](CHANGELOG.md), [LICENSE.md](LICENSE.md) and [composer.json](composer.json) files, then delete this line.

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what
PSRs you support to avoid any confusion with users and contributors.

## Install

Via Composer

``` bash
composer require code4interactive/settings
```

## Usage

``` php

$settings = new Code4/Settings/SettingsFactory(['global', 'global_user'], $user_id, $prefix, (bool) $lazyLoading);

$settings->get('global.variable');

```

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
https://img.shields.io/github/license/mashape/apistatus.svg
[link-packagist]: https://packagist.org/packages/code4interactive/settings

[link-travis]: https://travis-ci.org/code4interactive/settings
[link-downloads]: https://packagist.org/packages/code4interactive/settings
[link-author]: https://github.com/code4interactive
[link-contributors]: ../../contributors

