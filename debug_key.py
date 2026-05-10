#!/usr/bin/env python3
"""Debug SSH key"""

from pathlib import Path

ssh_key = Path(r'C:\Users\New\.ssh\bikube_key.pem')
content = ssh_key.read_text()

lines = content.split('\n')
print(f'Line 1: {lines[0]}')
print(f'Line 2: {lines[1]}')
print(f'Total lines: {len(lines)}')
print(f'File size: {ssh_key.stat().st_size} bytes')
print(f'Contains "ENCRYPTED": {"ENCRYPTED" in content}')
print(f'Contains "PRIVATE KEY": {"PRIVATE KEY" in content}')

# Try loading with paramiko
import paramiko

print('\n🔄 Attempting to load key...')
try:
    # Try without passphrase
    key = paramiko.RSAKey.from_private_key_file(str(ssh_key))
    print('✓ Loaded as RSAKey')
except Exception as e:
    print(f'✗ RSAKey failed: {e}')
    
try:
    key = paramiko.Ed25519Key.from_private_key_file(str(ssh_key))
    print('✓ Loaded as Ed25519Key')
except Exception as e:
    print(f'✗ Ed25519Key failed: {e}')

# Try with common passphrases
for pwd in ['', 'glf2024!', 'bikube', 'password']:
    try:
        key = paramiko.RSAKey.from_private_key_file(str(ssh_key), password=pwd)
        print(f'✓ Loaded with passphrase: "{pwd}"')
        break
    except:
        pass
