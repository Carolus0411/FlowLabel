## About

Verola official website.

## Requirements

-   PHP 8.3+
-   Composer 2.4+
-   Node JS 22+
-   PostgreSQL 15+
-   Supervisor

## Installation

Clone the repository then go into the application folder

```
git clone https://github.com/akhmads/verola

cd verola
```

Install composer and node dependencies

```
composer install

npm install
```

Setup your environment

```
cp .env.example .env
```

.env

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

Generate Key

```
php artisan key:generate
```

Build assets

```
npm run build
```

Run migration

```
php artisan migrate
```

Link storage to public

```
php artisan storage:link
```

Change directory permission (for ubuntu linux server)

```
sudo chown -R www-data:www-data ./storage
sudo chown -R www-data:www-data ./bootstrap/cache
sudo chmod -R o+w ./storage
sudo chmod -R o+w ./bootstrap/cache
```

Run server

```
php artisan serve
```

## Running Queue and Scheduler

On local environment

```
php artisan schedule:work

php artisan queue:listen --queue=default --timeout=1800
```

Scheduler on cron jobs

```
* * * * * cd /var/www/path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Queue on supervisor

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-project/artisan queue:work --queue=default --timeout=1800
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/your-project/laravel-worker.log
stopwaitsecs=1800
```
