# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. Running as a cron job, this app will download the latest JMDict file, parse it, and insert any changes relative to the previous version into the database.

## Features
* Requires PHP 7.0
* A reg-exp based DTD parser
* Custom Unicode 7.0 routines for case folding and normalization, etc. (the installation process will download the needed Unicode data files)
* Exports the dictionary in an XML format that has several advantages over the JMDict format.
* Low memory footprint (xml parsing, checksum verification, etc. do not require loading the entire xml document as a string)
* The database layer is buffered which yields a net processing time of about 1 hour (previously without buffering processing time took a whopping 72 hours)