#!/usr/bin/env python3
"""Update a production installation; never uploads environment or storage."""
import json,subprocess
from pathlib import Path
root=Path(__file__).resolve().parent.parent
c=json.loads((root/'deploy/production.json').read_text())
archive=root/'deploy/artifacts/minh.tar.gz'
if not archive.exists(): raise SystemExit('Chạy scripts/package.py trước.')
remote=c['remote_directory'];host=c['ssh_alias']
subprocess.run(['scp',str(archive),f'{host}:{remote}-release.tar.gz'],check=True)
php=f"PHPRC={c['php_ini']} PHP_INI_SCAN_DIR={c['php_ini_scan_dir']} {c['php_binary']}"
# Values are tracked fixed deployment configuration, not user input or secrets.
cmd=f"cd ~/{remote} && test -f .env && test -d storage && tar --no-same-owner --no-overwrite-dir -xzf ../minh-release.tar.gz && {php} artisan package:discover && {php} artisan optimize:clear && {php} artisan view:cache"
subprocess.run(['ssh',host,cmd],check=True)
print('Đã cập nhật website: '+c['url'])
