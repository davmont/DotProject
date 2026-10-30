#!/usr/bin/env python3
"""Crawl the sandbox as one user and fail on pages that show errors.

    crawl.py <user> <password> <urls-out> [extra-urls-file]

Follows every index.php?m=... link (at most PER_MODULE pages per module, ids
normalised so a list of 50 tasks counts once), skipping anything that changes
data (dosql, do_*, delete, logout) or downloads a file. A page fails when it is
not HTTP 200 or its body shows an SQL error or a PHP error message. The
visited URLs go to <urls-out> for the browser checks.
"""
import collections
import html
import http.cookiejar
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('BASE', 'http://127.0.0.1:8089')
PER_MODULE = int(os.environ.get('PER_MODULE', '15'))
user, password, urls_out = sys.argv[1:4]
extra = open(sys.argv[4]).read().split() if len(sys.argv) > 4 else []

opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def get(url, data=None):
    try:
        body = urllib.parse.urlencode(data).encode() if data else None
        r = opener.open(urllib.parse.quote(url, safe=':/?&=%,#+[]'), body, timeout=60)
        return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', 'replace')


get(BASE + '/index.php')
_, body = get(BASE + '/index.php', {'login': 'login', 'username': user, 'password': password})
if 'logout' not in body.lower():
    sys.exit(f'FAIL  crawl {user}: login failed')

SKIP = re.compile(r'(^|[?&])(del|dosql|logout|delete)=|[?&]a=do_|[?&]a=(delete|remove)|export|suppressHeaders|csvexport|[?&]a=(pdf|print)', re.I)
ERRORS = re.compile(r'error was: ([^\n<]{0,140})|Error executing: ([^\n]{0,140})|<b>(?:Fatal error|Warning|Notice|Deprecated)</b>:\s*([^\n<]{0,140})')
queue = collections.deque(['index.php?' + u for u in extra] + ['index.php?m=companies'])
queue.extend(f'index.php?m={m}' for m in re.findall(r'href="(?:\./)?index\.php\?m=(\w+)', body))
seen, per_module, visited, failed = set(), collections.Counter(), [], 0
while queue:
    rel = queue.popleft()
    key = re.sub(r'(_id|id)=\d+', r'\1=N', rel)
    if key in seen:
        continue
    seen.add(key)
    module = (re.search(r'm=(\w+)', rel) or [None, '?'])[1]
    is_extra = rel[len('index.php?'):] in extra
    if per_module[module] >= PER_MODULE and not is_extra:
        continue
    per_module[module] += 1
    status, page = get(BASE + '/' + rel)
    problems = sorted({next(g for g in m.groups() if g) for m in ERRORS.finditer(page)})
    if status != 200:
        problems.insert(0, f'HTTP {status}')
    if problems:
        failed += 1
        print(f'FAIL  {rel}: ' + ' | '.join(problems)[:400])
    visited.append(rel[len('index.php?'):])
    for href in re.findall(r'(?:href|action)=["\']((?:\./)?(?:index\.php)?\?m=[^"\'#]+)["\']', page):
        href = 'index.php' + html.unescape(href).lstrip('./').removeprefix('index.php')
        if not SKIP.search(href):
            queue.append(href)

with open(urls_out, 'w') as f:
    f.write('\n'.join(visited) + '\n')
print(f'{"FAIL" if failed else "PASS"}  crawl {user}: {len(visited)} pages, {failed} with errors')
sys.exit(1 if failed else 0)
