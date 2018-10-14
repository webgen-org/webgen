# Webgen

Simple PHP CLI generator of static web sites.

<a href="https://www.patreon.com/bePatron?u=9680759"><img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" alt="Become a Patron!" height="35"></a>
<a href="https://www.paypal.me/janpecha/5eur"><img src="https://buymecoffee.intm.org/img/button-paypal-white.png" alt="Buy me a coffee" height="35"></a>


## Features

* [Texy!](https://texy.info/) support
* [Latte templates](https://latte.nette.org/) support
* incremental mode


## Usage

### Non-incremental mode

```
$ cd examples/basic
$ php -f ../../webgen.php -- --run --onedir
$ cd output
$ ls
articles  index.html
```


### Incremental mode

```
$ cd my-web-local-directory
$ php -f /path/to/webgen.php -- --run
# or 'webgen --run' (see file bin/readme.md for details)
```

For example:

```
$ cd examples/basic
$ php -f ../../webgen.php -- --run
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
$ php -f ../../webgen.php -- --run

$ cd output
$ ls
2013-12-12_14:15:16  2013-12-12_14:16:34

$ cd 2013-12-12_14:16:34
$ ls
index.html
```

You can use parameter ```--onedir``` for disabling of incremental mode. This parameter ignores date of last generating too (generates all files like parameter ```--force```).

**Note:** you can use config option ```output.onedir``` in your config file (see [config.neon](examples/basic/config.neon)) instead of ```--onedir``` parameter.

```
output:
    onedir: yes
```

------------------------------

***Note:*** *Webgen ignores files with ```@``` on start of filename.*


## Templating

**Texy in Latte template**

```smarty
{block |texy}
This is **Texy** snippet.
{/block}


{texy}
This is "Texy":https://texy.info snippet too.
{/texy}
```

**Get name of current generated file**

```smarty
{$webgen->currentFile} {* prints for example: 'articles/article-2.texy' *}
```

**Get relative path from ```currentFile``` to a file**

```smarty
{$webgen->createRelativeLink('articles/article-1.html')}
{* prints:
'article-1.html'          for currentFile = 'articles/article-2.texy'
'articles/article-1.html' for currentFile = 'index.texy'
*}
```
Shortcuts (output for ```currentFile = 'articles/article-2.texy'```)

```html
{link articles/article-1.html} {* prints article-1.html *}

<a n:href="articles/article-1.html">Article #1</a>
{* prints *}
<a href="article-1.html">Article #1</a>


<link rel="stylesheet" n:href="css/style.css" type="text/css">
{* prints *}
<link rel="stylesheet" href="../css/style.css" type="text/css">


<img n:src="images/photo.jpg">
{* prints *}
<img src="../images/photo.jpg">

<img n:image="images/photo.jpg">
{* prints (image file must exist) *}
<img src="../images/photo.jpg" width="1024" height="768">
```

Relative paths in Texy:

```
"Article #1":@articles/article-1.html

[* @images/photo.jpg *]
```

**Highlight current page in menu** *(is link current?)*
```html
<div id="menu">
    <a n:href="/" n:class="$webgen->isLinkCurrent('index.texy') ? current">Homepage</a>
    <a n:href="about-us/" n:class="$webgen->isLinkCurrent('about-us/**') ? current">About us</a>
    <a n:href="contact.html" n:class="$webgen->isLinkCurrent('contact.*') ? current">Contact</a>
</div>
```

In mask ```**``` means *everything*, ```*``` means *everything <b>except</b> ```/```*.

**Variables in Texy document**
```
{{$var}}

{{$var = value}}

{{$var: value}}
```


## Configuration

Configuration is stored in file named [```config.neon```](examples/basic/config.neon). [NEON](https://ne-on.org/) is format very similar to YAML, see https://ne-on.org/.

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

**Copy files from source directory into output directory (CSS & JS files, images,...)**

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

**Purge output directory before generating (boolean value (`yes`/`no`) or filemask(s))**

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

```smarty
{webgen ext => php}   ## in Latte template
{{webgen: ext: php}}  ## in Texy file
```

**Change output filename**

```smarty
## changes output file extension
{webgen ext => php}

## changes output basename of file
{webgen name => my-first-page}

## changes output filename (ignores 'ext' option)
{webgen filename => my-first-page.html}
```

**Disable/enable generating of ```lastBuild.dat``` file**

```
output:
    lastBuildInfo: off
    # values:
    #    off    - disabled
    #    on     - enabled
    #    NULL   - auto detected by mode
```


## Pagination

See example [pagination-repeated-generating](examples/pagination-repeated-generating).


## Who uses Webgen?

* http://knihovna.sluzatky.cz/
* http://maturitni-ples.iunas.cz/
* http://via.iunas.cz/

Articles:
* https://www.janpecha.cz/blog/webgen-2-1/ (in Czech)
* https://www.janpecha.cz/blog/webgen/ (in Czech)


------------------------------

License: [New BSD License](license.txt)
<br>Author: Jan Pecha, https://www.janpecha.cz/

