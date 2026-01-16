A Boilerplate Project based in Symfony 5.1

## Install packages

```shell
bin/docker-compose-run.sh composer install
```

## Unit tests

Running Unit Tests without coverage

```shell
bin/docker-compose-run.sh unit-tests
```

## Unit tests coverage

```shell
bin/docker-compose-run.sh unit-tests-coverage
```

## PHP CS validation

```shell
bin/docker-compose-run.sh phpcs <FILE_PATH>
```

## PHPStan

```shell
bin/docker-compose-run.sh phpstan <FILE_PATH>
```

## PHP CS fixer
```shell
bin/docker-compose-run.sh php-cs-fixer <FILE_PATH>
```

## PHP CS fixer all Changed files
```shell
bin/docker-compose-run.sh php-cs-fixer-develop
```

## Lint

```shell
bin/docker-compose-run.sh lint
```

### Start dev environment

```shell
bin/start-dev.sh
```



