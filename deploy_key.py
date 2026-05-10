#!/usr/bin/env python3
"""SFTP via SSH key - support multiple key types"""

import paramiko
from pathlib import Path

local_file = Path(r'C:\home\vscode\bikube\resources\views\public\delivery-market.blade.php')
ssh_key = Path(r'C:\Users\New\.ssh\bikube_key.pem')
remote_file = '/var/www/bikube/resources/views/public/delivery-market.blade.php'

print(f'✓ Local file: {local_file.stat().st_size} bytes')
print(f'✓ SSH key: {ssh_key}')

try:
    # Try to load key with different formats
    pkey = None
    for key_class in [paramiko.Ed25519Key, paramiko.RSAKey, paramiko.ECDSAKey]:
        try:
            pkey = key_class.from_private_key_file(str(ssh_key))
            print(f'✓ Key type: {key_class.__name__}')
            break
        except:
            pass
    
    if not pkey:
        print('✗ Could not load key with any format')
        exit(1)
    
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    
    for user in ['bikubeno', 'glf', 'ubuntu', 'root']:
        print(f'\n🔄 Trying {user}...')
        try:
            ssh.connect('136.119.84.22', username=user, pkey=pkey, timeout=10,
                       look_for_keys=False, allow_agent=False)
            print(f'✓ Connected as {user}!')
            
            sftp = ssh.open_sftp()
            print(f'📤 Uploading...')
            sftp.put(str(local_file), remote_file)
            
            stat = sftp.stat(remote_file)
            print(f'✅ Success! File: {stat.st_size} bytes')
            
            # Verify with curl
            stdin, stdout, stderr = ssh.exec_command(f'curl -s http://localhost/category/delivery | head -c 30')
            result = stdout.read().decode('utf-8', errors='ignore')
            if result:
                print(f'✓ Server response (first 30 chars): {result[:30]}')
            
            sftp.close()
            ssh.close()
            break
        except paramiko.AuthenticationException:
            print(f'✗ Auth failed for {user}')
            ssh.close()
            ssh = paramiko.SSHClient()
            ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
except Exception as e:
    import traceback
    print(f'✗ Error: {e}')
    traceback.print_exc()
