[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg?style=flat-square)](https://php.net/)

# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict http://www.edrdg.org/jmdict/j_jmdict.html

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. This app will download the latest
JMDict file, parse it, and insert any changes relative to the previous version into the database. This app aims to be a complete,
self-contained, and open wrapper for the JMDict file that aids the development of apps based off Breen's dictionary (like Manakyun).

## Features
* Requires PHP 7.0
* A reg-exp based DTD parser (if the DTD changes on JMDict, MKDict will notice)
* Pollyfill Unicode based normalization for string data, etc. (the installation process will download the needed Unicode data files)
* Export the dictionary in an XML format that has several advantages over the JMDict format.
* Low memory footprint (xml parsing, checksum verification, etc. do not require loading the entire xml document as a string)
* The database layer is buffered which yields a net processing time of about 1 hour (previously without buffering processing time took a whopping 72 hours)
* log file detailing any errors or elements which failed to import due to invalid data (which is a good way to catch errors in the original JMDict file)

## Upcoming Features
* Sentence examples (from tatoeba.org and the JEITA corpus)
* Collocations (done with NLTK in Python)

## The XML export format
See the XSD document in the Exporter folder. One of the main advantages of this format is that every element is given a unique id and cross references reference this id.
Another advantage is that in addition to the raw string being reproduced, Unicode normal forms are also given. Hence each element has a proper canonical form which can be
relied upon for searching and sorting. In otherwords this XML file is basically JMDict again but cleaned up and better organized.


## JMDict Errors and data integrity
The main JMDict file has always had several errors upon each iteration, such as a cross reference that references a previously removed element.
These errors are not propagated into the db. Also, given the data-reliant nature of a dictionary app, assurances of the data's integrity are mandatory which is why
this app does a lot to filter and sanitize the original JMDict file. Namely, with this database you can be sure that:
* Cross references are not broken
* Numbers are numbers, and strings are strings (types are as they should be, and both always contain reasonable values)
* The problematic "・" character to separate reading and kanji entries no longer denotes such a delimitation
* No duplicate or invalid Sequence ids
* No invalid UTF8

I hope to add config options related to how MKDict will react to invalid data from the JMDict file (ie do you want to truncate an excessively long string or safely ignore the element
containing that long string)

## Installer Options
(i will remove bash scripts and create one install/import phar file)
### --create-db
Create the manakyun database
### --test-db
For Development. Creates a temp table, populates it with utf8 data, and queries it. Uses DB library. 
### --utf-tests
TODO 
## --generate-utf-data
TODO

## Importer Options
### --local-copy
Use a local copy of JMDict instead of downloading it. The local file should be placed in MANAKYUN_DIR/var/data and should be a gz file. Must use --gz-file to specify the filename
### --gz-file
Specify the local JMDict file to use e.g. --gz-file=20D06965F4FEE90A8_1620819068.gz
### --parse-dictionary
TODO
### --version-dictionary
TODO
### --validate-crc32
TODO
### --validate-utf8
TODO
### --with-rollback
TODO

## Exporter Options
### --export-version
TODO
### --export-type
TODO

## General Options
### --debug-version
For Development. Turns on error_reporting(E_ALL) and libxml_use_internal_errors(true)