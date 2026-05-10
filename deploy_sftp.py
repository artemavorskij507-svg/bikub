#!/usr/bin/env python3
"""
SFTP deployment script for delivery-market.blade.php
Usage: python deploy_sftp.py <user> <password> <host>
"""

import sys
import os
import paramiko
from pathlib import Path

def deploy_template(user, password, host, port=22):
    """Deploy template via SFTP"""
    
    local_file = Path('c:\\home\\vscode\\bikube\\resources\\views\\public\\delivery-market.blade.php')
    remote_file = '/var/www/bikube/resources/views/public/delivery-market.blade.php'
    
    if not local_file.exists():
        print(f'✗ Local file not found: {local_file}')
        return False
    
    print(f'✓ Local file: {local_file.stat().st_size} bytes')
    
    try:
        # Create SSH client
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        
        print(f'🔌 Connecting to {user}@{host}:{port}...')
        ssh.connect(host, port=port, username=user, password=password, 
                   look_for_keys=False, allow_agent=False, timeout=10)
        
        # Get SFTP client
        sftp = ssh.open_sftp()
        
        # Ensure remote directory exists
        try:
            sftp.stat(os.path.dirname(remote_file))
        except FileNotFoundError:
            print(f'📁 Creating directory: {os.path.dirname(remote_file)}')
            sftp.mkdir(os.path.dirname(remote_file), mode=0o755)
        
        # Upload file
        print(f'📤 Uploading to {remote_file}...')
        sftp.put(str(local_file), remote_file)
        
        # Verify
        stat = sftp.stat(remote_file)
        print(f'✓ Deployed: {stat.st_size} bytes')
        
        # Check via SSH
        stdin, stdout, stderr = ssh.exec_command(f'ls -lh {remote_file} && curl -s http://localhost/category/delivery | head -c 50')
        print(f'📋 Server check:')
        print(stdout.read().decode('utf-8', errors='ignore'))
        
        sftp.close()
        ssh.close()
        print('✅ Deployment successful!')
        return True
        
    except paramiko.AuthenticationException as e:
        print(f'✗ Authentication failed: {e}')
        return False
    except Exception as e:
        print(f'✗ Error: {e}')
        return False

if __name__ == '__main__':
    # Try multiple credentials
    credentials = [
        ('bikubeno', 'glf2024!', '136.119.84.22'),
        ('glf', 'glf2024!', '136.119.84.22'),
        ('root', 'glf2024!', '136.119.84.22'),
    ]
    
    for user, pwd, host in credentials:
        print(f'\n🔄 Trying {user}@{host}...')
        if deploy_template(user, pwd, host):
            break
    else:
        print('\n✗ All credentials failed!')
