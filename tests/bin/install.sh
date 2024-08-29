#!/usr/bin/env bash
# Example https://raw.githubusercontent.com/wp-cli/scaffold-command/master/templates/install-wp-tests.sh

DB_NAME=$1
DB_USER=$2
DB_HOST=$3
DB_PASS=$4
WP_VERSION=$5
WC_VERSION=$6

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
  WP_TESTS_TAG="tags/$WP_VERSION"
elif [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
  WP_TESTS_TAG="trunk"
else
  # http serves a single offer, whereas https serves multiple. we only want one
  download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
  grep '[0-9]+\.[0-9]+(\.[0-9]+)?' /tmp/wp-latest.json
  LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
  if [[ -z "$LATEST_VERSION" ]]; then
    echo "Latest WordPress version could not be found"
    exit 1
  fi
  WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -ex

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp() {
  if [ -d $WP_CORE_DIR ]; then
    return;
  fi

  mkdir -p $WP_CORE_DIR
  local ARCHIVE_NAME="wordpress-$WP_VERSION"
  download https://wordpress.org/${ARCHIVE_NAME}.tar.gz  /tmp/wordpress.tar.gz
  tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
  download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php $WP_CORE_DIR/wp-content/db.php
  mkdir -p $WP_CORE_DIR/wp-content/plugins/woo-retailcrm/assets/default
  cp /code/src/assets/default/default_meta_fields.txt $WP_CORE_DIR/wp-content/plugins/woo-retailcrm/assets/default/
}

install_woocommerce() {
  if [[ ! -d "/tmp/woocommerce" ]]
  then
    echo  $WC_VERSION;
    cd /tmp
    git clone https://github.com/woocommerce/woocommerce.git
    cd woocommerce
    git checkout $WC_VERSION

    # In 6.x.x versions WooCommerce changed structure project, for install need move to plugins/woocommerce directory
    if [[ "$WC_VERSION" =~ .*"6.".* ]]; then
      cd plugins/woocommerce
    fi;

    composer install --ignore-platform-reqs
    cd /tmp
  fi
}

install_test_suite() {
  # portable in-place argument for both GNU sed and Mac OSX sed
  if [[ $(uname -s) == 'Darwin' ]]; then
    local ioption='-i .bak'
  else
    local ioption='-i'
  fi

  # set up testing suite if it doesn't yet exist
  if [ ! -d $WP_TESTS_DIR ]; then
  # set up testing suite
  mkdir -p $WP_TESTS_DIR
  svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
  svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
  fi

  if [ ! -f wp-tests-config.php ]; then
  download https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php "$WP_TESTS_DIR"/wp-tests-config.php
    # remove all forward slashes in the end
    WP_CORE_DIR=$(echo $WP_CORE_DIR | sed "s:/\+$::")
    sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$WP_TESTS_DIR"/wp-tests-config.php
    sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
    sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
  fi
}

install_db() {
  if [ ${DB_HOST} == "localhost" ]; then
    mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS" --host=$DB_HOST
  fi
}

install_wp
install_test_suite
install_woocommerce
install_db
