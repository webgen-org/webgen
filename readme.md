Webgen 2.0
==========

Simple PHP CLI generator of static web sites.


Features
--------

* Texy! support (http://texy.info/)
* Latte templates support (http://doc.nette.org/en/templating#toc-latte)


Usage
-----

```
cd my-web-local-directory
php -f /path/to/webgen.phpc -- --run
# or 'webgen --run' (see file bin/readme.txt for details)
```

For example:

```
cd example
php -f ../webgen.phpc -- --run
```

Webgen currently creates subdirectories (eg. `2013-12-12_14:15:16`) in output directory and generates only files changed from date of last generating. You can use parameter ```--force```, then Webgen will generating all files. Creating of subdirectories is primitive method of versioning.

You can use parameter ```--onedir``` for disabling of subdirectories creating. This parameter ignores date of last generating too (generates all files like parameter ```--force```).


Who uses Webgen?
----------------

* http://knihovna.sluzatky.cz/
* http://maturitni-ples.iunas.cz/


------------------------------

License: [New BSD License](license.txt)
<br>Author: Jan Pecha, http://janpecha.iunas.cz/webgen

