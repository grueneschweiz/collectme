# Collectme V2

## Shortcode

```
[collectme causeuuid='{uuid}']
```

## Testing

```
# install test environement
docker-compose run wordpress bash -c \
  'cd wp-content/plugins/collectme \
  && TMPDIR=$(pwd)/tmp bin/install-wp-tests.sh collectme_test root "" mysql latest'

# run tests
docker-compose run wordpress bash -c \
  'cd wp-content/plugins/collectme \
  && php vendor/bin/phpunit'
```

Set `PHP_IDE_CONFIG='serverName=Docker'` in your IDEs run configuration if you want to run tests directly in your IDE.
`serverName=Docker` corresponds to the server name configured in your IDE (`Settings > PHP > Servers > Name` for
PHPStorm).

Please note also, that the `WP_TESTS_DIR="$(pwd)/tmp/wordpress-tests-lib"` environment variable is set in 
[/phpunit.xml.dist](phpunit.xml.dist). It must point to the same directory as `TMPDIR`, you've used for installing the 
tests.

## Architecture

### Vue JS

#### Components

Paths:

- General components live in [/app/src/components/base/](/app/src/components/base)
- Specific components [/app/src/components/specific/{routeName}/](/app/src/components/specific)
- Components with assets or specific child components are to be packed into a subdirectory with the name of the
  component (in `PascalCase`)

Component Naming:

- `PascalCase` file names
- General components names are prefixed with `Base`
- Components naming rules:
    - At least [two words](https://vuejs.org/style-guide/rules-essential.html#use-multi-word-component-names)
    - `The` prefix for components, that should only ever have a single active instance (e.g. `TheBaseOverlay`)
    - Tightly coupled or specific child components include the parent component name as prefix.
    - Start with the highest level words and put more specific ones after (e.g. `SearchButtonClear.vue`)

HTML Class Naming:

- Wrapping container: `collectme-{component-name}` e.g. `collectme-the-base-overlay`
- Inner elements: `collectme-{component-name}__{specific-tag}` e.g. `collectme-the-base-overlay__title`

CSS:

- Don't use the styles `scoped` attribute (so themes can overwrite the plugins styles)

### Server side

- [PHP-DI](https://php-di.org/)
- Composer Autoloading
- PSR-4 & PSR-12 instead of WordPress Coding Standards

## Tooling

### Mailhog

### Node

```
# install
docker-compose run node yarn install

# build for production
docker-compose run node yarn run build
```

### Composer

```
docker-compose run wordpress composer --working-dir=wp-content/plugins/collectme install
```

### WordPress-Cli

Extract `.pot` file
```
docker-compose run wordpress bash -c 'XDEBUG_MODE=off php -d memory_limit=1G $(which wp) --allow-root i18n make-pot wp-content/plugins/collectme/ wp-content/plugins/collectme/languages/collectme.pot --slug=collectme --domain=collectme --exclude=tmp,vendor --skip-js --skip-block-json --skip-theme-json && chown 1000:1000 wp-content/plugins/collectme/languages/collectme.pot'
```

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

- Generate type definitions in [/app/src/models/generated](/app/src/models/generated)
  ```bash
  docker-compose run swagger-codegen generate -i /tmp/swagger/input/rest-api.yaml -o /tmp/swagger/output -l typescript-axios
  sudo chown -R $(id -u):$(id -g) gen
  sed -i '/import type/! s/import /import type /g' gen/models/*.ts
  sed -i -r 's/(\s)Date(\s)/\1string\2/g' gen/models/*.ts
  rsync -av --delete gen/models/ app/src/models/generated
  ```
- Generate API docs in [/docs/api/index.html](/docs/api/index.html)
  ```bash
  docker-compose run swagger-codegen generate -i /tmp/swagger/input/rest-api.yaml -o /tmp/swagger/output -l html2 
  sudo chown -R $(id -u):$(id -g) gen
  cp gen/index.html docs/api/index.html 
  ```

Helpful resources:

- `docker-compose run swagger-codegen langs`
- `docker-compose run swagger-codegen generate`
- [Swagger-Codegen](https://github.com/swagger-api/swagger-codegen) on GitHub

#### OpenAPI Schema to JSON Schema

- Converts the [OpenAPI 3.0](https://spec.openapis.org/oas/v3.0.3.html) definition to
  a [JSON Schema 4](http://json-schema.org/specification-links.html#draft-4) (which is recommended by WordPress)
- Currently, not containerized

```
npm install -g openapi2schema
```

Helpful resources:

- [OpenAPI Schema to JSON Schema](https://github.com/openapi-contrib/openapi-schema-to-json-schema)


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