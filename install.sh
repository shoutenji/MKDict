#!/bin/bash

source ./common.sh

#clear tmp and log files
#CAREFUL rm
#make sure JMDICT_DIR is actually truly set otherwise we could delete the wrong files (say someone deletes common.sh then runs this file)
#if ! [ -z "$JMDICT_DIR" ]; then
#    rm -rdf "$JMDICT_DIR"/var/logs/*
#    rm -rdf "$JMDICT_DIR"/var/tmp/*
#fi

INIT_FILE="$JMDICT_DIR/install.php"

INIT_OPTIONS=" --
                $CREATE_DB
                $TEST_DB
                $DEBUG_VERSION
                $GENERATE_UTF_DATA
                $UTF_TESTS"

INIT_RESULT=`"$PHP" -f "$INIT_FILE" $INIT_OPTIONS`

#TODO colorize output based on what the shell is in use
if [ "$INIT_RESULT" == "0" ]; then
    echo -e "\033[30;42m [ OK ] Manakyun installed successfully\033[0m"
else
    echo "Oh no! A problem occured."
    echo "$INIT_RESULT"
fi