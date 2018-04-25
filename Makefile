FILE = $(TRAVIS_BUILD_DIR)/VERSION
VERSION = `cat $(FILE)`

all: svn_co prepare deploy remove_dir

svn_co:
	mkdir /tmp/svn_plugin_dir
	svn co $(SVNREPOURL) /tmp/svn_plugin_dir

prepare: /tmp/svn_plugin_dir
	svn copy /tmp/svn_plugin_dir/trunk /tmp/svn_plugin_dir/tags/$(VERSION)
	rm -rf /tmp/svn_plugin_dir/trunk/*
	cp -R $(TRAVIS_BUILD_DIR)/src/* /tmp/svn_plugin_dir/trunk

deploy: /tmp/svn_plugin_dir/tags
	svn ci /tmp/svn_plugin_dir -m $(VERSION) --username $(USERNAME) --password $(PASSWORD)

remove_dir:
	rm -rf /tmp/svn_plugin_dir
