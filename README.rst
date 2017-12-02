Extend event parser
###################

Parses ``Extend::call()``, ``Extend::buffer()`` and ``Extend::fetch()`` calls
and produces a JSON output with the found invocations.


Requirements
************

- PHP 5.6+
- SunLight CMS codebase (version 8.x)
- `Composer <https://getcomposer.org/>`_


Installation
************

1. download (or clone) this repository
2. run ``composer install``


Usage
*****

Parsing calls in all PHP files in the given directory
=====================================================

.. code:: bash

   bin/parse path/to/sunlight/cms/source

.. NOTE::

   Files under *vendor*, *plugins*, *cache* and *tmp* directories are ignored.


Parsing calls in a single file
==============================

.. code:: bash

   bin/parse path/to/file.php
