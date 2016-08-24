#!/bin/bash

#This script should only by run in a terminal window
#TODO need a window message here
if ! [ -t 1 ]; then
    TITLE="MKdict error"
    MSG="This script cannot be run outside a terminal context."
    if [ -n `which zenity` ]; then
        zenity --error --title="" --text="$MSG"
    elif [ -n `which kdialog` ]; then
        kdialog --error --title "$TITLE" "$MSG"
    elif [ -n `which xmessage` ]; then
        xmessage -center "ERROR: $TITLE: $MSG"
    elif [ -n `which notify-send` ]; then
        notify-send "ERROR: $TITLE: $MSG"
    else
        echo "$TITLE:\n$1"
    fi
    exit 1
fi

die()
{
    echo "$1"
    exit 1
}

finish()
{
    echo "$1"
    exit 0
}

#Make sure the PHP CLI is present and working
PHP=`which php`
if [ -z "$PHP" ]; then
    die "PHP command not found."
fi

PHP_VERSION=`"$PHP" -r "echo PHP_VERSION;"`

#TODO should be a better way to process the version string
INDEX=`expr index "$PHP_VERSION" "."`
INDEX=`expr $INDEX - 1`
PHP_MAJOR_VERSION=${PHP_VERSION:0:$INDEX}
INDEX=`expr $INDEX + 1`
PHP_VERSION=${PHP_VERSION:$INDEX}
INDEX=`expr index "$PHP_VERSION" "."`
INDEX=`expr $INDEX - 1`
PHP_MINOR_VERSION=${PHP_VERSION:0:$INDEX}

if [ $PHP_MAJOR_VERSION -lt 7 -a  $PHP_MINOR_VERSION -lt 0 ]; then
    die "PHP version must be 7.0 or higher"
fi

#Now run the init script
THIS_DIR=`dirname $0`
JMDICT_DIR=`readlink -e $THIS_DIR`

#possible options for scripts
OUTPUT_FORMAT="--bash-output"
CREATE_DB="--create-db"
TEST_DB="--test-db"
UTF_TESTS="--utf-tests"
GENERATE_UTF_DATA="--generate-utf-data"
LOCAL_COPY="--local-copy"
PARSE_DICTIONARY="--parse-dictionary"
VERSION_DICTIONARY="--version-dictionary"
VALIDATE_CRC="--validate-crc32"
VALIDATE_UTF8="--validate-utf8"
DEBUG_VERSION="--debug-version"
WITH_ROLLBACK="--with-rollback"

#Check for PHP extensions
EXTENSIONS=`"$PHP" -m`

PHP_PACKAGE_SUFIX="php-"
if [ $PHP_MAJOR_VERSION -eq 7 ]; then
    PHP_PACKAGE_SUFIX="php7.0-"
fi;

#TODO not everyone will have apt-get
COMMAND="sudo apt-get install $PHP_PACKAGE_SUFIX"

ZLIB=`echo "$EXTENSIONS" | grep zlib`
if [ -z "$ZLIB" ]; then
    die "zlib extension is disabled You will need to configure PHP --with-zlib[=DIR]"
fi

MBSTRING=`echo "$EXTENSIONS" | grep mbstring`
if [ -z "$MBSTRING" ]; then
    die "mbstring extension required. Run ${COMMAND}mbstring"
fi

XML=`echo "$EXTENSIONS" | grep xml`
if [ -z "$XML" ]; then
    die "xml extension required. Run ${COMMAND}xml"
fi

LIBXML=`echo "$EXTENSIONS" | grep libxml`
if [ -z "$MBSTRING" ]; then
    die "libxml extension is disabled. Must enable libxml to continue."
fi

PDO=`echo "$EXTENSIONS" | grep PDO`
if [ -z "$PDO" ]; then
    die "PDO extension is disabled. Must enable PDO to continue."
fi

PDOMYSQL=`echo "$EXTENSIONS" | grep pdo_mysql`
if [ -z "$PDOMYSQL" ]; then
    die "pdo_mysql extension is required. Run ${COMMAND}mysql"
fi

#TODO
#check for mysql version
#need at least 5.6 