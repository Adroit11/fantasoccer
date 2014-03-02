# What's this?

Fantatorneo is a simple PHP web application that you can use to manage your personal "Fantacalcio" league with your friends. Matured after years of tough championships with continuous improvements, it is now available as a basis to develop your own tournament manager.

Basic features:

* User management
* Calendar customization
* Automated calendar generation
* Daily formations insertion
* Automatic player list loading (from [PianetaFantacalcio.it](http://www.pianetafantacalcio.it/fantacalcio.asp) )
* Automatic score calculation (votes from [PianetaFantacalcio.it](http://www.pianetafantacalcio.it/fantacalcio.asp))
* Wall
* Formations and results history

# Prerequisites

You need an hosting with PHP 5 and MySQL enabled.

# Configuration

Just edit the engine/config.php and set your host name, database access parameters (user, password, db name).
Then the configuration needs to know the public URL of your site and the URLs of the two reference services for player lists and votes retrieval ([Gazzetta.it](http://www.gazzetta.it) and [PianetaFantacalcio.it](http://www.pianetafantacalcio.it/fantacalcio.asp)).

These services could periodically change their URLs or their internal structure. I will try keeping the parsers up to date.

To automate the formation check and result calculations, you need to setup a CRON job pointing at the engine/cron.php script. Executing this once per hour is more than enough. 