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

Run the following to install dependencies with yarn.

```shell name="assets-install"
docker compose run --rm node yarn install
```

Run the following to continuesly build assets uppon file changes.

```shell name="assets-watch"
docker compose run --rm node yarn watch
```

Run the following to build assets once.

```shell name="assets-build"
docker compose run --rm node yarn build
```

### Site installation

Run the following commands to set up the site. This will run a normal Drupal site installation with the existing
configuration that comes with this project.

```shell name="site-up"
itkdev-docker-compose up --detach
itkdev-docker-compose composer install
itkdev-docker-compose drush site-install --existing-config --yes
```

When the installation is completed, that admin user is created and the password for logging in the outputted. If you
forget the password, use drush uli command to get a one-time-login link (not the uri here only works if you are using
trafik).

```shell name="site-login"
itkdev-docker-compose drush --uri="https://givdinstemme.local.itkdev.dk/" user:login
```

### Access the site

If you are using out `itkdev-docker-compose` simple use the command below to åbne the site in you default browser.

```shell name="site-open"
itkdev-docker-compose open
```

Alternatively you can find the port number that is mapped nginx container that server the site at `http://0.0.0.0:PORT`
by using this command:

```shell
open "http://$(docker compose port nginx 8080)"
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

## Production setup

@todo Write this section.

## OpenID Connect

We use the [OpenID Connect](https://www.drupal.org/project/openid_connect) for OpenID Connect.

### Development

During development, we use [OpenId Connect Server Mock](https://github.com/Soluto/oidc-server-mock) to test OpenID
Connect authentication, and this is reflected in the default OIDC configuration.

Run

```shell
itkdev-docker-compose --profile oidc up --detach
```

to start the OIDC mock along with the other stuff.

<details>
<summary>Updating the self-signed certificate</summary>

**Note**: This section is only kept as an internal note on how the self-signed certificate,
[`.docker/oidc-server-mock/cert/docker.pfx`](.docker/oidc-server-mock/cert/docker.pfx), is generated from our
self-signed development Traefik certificates. The certificate is committed to Git and should only be updated if our
Traefik certificates are ever updated.

```shell name=generate-mock-pfx-certificate
cert_path="$(dirname $(dirname $(which itkdev-docker-compose)))/traefik/ssl"
openssl pkcs12 -export -out .docker/oidc-server-mock/cert/docker.pfx -inkey $cert_path/docker.key -in $cert_path/docker.crt -passout pass:mock

openssl pkcs12 -in .docker/oidc-server-mock/cert/docker.pfx -passin pass:mock -passout pass: -info
```

</details>

### Production

For production, we override (some) OpenID Connect configuration (rather than ignoring config) in `settings.local.php`:

``` php
// settings.local.php
// …

// https://idp-citizen.givdinstemme.srvitkstgweb01.itkdev.dk/.well-known/openid-configuration
$config['openid_connect.client.generic']['settings']['client_id'] = '…';
$config['openid_connect.client.generic']['settings']['client_secret'] = '…';

// Get these from your OIDC Discovery document.
$config['openid_connect.client.generic']['settings']['authorization_endpoint'] = '…';
$config['openid_connect.client.generic']['settings']['token_endpoint'] = '…';
$config['openid_connect.client.generic']['settings']['end_session_endpoint'] = '…';
```

### Coding standards

``` shell name=coding-standards-composer
docker compose run --rm phpfpm composer install
docker compose run --rm phpfpm composer normalize
```

``` shell name=coding-standards-php
docker compose run --rm phpfpm composer install
docker compose run --rm phpfpm composer coding-standards-apply/phpcs
docker compose run --rm phpfpm composer coding-standards-check/phpcs
```

``` shell name=coding-standards-twig
docker compose run --rm phpfpm composer install
docker compose run --rm phpfpm composer coding-standards-apply/twig-cs-fixer
docker compose run --rm phpfpm composer coding-standards-check/twig-cs-fixer
```

``` shell name=coding-standards-markdown
docker run --platform linux/amd64 --rm --volume "$PWD:/md" peterdavehello/markdownlint markdownlint $(git ls-files *.md) --fix
docker run --platform linux/amd64 --rm --volume "$PWD:/md" peterdavehello/markdownlint markdownlint $(git ls-files *.md)
```

```shell name="coding-standards-assets"
docker compose run --rm node yarn install
docker compose run --rm node yarn coding-standards-apply
docker compose run --rm node yarn coding-standards-check
```
