# Tests

This directory contains standalone test scripts for the e621Tags app.

Each test can be run on its own. The `run.php` script is the master test runner and executes the selected standalone tests in separate PHP processes.

## Master test runner

From the app directory:

```bash
php tests/run.php
```

The master runner executes the deterministic parser/rate-limit tests, the configuration and queue tests, the file tag update test, and the database integration/validation tests.

You can also select specific tests through the master runner:

```bash
php tests/run.php filename_parser_test.php queue_test.php
```

The live e621 API test is intentionally not part of the master run because it performs an external API request.

## Individual tests

- `filename_parser_test.php` — filename/post-ID extraction, including regression cases such as `DateinameID10293-(1).png`
- `e621_tag_parser_test.php` — e621 tag groups and rating parsing
- `e6ai_tag_parser_test.php` — e6AI tag groups, franchise mapping, contributor handling, and rating parsing
- `api_rate_limiter_test.php` — shared rate-limit state, backoff, cap, and recovery
- `config_test.php` — configuration getters and batch-size limits
- `queue_test.php` — queue behavior for enabled/disabled sources and file tag updates
- `file_tag_update_test.php` — stored tag selection, configuration filtering, rating handling, and e6AI group mapping
- `db_test.php` — basic database write/read integration test
- `db_refresh_test.php` — `refreshPost()` tag replacement and `markChecked()` behavior
- `db_validation_test.php` — invalid arguments and unsupported-source handling
- `e621_api_test.php` — direct e621 API fetch and parser smoke test

## Database tests

The database tests must be run from a Nextcloud installation with the app installed and its database tables available.

## e621 API test

`e621_api_test.php` performs a real request to e621 and passes the returned metadata through the e621 tag parser. It uses the paths and environment of the development Nextcloud installation and may require adjustment for other environments.
