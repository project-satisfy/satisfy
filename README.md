# Satisfy

[Satis Composer repository manager](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md) with a simple web UI.

## Introduction

Satisfy provides:

* a Web UI: A CRUD to your manage satis configuration file
* a simple security layer with Google Open ID
* Satis itself

Satisfy ease your satis configuration management. It provides simple web UI over Satis to avoid a hand-editing of the satis.json configuration file. It's secured with google open ID and you can apply an google apps organization constraint (check `app/config.php.dist`).

## How it works ?

Basically, it just reads/writes the satis.json file and provides a web CRUD.

* On each HTTP request, the satis.json is loaded.
* If a write occurs in this file, a backup is made in app/data/

## Installation

### Get composer

* Download composer `wget http://getcomposer.org/composer.phar
* Install `php composer.phar install create-project ludofleury/satisfy --stability=dev`

### Satis configuration

Define your [default/existing satis configuration](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md).

By default, the `satis.json` file is expected at the project root.

### Application configuration

* Define your configuration `cp app/config.php.dist app/config.php`
* Customize `app/config.php` according to your needs.

## Satis packages definition generation

You still need to generate your packages definitions as mentioned in Satis documentation.
Satsify just provide a bin shortcut in bin/satis.

## Known limitation

Since it's an ultra-KISS project, it doesn't handle race conditions with several simultaneous writes.
This could be avoided by extending the project with a simple SQLite layer for example, but then you have to manage the auto-generation of the satis.json file...

## Author

Ludovic Fleury - <ludo.fleury@gmail.com> - <http://twitter.com/ludofleury>

## Credits

[KnpLabs](https://github.com/KnpLabs) and @ubermuda for the really KISS open-id layer in [the marketplace repository](https://github.com/KnpLabs/marketplace)

## License

Satisfy is licensed under the MIT License - see the LICENSE file for details
