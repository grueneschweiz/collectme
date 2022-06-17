# Collectme V2

## Architecture

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