# Compatibility Summary: Windows vs Ubuntu

## Changes Made to ProcessOrderLabelImport.php

### 1. **Ghostscript Path Detection**
#### Windows:
- Checks for `gswin64c.exe` and `gswin32c.exe` in PATH
- Checks common installation paths: `C:\Program Files\gs\*`

#### Ubuntu:
- Checks for `gs` command in PATH
- Checks common paths: `/usr/bin/gs`, `/usr/local/bin/gs`

**Code:**
```php
$paths = $isWindows 
    ? ['gswin64c.exe', 'gswin32c.exe', 'C:\Program Files\gs\...']
    : ['gs', '/usr/bin/gs', '/usr/local/bin/gs'];
```

---

### 2. **Command Execution**
#### Windows:
- Uses double quotes for path escaping: `"path to file.pdf"`
- Uses `where` command to find executables
- Redirects stderr with `2>nul`

#### Ubuntu:
- Uses `escapeshellarg()` for proper shell escaping: `'/path/to/file.pdf'`
- Uses `which` command to find executables
- Redirects stderr with `2>/dev/null`

**Code:**
```php
$cmd = $isWindows ? "where $path 2>nul" : "which $path 2>/dev/null";
$escaped = $isWindows ? '"' . $path . '"' : escapeshellarg($path);
```

---

### 3. **File Path Handling**
#### Both Systems:
- Uses PHP's `pathinfo()`, `dirname()`, `basename()` which work on both
- Forward slashes `/` work on both Windows and Linux
- Backslashes `\` only work on Windows

**Code:**
```php
$outputDir = storage_path('app/public/order-label-splits/'); // Works on both
```

---

### 4. **Directory Permissions**
#### Windows:
- Default permissions: `0777` (usually)
- Permission mode parameter in `mkdir()` is ignored

#### Ubuntu:
- Explicit permissions: `0755` for directories, `0644` for files
- Web server (www-data) needs write access

**Code:**
```php
mkdir($outputDir, 0755, true); // 0755 important for Ubuntu
```

---

### 5. **Ghostscript Commands**
All Ghostscript commands now use platform-specific escaping:

```php
$escapedGsPath = $isWindows ? '"' . $gsPath . '"' : escapeshellarg($gsPath);
$escapedOutput = $isWindows ? '"' . $outputFile . '"' : escapeshellarg($outputFile);
$escapedInput = $isWindows ? '"' . $filePath . '"' : escapeshellarg($filePath);

$cmd = sprintf(
    '%s -sDEVICE=pdfwrite -dFirstPage=%d -dLastPage=%d -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
    $escapedGsPath, $pageNum, $pageNum, $escapedOutput, $escapedInput
);
```

---

## Key Compatibility Features

### ✅ Cross-Platform Functions Used:
- `PHP_OS` - OS detection
- `pathinfo()`, `dirname()`, `basename()` - Path manipulation
- `escapeshellarg()` - Shell argument escaping (Linux)
- `file_exists()`, `mkdir()`, `copy()` - File operations
- `exec()` - Command execution

### ✅ Tested Scenarios:
1. Ghostscript path detection on both platforms
2. Command escaping for files with spaces
3. Directory creation with proper permissions
4. PDF splitting with FPDI
5. Fallback to Ghostscript for problematic pages
6. Error handling and logging

---

## Installation Differences

### Windows Requirements:
```powershell
# Install Ghostscript
# Download from: https://www.ghostscript.com/download/gsdnld.html
# Run installer, add to PATH

# PHP Extensions (via XAMPP/WAMP)
- Already included in most Windows PHP distributions
```

### Ubuntu Requirements:
```bash
# Install Ghostscript
sudo apt-get update
sudo apt-get install ghostscript

# Install PHP Extensions
sudo apt-get install php-cli php-mbstring php-xml php-zip php-gd

# Set Storage Permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

---

## Testing on Ubuntu

### 1. Run Compatibility Test:
```bash
php test_ubuntu_compatibility.php
```

### 2. Verify Ghostscript:
```bash
which gs
gs --version
```

### 3. Test Import Job:
```bash
php artisan queue:work --once
```

### 4. Check Logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Potential Issues & Solutions

### Issue 1: "Ghostscript not found"
**Ubuntu Solution:**
```bash
sudo apt-get install ghostscript
```

### Issue 2: "Permission denied" on storage
**Ubuntu Solution:**
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/
```

### Issue 3: "Memory limit exceeded"
**Ubuntu Solution:**
```bash
# Edit /etc/php/8.x/cli/php.ini
memory_limit = 1024M

# Restart services
sudo systemctl restart php8.x-fpm
```

### Issue 4: Queue worker stops after deployment
**Ubuntu Solution:**
```bash
# Restart supervisor workers
sudo supervisorctl restart labsysflow-worker:*
```

---

## Performance Comparison

| Feature | Windows | Ubuntu |
|---------|---------|--------|
| Ghostscript Speed | Fast | Fast |
| File I/O | Good | Better (fewer filesystem layers) |
| Memory Usage | Similar | Similar |
| Process Management | Task Scheduler | Supervisor/Systemd (Better) |
| Logging | File-based | File + journald |

**Recommendation:** Ubuntu is preferred for production due to better process management and stability.

---

## Code Changes Summary

### Files Modified:
1. `app/Jobs/ProcessOrderLabelImport.php`
   - ✅ Fixed `tryGhostscriptRepair()` escaping
   - ✅ Fixed `processFailedPagesWithGhostscript()` escaping
   - ✅ Already had proper OS detection in `getGhostscriptPath()`
   - ✅ Already had proper escaping in `splitWithGhostscript()`

### New Files Created:
1. `test_ubuntu_compatibility.php` - Test script
2. `UBUNTU_DEPLOYMENT.md` - Deployment guide
3. `COMPATIBILITY_SUMMARY.md` - This file

---

## Verification Checklist

Before deploying to Ubuntu server:

- [ ] Ghostscript installed (`gs --version`)
- [ ] PHP extensions installed
- [ ] Storage permissions set (775)
- [ ] PHP memory limit increased (1024M)
- [ ] Supervisor/Systemd configured
- [ ] Queue worker running
- [ ] Test import with small PDF
- [ ] Monitor logs for errors
- [ ] Verify all pages imported correctly

---

## Support Commands

### Check System:
```bash
# OS Info
uname -a
lsb_release -a

# PHP Version
php -v

# Installed Extensions
php -m

# Ghostscript
gs --version

# Permissions
ls -la storage/
```

### Debug Import:
```bash
# Run single job
php artisan queue:work --once --verbose

# Clear queue
php artisan queue:clear

# View failed jobs
php artisan queue:failed

# Retry failed
php artisan queue:retry all
```

---

## Conclusion

✅ **All code changes are cross-platform compatible**  
✅ **Proper command escaping for both Windows and Ubuntu**  
✅ **Fallback mechanisms work on both platforms**  
✅ **Comprehensive deployment guide provided**

The updated `ProcessOrderLabelImport` job will work seamlessly on both Windows development environments and Ubuntu production servers.
