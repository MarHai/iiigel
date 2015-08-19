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

###### Questions

Can be directed to @MarHai
