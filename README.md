# MKDict
A Japanese-English dictionary based off Jim Breen's JMDict http://www.edrdg.org/jmdict/j_jmdict.html

## Overview
A stand alone PHP application that generates the database used in the Android app Manakyun. Running as a cron job, this app will download the latest JMDict file, parse it, and insert any changes relative to the previous version into the database.

## Features
* Requires PHP 7.0
* A reg-exp based DTD parser
* Custom Unicode 7.0 routines for case folding and normalization, etc. (the installation process will download the needed Unicode data files)
* Exports the dictionary in an XML format that has several advantages over the JMDict format.
* Low memory footprint (xml parsing, checksum verification, etc. do not require loading the entire xml document as a string)
* The database layer is buffered which yields a net processing time of about 1 hour (previously without buffering processing time took a whopping 72 hours)
* log file detailing any errors or elements which failed to import due to invalid data (which is a good way to catch errors in the original JMDict file)

## The XML export format
See the XSD document in the Exporter folder. One of the main advantages of this format is that every element is given a unique id. Sample:
<entry entry_uid="17" sequence_id="1000090">
      <kanji kanji_uid="18" >
          <binary form="raw" >○</binary>
      </kanji>
      <kanji kanji_uid="19" >
          <binary form="raw" >〇</binary>
      </kanji>
      <reading reading_uid="20" >
          <binary form="raw" >まる</binary>
      </reading>
      <sense sense_uid="21">
          <pos>&n;</pos>
          <gloss>circle (sometimes used for zero)</gloss>
      </sense>
      <sense sense_uid="22">
          <pos>&n;</pos>
          <gloss>'correct' (when marking)</gloss>
          <ant>
              <binary form="raw">×・ばつ・1</binary>
              <kanji_ref kanji_uid="28">×</kanji_ref>
              <reading_ref reading_uid="29">ばつ</reading_ref>
              <sense_ref sense_uid="32">x-mark (used to indicate an incorrect answer in a test, etc.)</sense_ref>
          </ant>
      </sense>
</entry>

## JMDict Errors
The main JMDict file has always had several errors upon each iteration, such as a cross reference that references a previously removed element. These errors are not propagated into the db.