#!/bin/bash

SCRIPT_DIR_ABS=$(cd "$(dirname "${BASH_SOURCE[0]}")" &> /dev/null && pwd)

php -d memory_limit=1G ${SCRIPT_DIR_ABS}/../../vendor/php-parallel-lint/php-parallel-lint/parallel-lint --exclude vendor .
