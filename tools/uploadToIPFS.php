<?php

$dir = '/mnt/c/Users/day2day/Documents/GitHub/craig.mayhew.io/htdocs';

exec('ipfs add -r /mnt/c/Users/day2day/Documents/GitHub/craig.mayhew.io/htdocs');

echo 'Shared to IPFS: https://127.0.0.1:8080/'.$hash."\n";