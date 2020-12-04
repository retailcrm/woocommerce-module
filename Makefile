FILE = $(TRAVIS_BUILD_DIR)/VERSION
VERSION = `cat $(FILE)`

.PHONY: test

before_script:
	bash tests/bin/install.sh $(DB_NAME) $(DB_USER) $(DB_HOST) $(WP_VERSION) $(WC_VERSION) $(DB_PASS) $(SKIP_DB_CREATE)

svn_clone:
	mkdir /tmp/svn_plugin_dir
	svn co $(SVNREPOURL) /tmp/svn_plugin_dir --username $(USERNAME) --password $(PASSWORD) --no-auth-cache

svn_push: /tmp/svn_plugin_dir
	if [ ! -d "/tmp/svn_plugin_dir/tags/$(VERSION)" ]; then \
		svn delete /tmp/svn_plugin_dir/trunk/*; \
		rm -rf /tmp/svn_plugin_dir/trunk/*; \
		cp -R $(TRAVIS_BUILD_DIR)/src/* /tmp/svn_plugin_dir/trunk; \
		svn copy /tmp/svn_plugin_dir/trunk /tmp/svn_plugin_dir/tags/$(VERSION) --username $(USERNAME) --password $(PASSWORD) --no-auth-cache; \
		svn add /tmp/svn_plugin_dir/trunk/* --force; \
		svn add /tmp/svn_plugin_dir/tags/$(VERSION)/* --force; \
		svn ci /tmp/svn_plugin_dir -m $(VERSION) --username $(USERNAME) --password $(PASSWORD) --no-auth-cache; \
	fi

remove_dir:
	rm -rf /tmp/svn_plugin_dir

compile_pot:
	msgfmt resources/pot/retailcrm-ru_RU.pot -o src/languages/retailcrm-ru_RU.mo
	msgfmt resources/pot/retailcrm-es_ES.pot -o src/languages/retailcrm-es_ES.mo

install:
	bash tests/bin/install.sh $(DB_NAME) $(DB_USER) $(DB_HOST) $(WP_VERSION) $(WC_VERSION) $(DB_PASS) $(SKIP_DB_CREATE)
ifeq ($(USE_COMPOSER),1)
	composer install
endif

test:
ifeq ($(USE_COMPOSER),1)
	vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist
else
	phpunit -c phpunit.xml.dist
endif

local_test: install
	phpunit -c phpunit.xml.dist

run_tests:
	docker-compose --no-ansi up -d --build mysql
	docker-compose --no-ansi run --rm --no-deps app make local_test
	docker-compose stop

phpcs-config:
	phpcs --config-set installed_paths vendor/phpcompatibility/php-compatibility

phpcomp: phpcs-config
	phpcs -i
	phpcs -s -p ./src --standard=PHPCompatibility --runtime-set testVersion 5.4-7.3
