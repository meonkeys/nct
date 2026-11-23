# SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

# The gold standard: if `make test` passes on your machine, the build is good.
# `make setup` needs the network once (docker pulls + composer/npm installs);
# after that, everything runs offline.

# Run containers as the host user so files written into the bind-mounted
# repo (composer/, node_modules/) aren't owned by root. HOME=/tmp gives
# psalm/npm a writable cache dir.
RUN = docker run --rm --user "$$(id -u):$$(id -g)" -e HOME=/tmp -v "$$PWD":/app -w /app

.PHONY: setup test lint cs-fix assets package dev-up dev-down dev-enable

# Files/dirs that are only for dev/test/build, not needed to run the app.
PACKAGE_EXCLUDE = .git .gitignore .dockerignore .php-cs-fixer.cache \
	.php-cs-fixer.dist.php .phpunit.result.cache .local-creds.json \
	AGENTS.md Dockerfile Makefile build composer docker-compose.yml \
	node_modules package-lock.json package.json phpunit.xml psalm.xml \
	src tests webpack.config.js *.swp

setup:
	docker build --target test -t nct-test .
	$(RUN) nct-test composer install
	docker build --target assets -t nct-assets .
	$(RUN) nct-assets npm clean-install

test:
	$(RUN) nct-test composer/bin/phpunit

lint:
	$(RUN) nct-test composer lint:lint
	$(RUN) nct-test composer cs:check
	$(RUN) nct-test composer/bin/psalm.phar --threads=1 --no-diff

cs-fix:
	$(RUN) nct-test composer cs:fix

assets:
	$(RUN) nct-assets npm run build

# Builds an unsigned app tarball for manual install on a real Nextcloud
# server (Apps > upload). Vendors production-only PHP deps fresh (the repo's
# own composer/ has dev tooling like psalm/phpunit that shouldn't ship).
package: assets
	rm -rf build
	mkdir -p build/nct
	rsync -a $(foreach x,$(PACKAGE_EXCLUDE),--exclude='$(x)') ./ build/nct/
	docker build --target test -t nct-test .
	docker run --rm --user "$$(id -u):$$(id -g)" -e HOME=/tmp -v "$$PWD/build/nct":/app -w /app \
		nct-test composer install --no-dev --optimize-autoloader --no-interaction
	tar -czf build/nct.tar.gz -C build nct
	@echo "Package written to build/nct.tar.gz"

dev-up:
	docker compose up -d

dev-down:
	docker compose down

dev-enable:
	# Docker bind-mounts custom_apps/nct, which auto-creates the custom_apps/
	# parent dir as root -- that leaves www-data unable to install other apps
	# (e.g. Contacts) into it. Fix ownership of the dir itself (not -R: nct/
	# is our host bind mount and must keep host-user ownership).
	docker compose exec --user root nextcloud chown www-data:www-data /var/www/html/custom_apps
	docker compose exec --user www-data nextcloud php occ app:enable nct
