#!/usr/bin/env python3
"""Package only allowlisted source and production dependencies, without runtime state."""
import os, shutil, subprocess, tarfile
from pathlib import Path
root=Path(__file__).resolve().parent.parent
stage=root/'deploy/artifacts/stage'
if stage.exists(): shutil.rmtree(stage)
stage.mkdir(parents=True)
for folder in ['app','bootstrap','config','content','public','resources','routes']:
    shutil.copytree(root/folder,stage/folder,ignore=shutil.ignore_patterns('._*','.DS_Store'))
for file in ['artisan','composer.json','composer.lock']: shutil.copy2(root/file,stage/file)
for file in (stage/'bootstrap/cache').glob('*.php'): file.unlink()
composer = [os.environ['PHP_BINARY'], shutil.which('composer')] if os.environ.get('PHP_BINARY') else ['composer']
subprocess.run([*composer,'install','--working-dir='+str(stage),'--no-dev','--no-scripts','--no-interaction','--prefer-dist'],check=True)
archive=root/'deploy/artifacts/minh.tar.gz'
with tarfile.open(archive,'w:gz',format=tarfile.GNU_FORMAT) as tar:
    tar.add(stage,arcname='.',filter=lambda info: None if Path(info.name).name.startswith('._') else info)
print(archive.relative_to(root))
