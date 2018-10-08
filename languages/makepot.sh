#!/usr/bin/env bash

HOST_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
GUEST_DIR='/var/www/html/primary-category'

set -euxo pipefail

touch "$HOST_DIR/languages/primary-category.pot";

# note: using generic mode instead of plugin mode because we dont care about translating plugin metadata
docker run --rm \
    -v "$HOST_DIR/src:$GUEST_DIR/src" \
    -v "$HOST_DIR/languages/primary-category.pot:$GUEST_DIR/primary-category.pot" \
    sebwas/wp-i18n primary-category
