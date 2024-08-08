# Giv din stemme

## Local development

This project contains a docker compose setup build around [https://github.com/itk-dev/devops_itkdev-docker](https://github.com/itk-dev/devops_itkdev-docker)
which is a simple wrapper script around docker compose using [traefik](https://traefik.io/traefik/) as a revers proxy
to get FQDN to access the site.

If you do not want to use this wrapper, you can substitute the `itkdev-docer-compose` command with normal
`docker compose` remembering to load the right composer file using the `-f <file>` option.

This setup also assumes that you have a docker network shared with treafik, if you are not using the wrapper, use this
command to create the network first.

```shell
docker network create --driver=bridge --attachable --internal=false frontend
```

### Building assets for the frontend

Run `itkdev-docker-compose run node yarn watch` to continuesly build assets uppon file changes.

Run `itkdev-docker-compose run node yarn build` to build assets once.

### Site installation

Run the following commands to set up the site. This will run a normal Drupal site installation with the existing
configuration that comes with this project.

```shell
itkdev-docker-compose up -d
itkdev-docker-compose composer install
itkdev-docker-compose drush site-install minimal --existing-config -y
```

When the installation is completed, that admin user is created and the password for logging in the outputted. If you
forget the password, use drush uli command to get a one-time-login link (not the uri here only works if you are using
trafik).

```shell
itkdev-docker-compose drush uli --uri="http://givdinstemme.local.itkdev.dk/"
```

### Access the site

If you are using out `itkdev-docker-compose` simple use the command below to åbne the site in you default browser.

```shell
itkdev-docker-compose open
```

Alternatively you can find the port number that is mapped nginx container that server the site at `http://0.0.0.0:PORT`
by using this command:

```shell
docker compose port nginx 8080
```

### Drupal config

This project uses Drupal's configuration import and export to handle configuration changes and uses the
[config ignore](https://www.drupal.org/project/config_ignore) module to protect some of the site settings form being
overridden. For local and production configuration settings that you do not want to export, please use
`settings.local.php` to override default configuration values.

Export config created from drupal:

```shell
itkdev-docker-compose drush config:export
```

Import config from config files:

```shell
itkdev-docker-compose drush config:import
```

### Coding standards

@todo Add description about running and applying coding standards

## Production setup

@todo Write this section.
