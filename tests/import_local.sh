#!/bin/bash

source ./common.sh

THIS_DIR=`dirname $0`
JMDICT_DIR=`readlink -e "$THIS_DIR/.."`

DATA_FILE_NAME="DD50996022F781D36_1472274168"

GZ_XML_FILE_NAME=`readlink -e "$JMDICT_DIR/var/data/$DATA_FILE_NAME.xml"`
if [ -f "$GZ_XML_FILE_NAME" ]; then
    gzip -c $GZ_XML_FILE_NAME > "$JMDICT_DIR/var/data/$DATA_FILE_NAME.gz"
fi

GZ_FILE_NAME=`readlink -e "$JMDICT_DIR/var/data/$DATA_FILE_NAME.gz"`
if [ -z "$GZ_FILE_NAME" ]; then
    die "GZ_FILE_NAME not set"
fi
GZ_FILE="--gz-file=$DATA_FILE_NAME.gz"

IMPORT_FILE="$JMDICT_DIR/import.php"

IMPORT_OPTIONS=" --
                $OUTPUT_FORMAT
                $LOCAL_COPY
                $GZ_FILE
                $PARSE_DICTIONARY
                $VERSION_DICTIONARY
                $DEBUG_VERSION
                $WITH_ROLLBACK
                $VALIDATE_CRC
                $VALIDATE_UTF8"
                
IMPORT_RESULT=`"$PHP" -f "$IMPORT_FILE" $IMPORT_OPTIONS`

#TODO colorize output based on what the shell is in use
if [ "$IMPORT_RESULT" == "0" ]; then
    echo -e "\033[30;42m [ OK ] Manakyun installed successfully\033[0m"
else
    echo "Oh no! A problem occured."
    echo "$IMPORT_RESULT"
fi