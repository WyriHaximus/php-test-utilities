# set all to phony
SHELL=bash

.PHONY: *

COMPOSER_SHOW_EXTENSION_LIST=$(shell composer show -t | grep -o "\-\-\(ext-\).\+" | sort | uniq | cut -d- -f4- | tr -d '\n' | grep . | sed  '/^$$/d' | xargs | sed -e 's/ /, /g' | tr -cd '[:alnum:],' | sed 's/.$$//')
SLIM_DOCKER_IMAGE=$(shell php -r 'echo count(array_intersect(["gd", "vips"], explode(",", "${COMPOSER_SHOW_EXTENSION_LIST}"))) > 0 ? "" : "-slim";')
PHP_VERSION:=$(shell docker run --rm -v "`pwd`:`pwd`" jess/jq jq -r -c '.config.platform.php' "`pwd`/composer.json" | php -r "echo str_replace('|', '.', explode('.', implode('|', explode('.', stream_get_contents(STDIN), 2)), 2)[0]);")
CONTAINER_NAME=$(shell echo "ghcr.io/wyrihaximusnet/php:${PHP_VERSION}-nts-alpine${SLIM_DOCKER_IMAGE}-dev")
COMPOSER_CACHE_DIR=$(shell composer config --global cache-dir -q || echo ${HOME}/.composer-php/cache)
COMPOSER_CONTAINER_CACHE_DIR=$(shell docker run --rm -it ${CONTAINER_NAME} composer config --global cache-dir -q || echo ${HOME}/.composer-php/cache)

ifneq ("$(wildcard /.you-are-in-a-wyrihaximus.net-php-docker-image)","")
    IN_DOCKER=TRUE
else
    IN_DOCKER=FALSE
endif

ifeq ("$(IN_DOCKER)","TRUE")
	DOCKER_RUN:=
else
	DOCKER_RUN:=docker run --rm -it \
		-v "`pwd`:`pwd`" \
		-v "${COMPOSER_CACHE_DIR}:${COMPOSER_CONTAINER_CACHE_DIR}" \
		-w "`pwd`" \
		${CONTAINER_NAME}
endif

ifneq (,$(findstring icrosoft,$(shell cat /proc/version)))
    THREADS=1
else
    THREADS=$(shell nproc)
endif

include vendor/wyrihaximus/makefiles/includes/All.mk
include vendor/wyrihaximus/makefiles/includes/PHP.mk
include vendor/wyrihaximus/makefiles/includes/Shell.mk
include vendor/wyrihaximus/makefiles/includes/Help.mk
include vendor/wyrihaximus/makefiles/includes/TaskFinders.mk
