<?php

// work out if we are in the tool directory or the root of the repo
if (file_exists(getcwd().'/vendor/autoload.php')){
    require 'vendor/autoload.php';
    $dir = 'htdocs';
} else {
    require '../vendor/autoload.php';
    $dir = '../htdocs';
}

use Aws\CloudFront\CloudFrontClient; 
use Aws\S3\S3Client;  
use Aws\S3\Transfer;  
use Aws\Exception\AwsException;

$config = [
    'profile' => 'default',
    'region' => 'eu-west-1',
    'signature' => 'v4',
    'version' => '2006-03-01'
];

$s3 = new S3Client($config);

$bucket = 's3://craig.mayhew.io';

$uploader = new Transfer($s3, $dir, $bucket, [
    'before' => function (\Aws\Command $command) {
        // Commands can vary for multipart uploads, so check which command
        // is being processed
        if (in_array($command->getName(), ['PutObject', 'CreateMultipartUpload'])) {
            // Apply an ACL
            $command['ACL'] = 'public-read';
        }
    },
    'concurrency' => 20,
]);
$uploader->transfer();

echo 'Site uploaded to S3'."\n";

$cf = new Aws\CloudFront\CloudFrontClient([
    'profile' => 'default',
    'region' => 'eu-west-1',
    'version' => '2014-11-06',
]);

$distributionList = $cf->listDistributions([
    'Marker' => '',
    'MaxItems' => '100',
]);

$distributionId = false;
foreach($distributionList['DistributionList']['Items'] as $d) {
    if (isset($d['Aliases']['Items'])) {
        foreach ($d['Aliases']['Items'] as $aliases) {
            if ($aliases === 'craig.mayhew.io') {
                $distributionId = $d['Id'];
                break 2;
            }
        }
    }
}

$cf->createInvalidation(array(
    // DistributionId is required
    'DistributionId' => $distributionId,
    'InvalidationBatch' => [
        'CallerReference' => time(),
        // Paths is required
        'Paths' => array(
            'Items' => array('/*'),
            'Quantity' => 1
        )
    ]
));
echo 'Refreshed cloudfront: '.$distributionId."\n";