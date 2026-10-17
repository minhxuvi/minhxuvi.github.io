#!/usr/bin/env python3
"""Explicit, locked synchronization. Deliberately refuses the real notes repository."""
import argparse, hashlib, json, os, subprocess, sys
from pathlib import Path

class Stop(Exception): pass

def main():
    p=argparse.ArgumentParser(description='Đồng bộ chủ động repo minh-notes-demo; không tự stash/reset/force push.')
    p.add_argument('--repo', required=True, type=Path)
    sub=p.add_subparsers(dest='action',required=True)
    sub.add_parser('status'); sub.add_parser('pull')
    plan=sub.add_parser('plan'); plan.add_argument('files',nargs='+')
    publish=sub.add_parser('publish'); publish.add_argument('--review',required=True); publish.add_argument('--message',default='Cập nhật ghi chú mẫu từ Mac'); publish.add_argument('files',nargs='+')
    a=p.parse_args(); repo=a.repo.resolve()
    def git(*args,check=True):
        r=subprocess.run(['git','-C',str(repo),*args],text=True,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
        if check and r.returncode: raise Stop(r.stderr.strip() or r.stdout.strip())
        return r.stdout.strip()
    if Path(git('rev-parse','--show-toplevel')).resolve()!=repo: raise Stop('Phải chọn đúng thư mục gốc repo mẫu.')
    remote=git('remote','get-url','origin')
    if remote not in ['https://github.com/minhxuvi/minh-notes-demo.git','git@github.com:minhxuvi/minh-notes-demo.git']:
        raise Stop('Chỉ cho phép origin của minhxuvi/minh-notes-demo. Không thao tác kho thật.')
    if git('branch','--show-current')!='main': raise Stop('Chỉ hỗ trợ nhánh main của repo mẫu.')
    gitdir=Path(git('rev-parse','--absolute-git-dir')); lock=gitdir/'minh-sync.lock'
    try: lock.mkdir()
    except FileExistsError: raise Stop('Một phiên khác đang giữ khóa minh-sync.lock. Không thay đổi dữ liệu.')
    try:
        (lock/'owner').write_text(str(os.getpid()))
        if any((gitdir/name).exists() for name in ['index.lock','MERGE_HEAD','rebase-merge','rebase-apply','CHERRY_PICK_HEAD']): raise Stop('Git đang có thao tác dở hoặc xung đột. Hãy xử lý trước.')
        git('fetch','origin','main')
        head=git('rev-parse','HEAD'); online=git('rev-parse','origin/main')
        status=git('status','--short')
        if a.action=='status':
            print(json.dumps({'local':head,'github':online,'ahead_behind':git('rev-list','--left-right','--count','HEAD...origin/main'),'changes':status},ensure_ascii=False,indent=2)); return
        if a.action=='pull':
            if head==online: print('Đã ở phiên bản GitHub mới nhất. Giữ nguyên mọi thay đổi local.'); return
            if status: raise Stop('Local có thay đổi chưa commit/staged. Giữ nguyên; hãy xem và xử lý trước khi nhận phiên bản mới. Không tự stash.')
            r=subprocess.run(['git','-C',str(repo),'merge','--ff-only','origin/main'],text=True,capture_output=True)
            if r.returncode: raise Stop('Lịch sử đã phân nhánh. Giữ cả hai phía; cần đối chiếu và hợp nhất thủ công. '+r.stderr.strip())
            print(r.stdout.strip()); return
        files=[]
        for f in a.files:
            rel=Path(f)
            if rel.is_absolute() or any(x.startswith('.') for x in rel.parts) or not rel.parts or (repo/rel).is_symlink(): raise Stop('Đường dẫn file không hợp lệ.')
            if not (repo/rel).resolve().is_relative_to(repo): raise Stop('File nằm ngoài repo mẫu.')
            if rel.suffix.lower() not in ['.md','.mdx','.png','.jpg','.jpeg','.gif','.webp','.avif']: raise Stop('Chỉ chọn Markdown hoặc ảnh mẫu.')
            files.append(str(rel))
        staged=git('diff','--cached','--name-only','-z').split('\0')
        if any(f in staged for f in files): raise Stop('File được chọn đang có phần staged. Không thay staging của phiên khác; hãy tự đối chiếu trước.')
        snapshot={'head':head,'online':online,'files':{}}
        for f in sorted(set(files)):
            target=repo/f
            snapshot['files'][f]=hashlib.sha256(target.read_bytes()).hexdigest() if target.is_file() else None
        digest=hashlib.sha256(json.dumps(snapshot,sort_keys=True).encode()).hexdigest()
        if a.action=='plan':
            print(git('diff','HEAD','--',*files)); print(json.dumps(snapshot,ensure_ascii=False,indent=2)); print('REVIEW='+digest); return
        if digest!=a.review: raise Stop('HEAD, GitHub hoặc nội dung local đã đổi sau khi xem. Chạy plan lại; không commit/push.')
        if head!=online: raise Stop('Local và GitHub chưa cùng phiên bản. Nhận/hợp nhất trước, không force push.')
        if git('diff','--name-only','HEAD','--',*files)=='' and all(git('ls-files','--',f) for f in files): raise Stop('Không có thay đổi trong các file được chọn.')
        # --only commits selected paths and preserves unrelated index entries.
        for f in files:
            if not git('ls-files','--',f) and (repo/f).exists(): git('add','--intent-to-add','--',f)
        print(git('commit','--only','-m',a.message,'--',*files))
        print(git('push','origin','HEAD:main'))
        print('GitHub đã nhận phiên bản '+git('rev-parse','HEAD'))
    finally:
        (lock/'owner').unlink(missing_ok=True); lock.rmdir()

if __name__=='__main__':
    try: main()
    except (Stop,subprocess.SubprocessError,OSError) as e: print('DỪNG AN TOÀN: '+str(e),file=sys.stderr); sys.exit(1)
