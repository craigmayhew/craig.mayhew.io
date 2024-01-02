# craig.mayhew.io

Craig Mayhew's Personal website

Blog posts are stored in blogposts/ as json files. One file per post.
Articles are stored in articles/ as json files. One file per page.
Pages are stored in pages/ as json files. One file per page.

## deploy

IPFS: https://app.fleek.co/ builds and hosts the IPFS version whenever it detects changes on the "main" github branch.
Web 2.0 builds run on github actions and deploy to AWS S3 + Cloudfront.

## partial local builds

You can specify what should be regenerated via arguments to build.php.  e.g. to deploy just the blog and pages
<pre>
php build.php blog pages 
</pre>

Or just regenerate the web pages and static content.
<pre>
php build.php pages static 
</pre>

## Deployment

S3 deploys were removed at commits [0c8dfe7](https://github.com/craigmayhew/craig.mayhew.io/commit/0c8dfe7c244a22091d0a0922a9cf41d6b03c56a5) and [15bf523](https://github.com/craigmayhew/craig.mayhew.io/commit/15bf5235329e6fe6d63e76b4168658136057a5c2). If you need the github + s3 deploy scripts for something else, look at those commits.

Deployment is now automated via fleek and cloudflare pages gothub integrations.
