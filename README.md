# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict http://www.edrdg.org/jmdict/j_jmdict.html

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. This app will download the latest
JMDict file, parse it, and insert any changes relative to the previous version into the database.

## Features
* Requires PHP 7.0
* A reg-exp based DTD parser
* Unicode based normalization for string data, etc. (the installation process will download the needed Unicode data files)
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
