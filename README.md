# mgmse - Modern Game Master Server Emulator
Originally this platform was meant to emulate GameSpy v1 master servers. However additional features needed adding, and 
it eventually grew past that original goal. It can still handle basic GameSpy query/publishing services, 
but everything else is custom built.

## Requirements
 - Redis
 - PHP 7.4
 - postgres (You shouldn't need this, since we don't store anything in the DB yet, but Laravel will probably complain.)
 - Nginx (If you've enabled the json api feature)
 
## How to run
The project currently consists of two artisan commands that will run long-living daemons, and one minutely cronjob.
You can run the daemons with:

`php artisan run:query-server`

`php artisan run:listing-server`

For the cronjob make sure you have a cronjob entry set for:

`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`

## No Track feature
If there's a server that doesn't want to be included in the json api they can simply add `[NT]` to their server name, and the json api will skip reporting it.
