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

## Release and code review

Code in this repository is reviewed by the repository maintainer before publication or release. Particular attention is given to reviewing the code itself before it is made publicly available.

## License

Unless a file or app states otherwise, the contents of this repository are licensed under the **GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)**.

See the [LICENSE](./LICENSE) file for the full license text.

## AI assistance

Parts of this repository, including code and documentation, have been created or refined with the assistance of AI tools. AI assistance is used as a development aid only. Before publication or release, the code is reviewed by the repository maintainer, with particular focus on the code and its suitability for publication. Human decisions remain with the repository maintainer.

---

*Built and maintained by Olde16.*