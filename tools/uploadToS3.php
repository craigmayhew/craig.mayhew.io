<?php

require '../vendor/autoload.php';
use Aws\Common\Aws;

$dir = '/srv/craig.mayhew.io/public/htdocs';
$bucket = 'craig.mayhew.io';
$keyPrefix = '';
$options = array(
  'params'      => array('ACL' => 'public-read'),
  'concurrency' => 20,
  'debug'       => true
);

$config = [
  'profile' => 'default',
  'region' => 'eu-west-1'
];
$aws = Aws::factory($config);
$client = $aws->get('s3');
$client->uploadDirectory($dir, $bucket, $keyPrefix, $options);
