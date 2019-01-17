#!/usr/bin/env bash
DIRECTORY=$(cd `dirname $0` && pwd)

msgfmt $DIRECTORY/../pot/retailcrm-ru_RU.pot -o $DIRECTORY/../../src/languages/retailcrm-ru_RU.mo
msgfmt $DIRECTORY/../pot/retailcrm-es_ES.pot -o $DIRECTORY/../../src/languages/retailcrm-es_ES.mo