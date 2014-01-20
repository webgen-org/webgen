Webgen 2.1
==========

Simple PHP CLI generator of static web sites.


Features
--------

* [Texy!](http://texy.info/) support
* [Latte templates](http://doc.nette.org/en/templating#toc-latte) support
* incremental generating


Usage
-----

### Incremental generating

```
$ cd my-web-local-directory
$ php -f /path/to/webgen.phpc -- --run
# or 'webgen --run' (see file bin/readme.md for details)
```

For example:

```
$ cd examples/basic
$ php -f ../../webgen.phpc -- --run
```

Webgen creates subdirectories (eg. `2013-12-12_14:15:16`) in output directory and generates only files changed from date of last generating. You can use parameter ```--force```, then Webgen will generating all files.

After first generating:

```
$ cd output
$ ls
2013-12-12_14:15:16

$ cd 2013-12-12_14:15:16
$ ls
articles  index.html
```

After editing of ```index.html```:

```
$ cd ../../input
$ touch index.html
$ cd ..
$ php -f ../../webgen.phpc -- --run

$ cd output
$ ls
2013-12-12_14:15:16  2013-12-12_14:16:34

$ cd 2013-12-12_14:16:34
$ ls
index.html
```

### Non-incremental generating

You can use parameter ```--onedir``` for disabling of incremental generating. This parameter ignores date of last generating too (generates all files like parameter ```--force```).

```
$ cd examples/basic
$ php -f ../../webgen.phpc -- --run --onedir
$ cd output
$ ls
articles  index.html
```

**Note:** you can use config option ```output.onedir``` in your config file (see [config.neon](examples/basic/config.neon)) instead of ```--onedir``` parameter.

```
output:
    onedir: yes
```


Configuration
-------------

Configuration is stored in file named [```config.neon```](examples/basic/config.neon). [NEON](http://ne-on.org/) is format very similar to YAML, see http://ne-on.org/.

**Change name of source or output directory**

```
input:
	dir: <new-source-dir-name>

output:
	dir: <new-output-dir-name>
```

**Change name of layout template**

```
input:
	layout: @my-layout-name.latte
```

**Copy files from source directory** into output directory (CSS & JS files, images,...)

Boolean value (`yes`/`no`) or filemask(s):

```
input:
    copy: yes
```

```
input:
    copy:
        - *.js
        - *.css
```

**Purge output directory** before generating (boolean value (`yes`/`no`) or filemask(s))

```
output:
    purge: yes
```

```
output:
    purge:
        - *.html
        - *.php
        - *.js
        - *.css
```

**Enable XHTML output**

```
output:
    xhtml: on
```

**Change default output file extension**

```
output:
    ext: php
```

File-specific change:

```
{webgen ext => php}   ## in Latte template
{{webgen: ext: php}}  ## in Texy file
```


Who uses Webgen?
----------------

* http://knihovna.sluzatky.cz/
* http://maturitni-ples.iunas.cz/
* http://janpecha.iunas.cz/
* http://via.iunas.cz/


------------------------------

License: [New BSD License](license.txt)
<br>Author: Jan Pecha, http://janpecha.iunas.cz/webgen

