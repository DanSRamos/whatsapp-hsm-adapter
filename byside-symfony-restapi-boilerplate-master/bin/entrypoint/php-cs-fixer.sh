#!/bin/bash

set -e

while [[ $# -gt 0 ]]; do
  case "$1" in
    --branch)
      BRANCH="$2"
      shift 2
      ;;
    --compare-with)
      COMPARE_WITH="$2"
      shift 2
      ;;
    --ci)
      CI="--dry-run"
      shift 1
      ;;
    *)
      break
      ;;
  esac
done

function php-cs-fixer {
    /vendor/bin/php-cs-fixer fix -v \
    --ansi \
    --using-cache=no \
    --config=/app/.php-cs-fixer.dist.php \
    $CI "$@"
}


if [ -n "$BRANCH" ]; then
    git config --global --add safe.directory /app
    if [ -n "$COMPARE_WITH" ]; then
        CHANGED_PHP_FILES=$(git diff --diff-filter=d --name-only origin/"${BRANCH}"..."${COMPARE_WITH}" -- '*.php' ':!*vendor*')
    else
        CHANGED_PHP_FILES=$(git diff --diff-filter=d --name-only origin/"${BRANCH}"...HEAD -- '*.php' ':!*vendor*')
    fi

    if [ -z "$CHANGED_PHP_FILES" ]
    then
        echo "No PHP files were changed as of yet"
        exit 0
    fi

    php-cs-fixer $CHANGED_PHP_FILES
    exit $?
fi


php-cs-fixer "$@"
