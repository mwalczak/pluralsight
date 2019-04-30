#!/bin/bash

chmod 777 cache/ logs/ backup/
cp src/settings.php.dist src/settings.php
chmod u+x clear_cache.sh