# Tests

This directory contains standalone test scripts for the e621Tags app.

## Database test

Run `db_test.php` from a Nextcloud installation to verify that post data and stored tag groups can be written to and read from the app database.

## e621 API test

Run `test.php` from a Nextcloud installation to fetch a post from e621 and pass its metadata through the e621 tag parser.

The test scripts currently use the paths and environment of the development Nextcloud installation and may require adjustment for other environments.
