# Collectme V2

## Shortcode

```
[collectme causeUuid='{uuid}' stringOverwritesJson='{json}']
```

## Architecture

### Vue JS

#### Components

Paths:

- General components live in [/app/src/components/base/](/app/src/components/base)
- Specific components [/app/src/components/specific/{routeName}/](/app/src/components/specific)
- Components with assets or specific child components are to be packed into a subdirectory with the name of the
  component (in `PascalCase`)

Naming:

- `PascalCase` file names
- General components names are prefixed with `Base`
- Components naming rules:
    - At least [two words](https://vuejs.org/style-guide/rules-essential.html#use-multi-word-component-names)
    - `The` prefix for components, that should only ever have a single active instance (e.g. `TheBaseOverlay`)
    - Tightly coupled or specific child components include the parent component name as prefix.
    - Start with the highest level words and put more specific ones after (e.g. `SearchButtonClear.vue`)


### Server side

- [PHP-DI](https://php-di.org/)
- Composer Autoloading
- PSR-4 & PSR-12 instead of WordPress Coding Standards

## Tooling

### Mailhog

### Node

### Composer

### WordPress-Cli

### Restful API

#### Swagger-Editor

In browser editor and preview for the [openapi](https://www.openapis.org)
definition of our [rest-api](/docs/api/rest-api.yaml).

```
docker-compose run swagger-editor
```

Visit [localhost:8030](http://localhost:8030)

Helpful resources:

- [OpenApi Guide](https://swagger.io/docs/specification/about/)
- [OpenApi Spec](https://spec.openapis.org/oas/v3.0.0) (we use version 3.0.0)
- [JSON:API Spec](https://jsonapi.org/format/1.0/)

#### Swagger-Codegen

- Generate stubs in [/gen](/gen)
- Generate API docs in [/docs/api/index.html](/docs/api/index.html)

```
docker-compose run swagger-codegen generate -i /tmp/swagger/input/rest-api.yaml -o /tmp/swagger/output -l php
docker-compose run swagger-codegen generate -i /tmp/swagger/input/rest-api.yaml -o /tmp/swagger/output -l typescript-axios
docker-compose run swagger-codegen generate -i /tmp/swagger/input/rest-api.yaml -o /tmp/swagger/output -l html2 && mv -f gen/index.html docs/api/index.html 
```

Helpful resources:

- `docker-compose run swagger-codegen langs`
- `docker-compose run swagger-codegen generate`
- [Swagger-Codegen](https://github.com/swagger-api/swagger-codegen) on GitHub

#### Prism

API mock server.

```
docker-compose up -d prism
```

Visit:

- [localhost:8040/users/link-auth?token=dc74835ae17fe4aa876d7838791ce9005151abf1aeabce922d472884214b1ee4](http://localhost:8040/users/link-auth?token=dc74835ae17fe4aa876d7838791ce9005151abf1aeabce922d472884214b1ee4)
- Or any other endpoint defined the [rest-api](/docs/api/rest-api.yaml).

## Quirks

### Vuejs dev environment + Wordpress

To get seamless integration of the Vuejs dev environment with WordPress, we had to get messy with the asset loading. It
works as follows:

#### Non dev mode

Unless you set `define( 'SCRIPT_DEBUG', true );` in `wp-config.php` the static built assets from [/dist](/dist) are
served, as in production.

Run `docker-compose run node yarn build` to rebuild the [/dist](/dist) files.

#### Dev mode

To enable dev mode, add `define( 'SCRIPT_DEBUG', true );` to your `wp-config.php`. In this setup this is already the
case, via the `WORDPRESS_CONFIG_EXTRA` environment variable in [docker-compose.yml](/docker-compose.yml).

In dev mode, the vue scripts are the loaded directly from the [vite](https://vitejs.dev/) dev server, which runs under
[localhost:3000](http://localhost:3000) (cf. [docker-compose.yml](/docker-compose.yml)). If you need to change the
hostname or port, use the `NODEJS_DEV_SERVER_BASE_URL` environment variable in
the [docker-compose.yml](/docker-compose.yml).

See [AssetLoader::getScriptUrls()](/src/Misc/AssetLoader.php) for further details. Yes, it is hacky :)