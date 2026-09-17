# e621 Tags

A Nextcloud app that automatically applies tags to files based on e621 and e6AI post metadata.

## What it does

The app looks for post IDs in filenames and processes files from the configured e621/e6AI directories.

API metadata is stored locally in the app database, including the available API tag groups. This allows stored post data to be reused without requesting the API again for every file operation.

A background update job can periodically check stored posts against the APIs and refresh the local data when a post has changed.

## Supported sources

### e621

The app can import the following tag groups:

- General
- Artist
- Character
- Copyright
- Species
- Invalid
- Lore
- Meta
- Rating

### e6AI

The app can import the following tag groups:

- General
- Director
- Character
- Copyright
- Species
- Invalid
- Lore
- Meta
- Rating

## Configuration

The app provides an administration page for enabling or disabling e621 and e6AI processing, configuring API credentials, selecting tag groups, and setting the scan batch size.

## Marker tag

Processed files receive the marker tag `Tagged by e621TagSystem`.

## License

This app is licensed under the **GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)**.

See the repository [LICENSE](../LICENSE) file for the full license text.

## AI assistance

Parts of this app and its documentation have been created or refined with the assistance of AI tools. Human review, testing, and final decisions remain with the repository maintainer.
