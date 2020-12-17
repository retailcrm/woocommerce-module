ROOT_DIR=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))
VERSION = `cat $(ROOT_DIR)/VERSION`
ARCHIVE_NAME = '/tmp/retailcrm-'$(VERSION)'.ocmod.zip'

.PHONY: test

svn_clone:
	mkdir /tmp/svn_plugin_dir
	svn co $(SVNREPOURL) /tmp/svn_plugin_dir --username $(USERNAME) --password $(PASSWORD) --no-auth-cache

svn_push: /tmp/svn_plugin_dir
	if [ ! -d "/tmp/svn_plugin_dir/tags/$(VERSION)" ]; then \
		svn delete /tmp/svn_plugin_dir/trunk/*; \
		rm -rf /tmp/svn_plugin_dir/trunk/*; \
		cp -R $(ROOT_DIR)/src/* /tmp/svn_plugin_dir/trunk; \
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
	mkdir coverage
	bash tests/bin/install.sh $(DB_NAME) $(DB_USER) $(DB_HOST) $(WP_VERSION) $(WC_VERSION) $(DB_PASS) $(SKIP_DB_CREATE)

test:
	phpunit -c phpunit.xml.dist

local_test: install
	phpunit -c phpunit.xml.dist

run_tests:
	docker-compose --no-ansi up -d --build mysql
	docker-compose --no-ansi run --rm --no-deps app make local_test
	docker-compose stop

coverage:
	wget https://phar.phpunit.de/phpcov-2.0.2.phar && php phpcov-2.0.2.phar merge coverage/ --clover coverage.xml

build_archive:
	zip -r $(ARCHIVE_NAME) ./src/*

