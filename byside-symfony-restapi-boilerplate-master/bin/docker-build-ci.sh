#!/bin/bash

docker buildx build \
--platform linux/amd64,linux/arm64 \
--push \
--provenance=false \
--ssh default \
--add-host=git.byside-nos.local:10.36.2.32 "$@"
