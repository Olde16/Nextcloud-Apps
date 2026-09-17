# Nextcloud Apps

A collection of custom apps for [Nextcloud](https://nextcloud.com/).

This repository is intended as a home for multiple independent Nextcloud apps. Each app lives in its own directory and can be developed, maintained, and released separately.

## Apps

| App | Description | Version |
| --- | --- | --- |
| [e621 Tags](./e621tags) | Automatically tags files using e621 and e6AI metadata extracted from post IDs in filenames. | 1.6.0 |

More apps will be added here over time.

## Repository structure

```text
Nextcloud-Apps/
├── e621tags/       # e621 / e6AI tagging app
├── LICENSE         # GNU AGPL-3.0-or-later
└── README.md       # Repository overview
```

Each app directory contains the complete Nextcloud app, including its `appinfo`, PHP classes, frontend assets, templates, and any app-specific documentation.

## Disclaimer

These apps are provided as open-source software and are used at your own risk. I do not provide any guarantee that they will work correctly in every environment or for every use case.

I am not responsible for data loss, data corruption, service interruptions, security issues, or other damages resulting from the use, modification, configuration, or misuse of the software, to the extent permitted by applicable law.

Please review changes, test new releases in your environment, and keep appropriate backups before using an app in production.

## License

Unless a file or app states otherwise, the contents of this repository are licensed under the **GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)**.

See the [LICENSE](./LICENSE) file for the full license text.

## AI assistance

AI tools may be used during development of this repository, including for code and documentation. Before anything is published, I personally review and test the code. Particular attention is given to code quality, security, and reliable operation.

---

*Built and maintained by Olde16.*
