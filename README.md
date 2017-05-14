# craig.mayhew.io

Craig Mayhew's Personal website

Blog posts are stored in blogposts/ as json files. One file per post.
Pages are stored in pages/ as json files. One file per page.

PHP is used to generate static html in the htdocs directory and then optionally sync that to an S3 bucket.

## install

<pre>
php composer.phar selfupdate
php composer.phar install
</pre>

Optionally, if you intend to deploy to S3, you need to add your credentials to your home folder. The file needs permission 600 ~/.aws/credentials

<pre>
[default]
aws_access_key_id = SECRET_ID
aws_secret_access_key = SECRET_KEY
</pre>

## deploy

<pre>
cd tools
php createstatic.php
php uploadeToS3.php
</pre>
