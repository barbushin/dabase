DaBase is a very flexible, simple and lightweight pseudo ORM for PHP.

## Features

* Support multiple DB connectors
  * PDO http://php.net/pdo
  * MySQLnd http://php.net/manual/book.mysql.php
  * MySQLi http://php.net/mysqli
  * PgSql http://php.net/pgsql
* SQL builder with 8 types of smart placeholders for statements data binding & prepare
* Easy to use and flexible Collections & Models class map
* Flexible router for mapping `$db->someTable->get()` to collection class and table name
* No need to specify DB schema and relations, just follow Router naming convention 
* Many to many, one to many, one to one relations support with smart appending interface 
* Model with built in validator and 12 pre-defined validation rules
* Result cacher with auto clear mode
* Minimalistic collections filters `$db->users->age(27, '<')->isActive(true)->orderBy('age')->get()`
* Nested sets implemented interface

## Documentation & Examples

See http://php-console.com/dabase

## Recommendations
 * Google Chrome extension [PHP Console](https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef).
 * Google Chrome extension [JavaScript Errors Notifier](https://chrome.google.com/webstore/detail/javascript-errors-notifie/jafmfknfnkoekkdocjiaipcnmkklaajd).
