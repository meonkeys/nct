# SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# Same PHP version/extensions Nextcloud itself needs, so there is no second
# PHP version to keep in sync.
FROM nextcloud:34 AS test
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
ENTRYPOINT []

FROM node:24 AS assets
WORKDIR /app
