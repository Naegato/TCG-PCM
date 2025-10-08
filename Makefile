WITH_DOCKER?=1
COMPOSE=$(shell which docker) compose

ifeq ($(WITH_DOCKER), 1)
	PHP=$(COMPOSE) exec php
else
	PHP=
endif

CONSOLE=$(PHP) php bin/console

jwt:
	$(CONSOLE) lexik:jwt:generate-keypair --overwrite

fixtures:
	$(CONSOLE) doctrine:fixtures:load -n

format:
	$(PHP) vendor/bin/mago format

stan:
	$(PHP) vendor/bin/mago analyze
