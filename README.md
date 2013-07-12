Browse resources bookings
=========

This application reads an Excel sheet with resource bookings and creates a browsable web page from it.
It is structured as a simple view/controller framework. 

To run, 

* clone the repository,
* point a virtual web server host to it,
* copy the file database.ini.default to database.ini,
* import your Excel sheet with "REQUEST_URI=/update php index.php"
* invoke as http://SERVER/index.php
