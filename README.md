[![Build Status](https://travis-ci.org/bogdananton/Dokra.svg)](https://travis-ci.org/bogdananton/Dokra) [![Code Climate](https://codeclimate.com/github/bogdananton/Dokra/badges/gpa.svg)](https://codeclimate.com/github/bogdananton/Dokra) [![Coverage Status](https://coveralls.io/repos/bogdananton/Dokra/badge.svg)](https://coveralls.io/r/bogdananton/Dokra)

# Dokra

Documentation analyser and generator for WSDL (and more).

## @changes
* `[2015/06/07]` rebuild the application. Can scan and extract basic details (objects, custom arrays, methods) from WSDL files in the project path.


## @todo

* Scan and create links between objects from different serialization formats. (Dokra + CodeGraph)
* Output differ and diagnostics / logs
* Create Vagrant box and integrate Selenium (Selenium and PHPUnit will be used to login and extract the current documentation from the Application CPanel)
* Create NodeJS GUI client for displaying all documentation components (RAW editable JSON output, SOAP / JSON / RAML layers with interconnection between methods and routes / controllers), RAML intermediary format with Markdown inserts for HTML parts, and HTML previews in different themes (RAML, CPanel, ...)
* Create API mock-console with multiple scenarios for calls, linked to the NodeJS GUI client and useful for describing a feature / method.
