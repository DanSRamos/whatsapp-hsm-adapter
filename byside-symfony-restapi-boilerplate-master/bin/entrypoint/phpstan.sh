#!/bin/bash

CHANGED_PHP_FILES=$(git diff --diff-filter=d --name-only origin/develop...HEAD -- '*.php')
SCRIPT_DIR_ABS=$(cd "$(dirname "${BASH_SOURCE[0]}")" &> /dev/null && pwd)

if [ -z "$CHANGED_PHP_FILES" ]
then
	echo "No PHP files were changed as of yet"
else
	php -d memory_limit=1G ${SCRIPT_DIR_ABS}/../../bin/phpstan analyse -c phpstan.neon --memory-limit=1G $CHANGED_PHP_FILES
fi
