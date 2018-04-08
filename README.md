# Satisfy [![Build Status](https://travis-ci.org/ludofleury/satisfy.svg)](https://travis-ci.org/ludofleury/satisfy)

[Satis Composer repository manager](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md) with a simple web UI.

<p>
  <img src="http://ludofleury.github.io/satisfy/images/list.png" width="280" height="235" alt="Satis: list composer repositories"/>
  <img src="http://ludofleury.github.io/satisfy/images/create.png" width="280" height="235" alt="Satis: add a new composer repository"/>
  <img src="http://ludofleury.github.io/satisfy/images/update.png" width="280" height="235" alt="Satis: update an existing composer repository"/>
</p>

## Introduction

Satisfy provides:

* a Web UI: A CRUD to manage your satis configuration file
* a simple security layer with a login form
* Satis itself

Satisfy ease your satis configuration management. It provides simple web UI over Satis to avoid a hand-editing of the satis.json configuration file. All repositories from a composer.lock file can also be imported via upload.


## How it works ?

Basically, it just reads/writes the satis.json file and provides a web CRUD.

* On each HTTP request, the satis.json is loaded.
* If a write occurs in this file, a backup is made in app/data/

## Installation

### Composer

* Download composer `wget http://getcomposer.org/composer.phar`
* Install `php composer.phar create-project playbloom/satisfy`

### Satis configuration

Define your [default/existing satis configuration](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md).

By default, the `satis.json` file is expected at the project root. You can either create file via command line or use web interface.

### Application configuration

* Customize `app/config/parameters.yml` according to your needs.

### Security

You can restrict the access setting `admin.auth` parameter (in `app/config/parameters.yml`) to `true`. Set authorized users in `admin.users` config array.

## Run Satisfy

Create a webserver pointing to the `web` directory. Browse to »/admin/« to manage the `satis.json`. To serve the `package.json` it is required to run Satis first.

### Satis packages definition generation

Since Satisfy does only manage the Satis configuration file, it is necessary to build the package definitions using Satis.

A bin shortcut to Satis is already included in Satisfy, so run the following command to generate the files in the web folder.

 ```
./bin/satis build
```

## Authors

* Ludovic Fleury - <ludo.fleury@gmail.com> - <http://twitter.com/ludofleury>
* Julius Beckmann - <satisfy@h4cc.de> - <https://twitter.com/h4cc>
* Ramunas Dronga - <satisfy@ramuno.lt>

## License

Satisfy is licensed under the MIT License - see the LICENSE file for details

