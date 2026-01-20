#!/bin/bash

# LabSysFlow Ubuntu Quick Setup Script
# This script automates the setup process for Ubuntu servers

set -e

echo "=========================================="
echo "LabSysFlow Ubuntu Setup Script"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run with sudo: sudo bash setup_ubuntu.sh"
    exit 1
fi

# Get the actual user (not root)
ACTUAL_USER=${SUDO_USER:-$USER}
APP_DIR="/var/www/labsysflow"

echo "Setting up for user: $ACTUAL_USER"
echo "Application directory: $APP_DIR"
echo ""

# 1. Update system
echo "[1/8] Updating system packages..."
apt-get update -qq

# 2. Install Ghostscript
echo "[2/8] Installing Ghostscript..."
if ! command -v gs &> /dev/null; then
    apt-get install -y ghostscript
    echo "✓ Ghostscript installed"
else
    echo "✓ Ghostscript already installed"
fi
gs --version

# 3. Install PHP extensions (if not already installed)
echo "[3/8] Checking PHP extensions..."
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
echo "PHP Version: $PHP_VERSION"

REQUIRED_EXTENSIONS=("mbstring" "xml" "zip" "gd" "curl" "mysql")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✓ php-$ext already installed"
    else
        echo "Installing php-$ext..."
        apt-get install -y php-$ext
    fi
done

# 4. Configure PHP settings
echo "[4/8] Configuring PHP settings..."
PHP_CLI_INI="/etc/php/$PHP_VERSION/cli/php.ini"
PHP_FPM_INI="/etc/php/$PHP_VERSION/fpm/php.ini"

if [ -f "$PHP_CLI_INI" ]; then
    sed -i 's/memory_limit = .*/memory_limit = 1024M/' "$PHP_CLI_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 600/' "$PHP_CLI_INI"
    echo "✓ PHP CLI configured"
fi

if [ -f "$PHP_FPM_INI" ]; then
    sed -i 's/memory_limit = .*/memory_limit = 1024M/' "$PHP_FPM_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 600/' "$PHP_FPM_INI"
    sed -i 's/post_max_size = .*/post_max_size = 100M/' "$PHP_FPM_INI"
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' "$PHP_FPM_INI"

    systemctl restart php$PHP_VERSION-fpm
    echo "✓ PHP-FPM configured and restarted"
fi

# 5. Set storage permissions
echo "[5/8] Setting storage permissions..."
if [ -d "$APP_DIR" ]; then
    chown -R www-data:www-data "$APP_DIR/storage"
    chown -R www-data:www-data "$APP_DIR/bootstrap/cache"
    chmod -R 775 "$APP_DIR/storage"
    chmod -R 775 "$APP_DIR/bootstrap/cache"
    echo "✓ Permissions set"
else
    echo "⚠ Warning: $APP_DIR not found. Please set permissions manually after deployment."
fi

# 6. Install Supervisor
echo "[6/8] Installing Supervisor..."
if ! command -v supervisorctl &> /dev/null; then
    apt-get install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
    echo "✓ Supervisor installed"
else
    echo "✓ Supervisor already installed"
fi

# 7. Create Supervisor config
echo "[7/8] Creating Supervisor configuration..."
SUPERVISOR_CONF="/etc/supervisor/conf.d/labsysflow-worker.conf"

cat > "$SUPERVISOR_CONF" <<EOF
[program:labsysflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF

echo "✓ Supervisor config created: $SUPERVISOR_CONF"

# 8. Start queue workers
echo "[8/8] Starting queue workers..."
supervisorctl reread
supervisorctl update
sleep 2
supervisorctl start labsysflow-worker:*

echo ""
echo "=========================================="
echo "✓ Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Deploy your Laravel application to: $APP_DIR"
echo "2. Run: composer install"
echo "3. Run: php artisan migrate"
echo "4. Check worker status: sudo supervisorctl status"
echo ""
echo "Useful commands:"
echo "  - Restart workers: sudo supervisorctl restart labsysflow-worker:*"
echo "  - View logs: tail -f $APP_DIR/storage/logs/worker.log"
echo "  - Check queue: php artisan queue:work --once"
echo ""
echo "Configuration Summary:"
echo "  - Ghostscript: $(which gs)"
echo "  - PHP Version: $PHP_VERSION"
echo "  - Memory Limit: 1024M"
echo "  - Upload Limit: 100M"
echo "  - Queue Workers: 2 processes"
echo ""
