WebGen - simple CLI generator of static sites
=============================================

Using
-----

cd my_web_page_folder

php -f webgen_directory/webgen.php
 - WebGen starts searching input files in input directory
 - WebGen looks for only changed input files
 - WebGen generates output files in output directory (etc. output/2012-04-30_18:59:59)
 
You upload changed files on webhosting.
Done.


Files
-----

my_web_page_folder/@layout.html
	- File with layout
	- You can use variables

my_web_page_folder/config.ini
	- Config file

my_web_page_folder/texyConfig.php
	- PHP script with configuration of Texy! syntax (see http://texy.info/en/)
	- File has to contain definition of function texyConfig(&texy);
	- Information about configuration of Texy, see http://texy.info/cs/api (in Czech)



Config - config.ini
-------------------
Default config:

; Default config
[output]
dir = "/output" 	; relative to current dir (getcwd())
ext = ".php"		; output files extension
dirNameFormat = 'Y-m-d_H-i-s'	; name of directory for generated files in output directory
								; <Year>-<Month>-<Day>_<Hour>-<Min>-<Sec>

[input]
mask = "*.texy"	; mask for searching input files
ext = ".texy"	; input file extension
layout = "@layout.html"	; name of layout file
baseDir = ""	; value of variable {%baseDir}



Config - texyConfig.php
-----------------------
<?php
		function texyConfig(&$texy)
		{
			$texy->headingModule->generateID = false;
		}
?>



Variables
---------

{%title} 	- page title (H1)
{%content}	- escaped HTML content

{%updateDay}
{%updateMon}
{%updateYear}
{%updateHour}
{%updateMin}
{%updateSec}

{%tocUl}	- TOC in HTML <ul><li><a href="#toc">heading</a></li> - NOT IMPLEMENTED
{%tocOl}	- TOC in HTML <ul><li><a href="#toc">heading</a></li> - NOT IMPLEMENTED
{%tocA}		- TOC in HTML <a href="#toc">heading</a>

{%baseDir}	- Document Root


