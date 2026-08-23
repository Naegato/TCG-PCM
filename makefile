WITH_DOCKER?=1
COMPOSE=$(shell which docker) compose

ifndef env
env=dev
endif

ifeq ($(WITH_DOCKER), 1)
	PHP=$(COMPOSE) exec php
else
	PHP=cd api &&
endif

CONSOLE=$(PHP) php bin/console --env=$(env)
COVERAGE_DIR=var/phpunit

TEST_REPLAY_FOLDER = api/tests/GameReplay/resources
TEST_REPLAY_FOLDER_REL = $(TEST_REPLAY_FOLDER:api/%=%)

# ===== Dev =====

up:
	$(COMPOSE) up -d --build --remove-orphans
	$(PHP) composer install
	@echo "Waiting for database..."
	@$(PHP) bash -c 'until echo > /dev/tcp/db/3306 2>/dev/null; do sleep 1; done'
	@$(PHP) sh -c 'test -f config/jwt/private.pem || php bin/console lexik:jwt:generate-keypair -n'
	$(CONSOLE) doctrine:schema:update --force -n

install:
	$(COMPOSE) exec php composer install

card-list:
	$(CONSOLE) app:update:card-list

jwt:
	$(CONSOLE) lexik:jwt:generate-keypair --overwrite -n

fixtures:
	$(CONSOLE) doctrine:fixtures:load -n

dbReset:
	$(CONSOLE) doctrine:database:drop --force -n --if-exists
	$(CONSOLE) doctrine:database:create -n
	$(CONSOLE) doctrine:migrations:migrate -n --allow-no-migration
	$(CONSOLE) doctrine:schema:update --force -n
	$(MAKE) fixtures

setup-tests:
	$(MAKE) jwt
	$(MAKE) dbReset env=test

tests:
	@$(PHP) rm -rf var/cache/test
	$(PHP) bin/phpunit

tests-coverage:
	$(MAKE) remove-cache
	$(PHP) bin/phpunit --coverage-html=$(COVERAGE_DIR)

tests-ci:
	$(MAKE) remove-cache
	$(PHP) bin/phpunit \
		--log-junit var/junit.xml \
		--coverage-clover var/coverage.xml

tests-replay:
	$(PHP) bin/phpunit --group replay

remove-cache:
	@rm -rf api/var

clean:
	@rm -rf api/var
	@rm -rf front/.next front/node_modules
	docker builder prune -f

# ===== Build =====

build-api:
	docker build --no-cache -t tcg-api:prod --target prod ./api

build-api-dev:
	docker build --no-cache -t tcg-api:dev --target dev ./api

build-front:
	docker build --no-cache -t tcg-front:prod --target prod ./front

build-front-dev:
	docker build --no-cache -t tcg-front:dev --target dev ./front

pull:
	docker pull ghcr.io/fan2shrek/tcg/api:latest
	docker pull ghcr.io/fan2shrek/tcg/frontend:latest
	docker pull ghcr.io/fan2shrek/tcg/db-backup:latest
	docker pull mariadb:11.8.2
	docker pull redis:7
	docker pull dunglas/mercure
	docker pull amir20/dozzle:latest
	docker pull infisical/infisical:latest-postgres
	docker pull postgres:16-alpine

# ===== Code quality =====

format:
	$(PHP) vendor/bin/mago format

format-dry-run:
	$(PHP) vendor/bin/mago format --dry-run

format-check:
	$(PHP) vendor/bin/mago format --check

lint:
	$(PHP) vendor/bin/mago lint

lint-fix:
	$(PHP) vendor/bin/mago lint --fix --unsafe

symfony-lint:
	$(CONSOLE) lint:container
	$(CONSOLE) debug:container --deprecations

stan:
	$(PHP) vendor/bin/mago analyze

regenerate-tests-replay:
	@for file in $(TEST_REPLAY_FOLDER)/*.php; do \
		name=$$(basename "$$file" .php); \
		echo "Regenerating $$name..."; \
		id=$$( ( $(CONSOLE) app:import:game-replay $(TEST_REPLAY_FOLDER_REL)/$$name.php ) | tail -n1 | tr -d '\r' ); \
		output=$$( ( $(CONSOLE) app:export:game-replay $$id --test ) 2>&1 ); \
		if [ $$? -ne 0 ]; then \
			reason=$$(echo "$$output" | grep -m1 -oE '[A-Za-z0-9 "._-]+ not found' || echo 'export failed'); \
			echo "  skip $$name: $$reason"; \
			continue; \
		fi; \
		mv $(TEST_REPLAY_FOLDER)/replay-$$id.php $$file; \
	done
