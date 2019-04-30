#!/bin/bash

chmod 777 cache/ logs/
cp src/settings.php.dist src/settings.php
chmod u+x clear_cache.sh