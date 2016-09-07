#!/bin/bash

source ./common.sh

IMPORT_FILE="$JMDICT_DIR/import.php"

IMPORT_OPTIONS=" --
                $OUTPUT_FORMAT
                $PARSE_DICTIONARY
                $VERSION_DICTIONARY
                $DEBUG_VERSION
                $VALIDATE_CRC
                $VALIDATE_UTF8"
                
IMPORT_RESULT=`"$PHP" -f "$IMPORT_FILE" $IMPORT_OPTIONS`

#TODO colorize output based on what the shell is in use
if [ "$IMPORT_RESULT" == "0" ]; then
    echo -e "\033[30;42m [ OK ] Import successfull\033[0m"
else
    echo "Oh no! A problem occured."
    echo "$IMPORT_RESULT"
fi