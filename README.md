# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict

## Overview
A stand alone desktop PHP application that generates the database for Manakyun. Running as cron job, this app will download the latest JMDict file, parse it, and insert any changes relative to the previous version into the database. People may be interested in the biweekly XML export of this database available on my website here https://www.manakyun.com/downloads.

## Features (stuff I wrote)
* A custom DTD parser (if Jim Breen changes his DTD, then this app will know about it)
* Custom Unicode routines such as case folding, normalization, etc.
* Exports the dictionary in an XML format that has several advantages over the JMDict format. See https://www.manakyun.com/downloads for a detailed description of this format
