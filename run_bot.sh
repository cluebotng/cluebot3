#!/bin/bash
#
# To deal with labs environments being screwy
# bigbrother doesn't have PHP, but the exec nodes do...
#
cd /data/project/cluebot3/apps/cluebot3/

exec php -f cluebot3.php
