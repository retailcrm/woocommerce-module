FILE = $(TRAVIS_BUILD_DIR)/VERSION
VERSION = `cat $(FILE)`

all: svn_clone prepare svn_commit remove_dir

svn_clone:
	mkdir /tmp/svn_plugin_dir
	svn co $(SVNREPOURL) /tmp/svn_plugin_dir --username $(USERNAME) --password $(PASSWORD) --no-auth-cache

prepare: /tmp/svn_plugin_dir
	svn delete /tmp/svn_plugin_dir/trunk/*
	rm -rf /tmp/svn_plugin_dir/trunk/*
	cp -R $(TRAVIS_BUILD_DIR)/src/* /tmp/svn_plugin_dir/trunk
	svn copy /tmp/svn_plugin_dir/trunk /tmp/svn_plugin_dir/tags/$(VERSION) --username $(USERNAME) --password $(PASSWORD) --no-auth-cache

svn_commit: /tmp/svn_plugin_dir/tags
	svn add /tmp/svn_plugin_dir/trunk/* --force
	svn add /tmp/svn_plugin_dir/tags/$(VERSION)/* --force
	svn ci /tmp/svn_plugin_dir -m $(VERSION) --username $(USERNAME) --password $(PASSWORD) --no-auth-cache

remove_dir:
	rm -rf /tmp/svn_plugin_dir
