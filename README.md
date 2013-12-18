flatyaml2po
===========

Converts flat YAML translation files to PO files and back again. Usable for translations in 
<a href="http://github.com/bolt/bolt/">Bolt</a> and Symfony in general. The reason you might 
want to do this is, is the super translation support in tools like 
<a href="http://virtaal.translatehouse.org/">Virtaal</a>. 

Flat YAML translation files means that there is only one translation in the file and that 
you have a flat structure in the YAML file - see for example the 
<a href="https://github.com/bolt/bolt/tree/master/app/resources/translations">Bolt translation files</a>.

There are of course other scripts that do the same type of conversion, see for example
<a href="https://github.com/unho/yaml2po">Github: unho/yaml2po</a>, but they often require 
Ruby or PHP extensions that you might not have. This script is a hackish solution that seems
to work.

The script is licensed under GPL v3.

Usage
-----

Convert YAML to PO

```
php yaml2po.php file.yml file.po
```

Convert PO to YAML

```
php yaml2po.php -r file.yml file.po
```
