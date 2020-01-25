# mgmse - Modern GameSpy Master Server Emulator
(Work in progress)
This is a small Laravel project that mimics a GameSpy v1 master server. I'm sorry I could'nt think of a better name!

## Requirements
 - Redis (Memurai works well on Windows!)
 - PHP 7.4
 - postgres (You shouldn't need this, since we don't store anything in the DB yet, but Laravel will probably complain.)
 
## How to run
The project currently consists of two artisan commands that will run long-living daemons, and one minutely cronjob.
You can run the daemons with:

`php artisan run:query-server`

`php artisan run:listing-server`

For the cronjob make sure you have a cronjob entry set for:

`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`

