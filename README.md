# Collectme V2

[![.github/workflows/tests.yml](https://github.com/grueneschweiz/collectme/actions/workflows/tests.yml/badge.svg)](https://github.com/grueneschweiz/collectme/actions/workflows/tests.yml)
[![Crowdin](https://badges.crowdin.net/collectme/localized.svg)](https://crowdin.com/project/collectme)

The community loves to promise signatures in pledges. We love signatures. This WordPress plugin helps to push the
community to fulfill their promises. It mixes some gamification elements with pressure by control, and it makes
achievements visible to the community.

While a first version was developed for the [@jungegruene](https://github.com/jungegruene), this is a complete rewrite,
that is here to grow.

## User Guide

For folks that just want to use employ the plugin on their site.

### Setup

1. Grab the [latest release](https://github.com/grueneschweiz/collectme/releases/latest/download/collectme.zip) and
   install it like any other plugin.
2. Head over to `Settings > Collectme` and create a new cause.
3. Check out the causes settings on the same page.
4. Copy the shortcode and paste it into your post or page.
5. Visit the post or page and follow the instructions.

### Generating Links that Log-in the Users Automatically

1. Populate the `wp_collectme_account_tokens` table with the data of the users you want to log in automatically.
   The `token` must contain exactly 64 alpha-numeric characters (e.g. SHA256 hash).
2. You may then send the users their personal link, which must look like follows:
   `https://example.com/post-with-collectme-shortcode?action=create&email=<email>&token=<token>`

**Variant:** You may also use the `collectme_get_account_token` filter and craft your own token validation function.
See [/docs/link-auth-with-mailchimp.md](/docs/link-auth-with-mailchimp.md) for an example.

### Adding Goals from External Data

The action `collectme_after_user_setup` is built for this purpose. It fires every time a user is added to a cause.

Example:

```php
add_action( 'collectme_after_user_setup', function( 
        \Collectme\Model\Entities\User $user,
        string $groupUuid, 
        string $causeUuid 
    ) {
    // add your logic to grab the objective data from an external source
    // assign the goal to $count, a string identifying the source to $source
    $count = 123;
    $source = 'Form XY';
    
    $objective = new \Collectme\Model\Entities\Objective(
        null,
        $count,
        $groupUuid,
        $source
    );
    
    try {
        $objective->save();
    } catch (\Collectme\Exceptions\CollectmeDBException $e) {
        // it's possibly ok to just ignore the error as the
        // user will be prompted by the application to set a
        // goal if he hasn't got one yet.
    }
}, 10, 3 );
```

### Email Notifications

The plugin can send the following email notifications:

**Collection reminders**:

- **Start collecting reminder** to users that have created an account but haven't entered any signatures yet.
- **Continue collecting reminder** to users that haven't entered any signatures for some time (see the [Timing](#timing)
  section below) and haven't reached their goal. If no goal was set, no reminder is sent.

**Goal related**:

- **Thank you for adding a personal goal** to users that have added their first goal.
- **Thank you for upgrading your personal goal** for users that raised their goal.
- **Thank you for achieving your personal goal** for users that entered at least as many signatures as their personal
  goal.
- **Thank you for achieving the final goal** for users that reached the highest goal.
- **Thank you for achieving your personal goal and upgrading it afterwards** to users that reached their goal and have
  set a higher one after.

#### Timing

There is a section **Scheduled E-Mails** on the settings page of the plugin that controls the **timing** of the emails.
A good starting point is 21 days for the reminder emails and 24 hours for the goal related emails. If the fields are
left blank, no emails will be sent.

The mail scheduler also respects the **collection start date** and the **collection end date**. No emails are sent
before the start and after the end date. **If the dates are left blank, the mailer won't stop sending emails.**

#### Customizing the emails

You can customize the email subject and body using the string override feature on the plugin's settings page.


## Dev Guide

For the cool kids that want to contribute :sunglasses:

### Architecture

The plugin is primarily built as single page application (SPA) using Vue.js and the WordPress REST API.

The REST API is specified in [OpenAPI 3.0](https://spec.openapis.org/oas/v3.0.3.html) format (the specification:
[docs/api/rest-api.yaml](docs/api/rest-api.yaml)). It is [JSON:API Spec](https://jsonapi.org/format/1.0/) compliant.

The settings page and the SPAs home are classic HTML pages and not Vue components. The SPAs home also handles part of
the login process (see [docs/uml/login-flow.puml](docs/uml/login-flow.puml)).

### Vue JS

We use the standard installation of [Vue3](https://vuejs.org) with [Typescript](https://www.typescriptlang.org/),
[Vue Router 4](https://router.vuejs.org), [Pinia](https://pinia.vuejs.org/) for state management and
[Vite](https://vitejs.dev) for the build setup.

The sources live in [/app](/app) and the build files are output to [/dist](/dist).

#### Asset loading

To get seamless integration of the Vuejs dev environment with WordPress, we had to get messy with the asset loading. It
works as follows:

##### Non dev mode

Unless you set `define( 'SCRIPT_DEBUG', true );` in `wp-config.php` the static built assets from [/dist](/dist) are
served, as in production.

Run `docker-compose run node yarn build` to rebuild the [/dist](/dist) files.

##### Dev mode

To enable dev mode, add `define( 'SCRIPT_DEBUG', true );` to your `wp-config.php`. In this setup this is already the
case, via the `WORDPRESS_CONFIG_EXTRA` environment variable in [docker-compose.yml](/docker-compose.yml).

In dev mode, the vue scripts are the loaded directly from the [vite](https://vitejs.dev/) dev server, which runs under
[localhost:3000](http://localhost:3000) (cf. [docker-compose.yml](/docker-compose.yml)). If you need to change the
hostname or port, use the `NODEJS_DEV_SERVER_BASE_URL` environment variable in
the [docker-compose.yml](/docker-compose.yml).

See [AssetLoader::getScriptUrls()](/src/Misc/AssetLoader.php) for further details. Yes, it is hacky :)

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

#### Testing

Is set up ([vitest](https://vitest.dev) and [cypress](https://www.cypress.io)) but not used.

#### Node

All JS building in done with [Yarn](https://yarnpkg.com) in the node container.

```
# install dependencies
docker-compose run node yarn install

# build for production
docker-compose run node yarn run build
```

See [app/package.json](app/package.json) for details.

### WordPress

To get good IDE integration and nice tooling with [Composer](https://getcomposer.org) Autoloading, the PHP parts of the
plugin use [PSR-4](https://www.php-fig.org/psr/psr-4/) and [PSR-12](https://www.php-fig.org/psr/psr-12/) instead of the
[WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

For the sake of testability, we use dependency injection with [PHP-DI](https://php-di.org/). Testing is done with
[PHPUnit](https://phpunit.readthedocs.io/en/9.5/).

The plugin need at least PHP 8.1.

#### Testing

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

#### WordPress-Cli

The WordPress container also contains the [WP-CLI](https://make.wordpress.org/cli/handbook/). Currently it is only used
to extract the plugin translation template (`.pot` file) and to compile the `.mo` files:

```
# extract translations
docker-compose run wordpress bash -c 'XDEBUG_MODE=off php -d memory_limit=1G $(which wp) --allow-root i18n make-pot wp-content/plugins/collectme/ wp-content/plugins/collectme/languages/collectme.pot --slug=collectme --domain=collectme --exclude=tmp,vendor --skip-js --skip-block-json --skip-theme-json && chown 1000:1000 wp-content/plugins/collectme/languages/collectme.pot'

# generate mo files
docker-compose run wordpress bash -c 'XDEBUG_MODE=off $(which wp) --allow-root i18n make-mo wp-content/plugins/collectme/languages'
```

#### Composer

Dependecies are managed with [Composer](https://getcomposer.org). To install the dependencies, run:

```
docker-compose run wordpress composer --working-dir=wp-content/plugins/collectme install
```

### Mailhog

Mails sent by the Plugin are caught by [Mailhog](https://mailhog.dev/) and accessible under
[localhost:8020](http://localhost:8020).

### Swagger

#### Editor

In browser editor and preview for the [openapi](https://www.openapis.org) definition of our
[rest-api](/docs/api/rest-api.yaml).

```
docker-compose run swagger-editor
```

Visit [localhost:8030](http://localhost:8030)

Helpful resources:

- [OpenApi Guide](https://swagger.io/docs/specification/about/)
- [OpenApi Spec](https://spec.openapis.org/oas/v3.0.0) (we use version 3.0.0)
- [JSON:API Spec](https://jsonapi.org/format/1.0/)

#### Codegen

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

### L10N - Crowdin

Localization is done with gettext and [Crowdin](https://crowdin.com/project/collectme). The workflow:

1. Use translation functions as usual (
   see [How to Internationalize Your Plugin](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/))
2. Create a pull request
3. The translation template (.pot file) is generated and uploaded to Crowdin by the GitHub
   action [l10n.yml](/.github/workflows/l10n.yml).
4. Translate in [Crowdin](https://crowdin.com/project/collectme)
5. Merge pull request into main
6. The translations are downloaded from Crowdin, mo-files are compiled and committed to by the GitHub
   action [l10n.yml](/.github/workflows/l10n.yml).

See also [crowdin.yml](/crowdin.yml).

#### Translate JS

- use [app/src/utility/i18n.ts](/app/src/utility/i18n.ts) as translation function
- provide the strings in [languages/app-strings.php](/languages/app-strings.php)
