# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. Running as cron job, this app will download the latest JMDict file, parse it, and insert any changes relative to the previous version into the database. People may be interested in the biweekly XML export of this database available here https://www.manakyun.com/downloads.

## Features
* A reg-exp based DTD parser
* Custom Unicode routines such as case folding, normalization, etc.
* Exports the dictionary in an XML format that has several advantages over the JMDict format. See https://www.manakyun.com/downloads for a detailed description of this format
* Easy to add your own parser (just add a new folder and follow a few naming conventions)


Further details, like how the XML parsing is done or what features are supported by the DTD parser, can be found here https://www.manakyun.com/dev
