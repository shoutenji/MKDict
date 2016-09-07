# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict http://www.edrdg.org/jmdict/j_jmdict.html

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. This app will download the latest
JMDict file, parse it, and insert any changes relative to the previous version into the database.

## Features
* Requires PHP 7.0
* A reg-exp based DTD parser
* Custom Unicode 7.0 routines for case folding and normalization, etc. (the installation process will download the needed Unicode data files)
* Exports the dictionary in an XML format that has several advantages over the JMDict format.
* Low memory footprint (xml parsing, checksum verification, etc. do not require loading the entire xml document as a string)
* The database layer is buffered which yields a net processing time of about 1 hour (previously without buffering processing time took a whopping 72 hours)
* log file detailing any errors or elements which failed to import due to invalid data (which is a good way to catch errors in the original JMDict file)

## The XML export format
See the XSD document in the Exporter folder. One of the main advantages of this format is that every element is given a unique id and cross references reference this id.


## JMDict Errors
The main JMDict file has always had several errors upon each iteration, such as a cross reference that references a previously removed element.
These errors are not propagated into the db.