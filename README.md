# Satisfy

Satis Composer repository manager with a simple web UI.

## Introduction

Satisfy ease your satis configuration management. It provides simple web UI over Satis to avoid a hand-editing of the satis.json file. It's shipped with Satis, so you don't need anyting else.

## How it works ?

Basically, it just reads/writes the satis.json file and provides a web CRUD.

    1. On each HTTP request, the satis.json is loaded.
    2. If a write occurs in this file, a backup is made in app/data/

## Installation

### Get composer

    1. Download composer `wget http://getcomposer.org/composer.phar
    2. Install `php composer.phar create-project ludofleury/satisfy --stability=dev`

### Satis configuration

Define your [default/existing satis configuration](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md).

By default, the `satis.json` file is expected at the project root.

### Application configuration

    1. Define your configuration `cp app/config.php.dist app/config.php`
    2. Customize `app/config.php` according to your needs.

## Known limitation

Since it's an ultra-KISS project, it doesn't handle race conditions with several simultaneous writes.
This could be avoided by extending the project with a simple SQLite layer for example, but then you have to manage the auto-generation of the satis.json file...

## Author

Ludovic Fleury - <ludo.fleury@gmail.com> - <http://twitter.com/ludofleury>

## Credits

[KnpLabs](https://github.com/KnpLabs) and @ubermuda for the really KISS open-id layer in [the marketplace repository](https://github.com/KnpLabs/marketplace)

## License

Satisfy is licensed under the MIT License - see the LICENSE file for details
