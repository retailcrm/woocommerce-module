FILE = $(TRAVIS_BUILD_DIR)/VERSION
VERSION = `cat $(FILE)`

all: svn_clone svn_push remove_dir

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
