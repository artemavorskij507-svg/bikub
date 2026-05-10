#!/usr/bin/env python3
"""
Direct HTTP-based deployment via Laravel API endpoint
"""

import requests
import json
from pathlib import Path

local_file = Path(r'C:\home\vscode\bikube\resources\views\public\delivery-market.blade.php')
server = 'http://136.119.84.22'

print(f'📦 Template file: {local_file.stat().st_size} bytes')

# Read the template with UTF-8 encoding
content = local_file.read_text(encoding='utf-8')

# Method 1: Try uploading via multipart form
print('\n🔄 Method 1: Multipart upload...')
try:
    files = {'template': content.encode()}
    r = requests.post(f'{server}/deploy-upload.php', files=files, timeout=10)
    print(f'Status: {r.status_code}')
    print(f'Response: {r.text[:200]}')
    if r.status_code == 200:
        print('✅ Success!')
except Exception as e:
    print(f'✗ Error: {e}')

# Method 2: Try raw POST with JSON
print('\n🔄 Method 2: Raw POST...')
try:
    r = requests.post(
        f'{server}/api/deploy-template',
        json={'template': content},
        headers={'Content-Type': 'application/json'},
        timeout=10
    )
    print(f'Status: {r.status_code}')
    print(f'Response: {r.text[:200]}')
except Exception as e:
    print(f'✗ Error: {e}')

# Method 3: Check if artisan is accessible
print('\n🔄 Method 3: Artisan endpoint...')
try:
    r = requests.get(f'{server}/artisan-deploy', timeout=10)
    print(f'Status: {r.status_code}')
except Exception as e:
    print(f'✗ Error: {e}')

# Method 4: Check for Laravel routes
print('\n🔄 Checking server endpoints...')
endpoints = [
    '/api/health',
    '/health',
    '/status',
    '/ping',
    '/api',
    '/admin',
    '/dashboard',
]
for ep in endpoints:
    try:
        r = requests.get(f'{server}{ep}', timeout=5)
        if r.status_code < 500:
            print(f'  ✓ {ep}: {r.status_code}')
    except:
        pass
