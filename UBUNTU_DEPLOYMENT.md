# Ubuntu Deployment Guide - LabSysFlow

## Prerequisites

### 1. Install Ghostscript (Required for PDF Processing)
```bash
sudo apt-get update
sudo apt-get install ghostscript -y

# Verify installation
gs --version
```

### 2. Install PHP Extensions
```bash
sudo apt-get install php-cli php-mbstring php-xml php-zip php-gd php-curl php-mysql -y
```

### 3. Configure PHP Settings
Edit `/etc/php/8.x/cli/php.ini` and `/etc/php/8.x/fpm/php.ini`:

```ini
memory_limit = 1024M
max_execution_time = 600
post_max_size = 100M
upload_max_filesize = 100M
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.x-fpm
```

## Storage Permissions

Set proper permissions for Laravel storage:
```bash
cd /var/www/labsysflow

# Set ownership to web server user
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache

# If using different user (e.g., ubuntu), add to www-data group
sudo usermod -a -G www-data ubuntu
```

## Queue Worker Setup

### Option 1: Supervisor (Recommended)

#### Install Supervisor
```bash
sudo apt-get install supervisor -y
```

#### Create Supervisor Configuration
Create file: `/etc/supervisor/conf.d/labsysflow-worker.conf`

```ini
[program:labsysflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/labsysflow/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/labsysflow/storage/logs/worker.log
stopwaitsecs=3600
```

#### Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start labsysflow-worker:*

# Check status
sudo supervisorctl status
```

#### Useful Supervisor Commands
```bash
# Restart workers
sudo supervisorctl restart labsysflow-worker:*

# Stop workers
sudo supervisorctl stop labsysflow-worker:*

# View logs
sudo tail -f /var/www/labsysflow/storage/logs/worker.log
```

### Option 2: Systemd Service

Create file: `/etc/systemd/system/labsysflow-worker.service`

```ini
[Unit]
Description=LabSysFlow Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/labsysflow
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable labsysflow-worker
sudo systemctl start labsysflow-worker

# Check status
sudo systemctl status labsysflow-worker

# View logs
sudo journalctl -u labsysflow-worker -f
```

## Cron Job Setup (Optional)

For scheduled tasks, add to crontab:

```bash
sudo crontab -e -u www-data
```

Add this line:
```
* * * * * cd /var/www/labsysflow && php artisan schedule:run >> /dev/null 2>&1
```

## Testing the Setup

### 1. Test Ghostscript
```bash
gs --version
which gs
```

### 2. Test PHP Configuration
```bash
php -i | grep memory_limit
php -i | grep max_execution_time
```

### 3. Test Queue Worker
```bash
# Run manually first
cd /var/www/labsysflow
php artisan queue:work --once

# Check for errors
tail -f storage/logs/laravel.log
```

### 4. Test File Permissions
```bash
# Try creating a file in storage
sudo -u www-data touch storage/app/test.txt

# If successful, remove it
sudo rm storage/app/test.txt
```

## Troubleshooting

### Permission Denied Errors
```bash
# Reset all permissions
sudo chown -R www-data:www-data /var/www/labsysflow
sudo chmod -R 755 /var/www/labsysflow
sudo chmod -R 775 /var/www/labsysflow/storage
sudo chmod -R 775 /var/www/labsysflow/bootstrap/cache
```

### Queue Worker Not Processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart labsysflow-worker:*

# Clear failed jobs
php artisan queue:flush
php artisan queue:restart
```

### Ghostscript Not Found
```bash
# Install ghostscript
sudo apt-get install ghostscript

# Check installation
which gs
gs --version
```

### Memory Issues
```bash
# Increase PHP memory limit
sudo sed -i 's/memory_limit = .*/memory_limit = 1024M/' /etc/php/8.x/cli/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 1024M/' /etc/php/8.x/fpm/php.ini

# Restart services
sudo systemctl restart php8.x-fpm
sudo supervisorctl restart labsysflow-worker:*
```

## Monitoring

### Check Worker Status
```bash
# Supervisor
sudo supervisorctl status

# Systemd
sudo systemctl status labsysflow-worker
```

### View Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Worker logs (supervisor)
tail -f storage/logs/worker.log

# Worker logs (systemd)
sudo journalctl -u labsysflow-worker -f
```

### Check Queue Status
```bash
php artisan queue:listen --timeout=0
```

## Performance Optimization

### 1. Use Redis for Queues (Optional)
```bash
# Install Redis
sudo apt-get install redis-server php-redis

# Update .env
QUEUE_CONNECTION=redis
```

### 2. Increase Worker Processes
Edit supervisor config to increase `numprocs`:
```ini
numprocs=4  # Increase based on server capacity
```

### 3. Enable OPcache
Edit `/etc/php/8.x/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

## Security Notes

1. Never run queue workers as root
2. Keep storage directories outside web root if possible
3. Regularly rotate logs to prevent disk space issues
4. Use firewall to restrict access to sensitive ports
5. Keep Ghostscript updated for security patches

## Updating After Code Changes

```bash
# Pull latest code
cd /var/www/labsysflow
git pull

# Update dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Restart workers
sudo supervisorctl restart labsysflow-worker:*

# Or for systemd
sudo systemctl restart labsysflow-worker
```
