# Satisfy [![Build Status](https://travis-ci.org/ludofleury/satisfy.svg)](https://travis-ci.org/ludofleury/satisfy)

[Satis Composer repository manager](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md) with a simple web UI.

<p>
  <img src="http://project-satisfy.github.io/satisfy/images/list.png" width="280" height="235" alt="Satis: list composer repositories"/>
  <img src="http://project-satisfy.github.io/satisfy/images/create.png" width="280" height="235" alt="Satis: add a new composer repository"/>
  <img src="http://project-satisfy.github.io/satisfy/images/update.png" width="280" height="235" alt="Satis: update an existing composer repository"/>
</p>

## Introduction

Satisfy provides:

* a Web UI: A CRUD to manage your satis configuration file
* a simple security layer with a login form
* a webhook endpoints for the most popular version control systems
* Satis itself

Satisfy ease your satis configuration management. It provides a simple web UI over Satis to avoid a hand-editing of the satis.json configuration file. All repositories from a composer.lock file can also be imported via upload.


## How it works?

Basically, it just reads/writes the satis.json file and provides a web CRUD.

* On each HTTP request, the satis.json is loaded.
* If a write occurs in this file, a backup is made in var/satis/

## Installation

### Composer

* Clone project `git clone https://github.com/project-satisfy/satisfy.git`
* Download composer `wget http://getcomposer.org/composer.phar`
* Install `php composer.phar install -n`

### Satis configuration

* Either define your [default/existing satis configuration](http://getcomposer.org/doc/articles/handling-private-packages-with-satis.md)
* or use interactice satis CLI tool `./bin/satis init`
* or submit form at /admin/configuration

By default, the `satis.json` file is expected at the project root, but You can set another path under the parameter `satis_filename`.

### Application configuration

* Customize `app/config/parameters.yml` according to your needs.

### Security

You can restrict the access setting `admin.auth` parameter (in `app/config/parameters.yml`) to `true`. Set authorized users in `admin.users` config array.

## Run Satisfy

Create a web server pointing to the `public` directory. Browse to »/admin/« to manage the `satis.json`. To serve the `package.json` it is required to run Satis first.

### Satis packages definition generation

Since Satisfy does only manages the Satis configuration file, it is necessary to build the package definitions using Satis.

A bin shortcut to Satis is already included in Satisfy, so run the following command to generate the files in the web folder.

 ```
./bin/satis build
```

### Automatically build a single package using WebHooks

For example, you can trigger a package generation for your BitBucket project by setting up a BitBucket webhook to connect back to [your-satis-url]/webhook/bitbucket every time you do a code push. This is more efficient than doing a full build and avoids you having to run full builds on a frequent schedule or logging in to the admin interface just to force a build.

## Prebuilt docker image

You can run satisfy using prebuilt docker image. Here is an example how to setup it.

1. Create dir for configuration files
2. Add parameters.yml file, can be copied from config/parameters.yml.dist
3. Add auth.json with all required composer authentication tokens
4. Add simple satis.json with basic information
5. Create docker-compose.yml using example below
6. Start containers `docker-compose up`
7. Access container shell `docker-compose exec php bash`
8. Run initial build `./bin/satis build`
9. Open satis page on `http://localhost:8000`

```
version: '3'
services:
  php:
    image: ghcr.io/project-satisfy/satisfy:latest
    ports:
      - "${APP_PORT:-8000}:80"
    volumes:
      - ./satis.json:/var/www/html/satis.json
      - ./parameters.yml:/var/www/html/config/parameters.yml
      - ./auth.json:/var/www/.composer/auth.json:ro
    environment:
      APP_ENV: ${APP_ENV:-dev}
      APP_DEBUG: ${APP_DEBUG:-1}
```

## Authors

* Ludovic Fleury - <ludo.fleury@gmail.com> - <http://twitter.com/ludofleury>
* Julius Beckmann - <satisfy@h4cc.de> - <https://twitter.com/h4cc>
* Ramūnas Dronga - <satisfy@ramuno.lt>

## License

Satisfy is licensed under the MIT License - see the LICENSE file for details

