# i³gel

_**I**nformatik **i**n **i**nteraktiven **G**ruppen **e**infach **l**ernen_ || _**I**T **i**n **i**nteractive **g**roups **e**asily **l**earnt_

## Requirements

- PHP > 5.5.x
  - fileinfo
  - gettext
  - imap_mail
- MySQL > 5.6.x
- composer


## Setup

First, clone the repository into your webserver's website directory (for xampp, that's normally c:/xampp/htdocs).

Second, install [composer](https://getcomposer.org/), fire up a command line (probably with admin. permissions), change to the main directory of the cloned repo and enter `composer update`.

Third, run `res/iiigel.sql` through your database.

Fourth, update `config/main.php` and set both main URL and database access settings.

Fifth, start up a browser, navigate to **i³gel** and register as a new user.

Sixth, open [HeidiSQL](http://www.heidisql.com/) or phpMyAdmin or something similar, find the `user` table and set both `bAdmin` and `bActive` of your newly generated user to `1`.

Good to go, now start coding/improving/documenting. And finally, push the modifications with a good (= self-explanatory and English) comment to the GitHub repo.


## i18n (language)

The project should fully build on i18n. That is, no expression should be hard-coded anywhere but every word that potentially reaches a user has to be put into a language file. Language handling is done through gettext's .mo/.po files and can be found under `res/i18n/`. The .mo file is editable using any text editor. It has, then, to be converted into a machine-readable .po file using [Poedit](https://poedit.net/) in its free version. Therefore, start Poedit, open the .mo file and save it. That's it. Keep in mind, that Apache caches these .po files and needs to be restarted in order to make the changes effective.

JavaScript i18n is handled similarly. The .po files are converted into a JSON object and saved (thus, cached) within the `res/i18n/` folders as well. Hence, all terms available to PHP (and Twig) are also available to JS and vice versa.

To use the text in your code, use one of the following approaches:

Language | Term to translate | Code example
--- | --- | ---
PHP | yes | ```gettext('yes')```
PHP | yes | ```_('yes')``` because \_(...) is a synonym of gettext(...)
PHP | user | ```ngettext('user', 'user.plural', 1)```
PHP | users | ```ngettext('user', 'user.plural', 2)``` or ```ngettext('user', 'user.plural', 3)``` or ```ngettext('user', 'user.plural', 42)```
Twig | yes | ```{% trans 'yes' %}```
Twig | yes | ```{{ 'yes'|trans }}```
Twig | users | ```{% trans %}user{% plural 2 %}user.plural{% endtrans %}``` or ```{% trans %}user{% plural 3 %}user.plural{% endtrans %}``` or ```{% trans %}user{% plural 42 %}user.plural{% endtrans %}```
JS | yes | ```i18n('yes')```
JS | users | ```i18n('user', 2)``` or ```i18n('user', 3)``` or ```i18n('user', 42)```


## MVC and namespaces

Namespaces and inclusions are handled through composer's PSR-4 autoloader. Moreover, the code is fully MVC-structured, where ...

- **M**odels include the main functional logic as well as database operations; they do not initiate something but only handle stuff upon request
- **V**iews handle outputs and output formats, communicate with Twig, and request data from models
- **C**ontrollers keep the whole thing running, initiate views, tell them what to do and handle user interactions (by fowarding them to the views or by initiating model actions)

Every call to the page is redirected to `index.php` (the only file not strictly object-oriented) which routes calls to appropriate controllers (and only to controllers). An appropriate call ...

- names a controller by making use of the `c` parameter (example: `iiigel.net/?c=MyController`)
- names a controller as quasi-directory right after the main URL (example: `iiigel.net/MyController`)
- does not name a controller and thus initiates the default controller

A controller can handle multiple actions. However, without naming an action, a controller does not know what to do. An appropriate call thus ...

- names an action by making use of the `a` parameter (example: `iiigel.net/?c=MyController&a=magicAction`)
- names an action as quasi-directory right after the controller (example: `iiigel.net/MyController/magicAction`)
- does not name a controller and thus initiates a controller's default action (specified as controller constant)

Ultimately, a controller's action can take parameters. Parameters can be pushed to controllers in two ways. They can ...
- be named specifically using the exact PHP variable's name as parameter name (example: `iiigel.net/?_sFile=foo.bar`)
- be listed as quasi-directory right after an action, split by slashes (`/`) (example: `iiigel.net/MyController/magicAction/param1/param2`)
- the two ways mentioned can also be combined (example: `iiigel.net/MyController/magicAction/param1?_sFile=foo.bar`)


## Coding constraints

All code has to be readable in English. Moreover, all code must be commented following the [PHPDoc DocBlock syntax](http://www.phpdoc.org/docs/latest/guides/docblocks.html), again in English.

Additionally, as PHP does not offer strict variable type declaration, naming conventions should be applied where the first character of a variable indicates a variable's (MySQL: column's) type (e.g., `bDeleted`). The rest of the variable should be formatted in camelCase. Parameters within functions/methods start with an underscore prefix (e.g., `_bDeleted`).

- nVariable: numeric (integer or double/float)
- sVariable: text
- bVariable: boolean
- aVariable: array (numeric or associative)
- oVariable: object (or resource)
- fVariable: function or method (also callbacks)
- mVariable: mixed, can take multiple types (use sparingly)
- the only exception are counter variables for `for` and `while` loops (use `i`, `j`, `k` and so forth)


## Questions

Can be directed to @MarHai
