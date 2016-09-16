#!/bin/bash

source ./common.sh

EXPORT_FILE="$JMDICT_DIR/export.php"

EXPORT_VERSION="--export-version=1"
EXPORT_TYPE="--export-type=XML"
#EXPORT_TYPE="--export-type=SQL"

EXPORT_OPTIONS=" --
                $EXPORT_VERSION
                $EXPORT_TYPE"
                
EXPORT_RESULT=`"$PHP" -f "$EXPORT_FILE" $EXPORT_OPTIONS`

#TODO colorize output based on what the shell is in use
if [ "$EXPORT_RESULT" == "0" ]; then
    echo -e "\033[30;42m [ OK ] Export successfull\033[0m"
else
    echo "Oh no! A problem occured."
    echo "$EXPORT_RESULT"
fi