# masi - Extend TYPO3's URL Handling

Masi is the missing piece for the people who want to customize _everything_ when generating URLs in TYPO3 v9+.

## Features

1. TYPO3 v9 skips pages of type "SysFolder" and "Spacers" by default when generating the URL of subpages. _masi_ includes them by default!

2. _masi_ also ships with a new checkbox for pages, to exclude a certain page slug when generating subpages. This way, you can exclude only certain SysFolders.

There is a CLI command to migrate the options from RealURL to the _masi_ database field.

3. _masi_ evaluates PageTSconfig where you can override your values.

        TCEMAIN.pages.slug.generatorOptions {
            generatorOptions.fields = company, city
            fieldSeparator = -
        }

4. _masi_ allows you to set a hard prefix (!) for a specific page tree via PageTS:

        TCEMAIN.pages.slug.generatorOptions {
            prefix = /wishlist/
        }

    Any prefix is added BEFORE the parent page prefix, but you can also disable the option "prefixParentPageSlug"

        TCEMAIN.pages.slug.generatorOptions {
            prefixParentPageSlug = 0
        }

Of course, all the values within the "slug" field can be changed by the editor, but it's here for convenience.


## Installation

Use it via `composer req b13/masi` or install the Extension `masi` from the TYPO3 Extension Repository.

_masi_ requires TYPO3 v9.5.6 or later.

If you want to migrate from RealURL, execute this one-time command as long as the database field `pages.tx_realurl_exclude` exists, and transfers the data to `pages.exclude_slug_for_subpages`:

    `vendor/bin/typo3 database:migrate:masi`


## License

As TYPO3 Core, _masi_ is licensed under GPL2 or later. See the LICENSE file for more details.


## Background, Authors & Further Maintenance

This extension was created as a show-case on what you can do with one magic hook for TYPO3 v9 and customize
so many things.

TYPO3 community often requests functionality, which can be put in small and efficient extensions, and _masi_ does
exactly that, without having to burden everything into TYPO3 Core.

_masi_ was initially created by Benni Mack in 2019, for [b13, Stuttgart](https://b13.com), with the nice support from
Martin Kutschker.