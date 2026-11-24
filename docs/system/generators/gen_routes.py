import json,sys,re
from collections import defaultdict,OrderedDict
r=json.load(open(sys.argv[1]))
def area(x):
    d=x['domain'];u=x['uri']
    if d=='localhost':
        if u.startswith('admin'):return '1. Super Admin panel (central domain, /admin)'
        if u.startswith('affiliate'):return '3. Affiliate panel (central domain, /affiliate)'
        return '0. Central marketing website, registration & tenant-owner portal (central domain)'
    if d is not None:return None
    if u.startswith('api/mobile'):return '7. Mobile API (/api/mobile/v1)'
    if u.startswith('api'):return '6. Storefront/tenant JSON API (/api)'
    if u.startswith('admin'):return '2. Tenant (vendor) admin panel (tenant domain, /admin)'
    if u.startswith('vendor/') or u.startswith('s/'):return '5. Path-based storefront (central domain, /vendor/{tenant})'
    if u.startswith('_debugbar') or u.startswith('livewire') or u in('up',) or u.startswith('storage') :return '9. Framework / tooling'
    return '4. Tenant storefront (tenant subdomain / custom domain)'
skip_mw={'web'}
def mw(ms):
    out=[]
    for m in ms:
        s=m.split('\\')[-1]
        if s in skip_mw:continue
        out.append(s)
    return ', '.join(out)
g=defaultdict(list)
for x in r:
    a=area(x)
    if a: g[a].append(x)
print('# Route Inventory (generated)\n')
print('> Generated from `php artisan route:list --json` on 2026-11-24 (branch `refactor/tenant-central-dedup-plan`). Central-domain routes are registered once per entry in `config/tenancy.php` `central_domains` (dokan-v2.loc, 127.0.0.1, localhost) by `routes/web.php`; only the `localhost` copy is listed here. Middleware column omits the `web` group. `AdminPermission:x` / `TenantPermission:x` = required permission key.\n')
tot=0
for a in sorted(g):
    rows=sorted(g[a],key=lambda x:x['uri'])
    tot+=len(rows)
print('Total unique routes listed: %d (raw registered routes incl. duplicate central-domain copies: %d)\n'%(tot,len(r)))
print('| Area | Routes |\n|---|---|')
for a in sorted(g):print('| %s | %d |'%(a,len(g[a])))
for a in sorted(g):
    rows=sorted(g[a],key=lambda x:x['uri'])
    print('\n## %s\n'%a)
    # sub-group by second segment
    sub=OrderedDict()
    for x in rows:
        parts=x['uri'].split('/')
        if a.startswith(('1','2','3')): key=parts[1] if len(parts)>1 else '(root)'
        elif a.startswith('5'): key=parts[2] if len(parts)>2 else '(home)'
        elif a.startswith('7'): key=parts[3] if len(parts)>3 else '(root)'
        elif a.startswith('6'): key=parts[1] if len(parts)>1 else '(root)'
        else: key=parts[0] or '(home)'
        sub.setdefault(key,[]).append(x)
    for k,xs in sub.items():
        print('\n### `%s` (%d)\n'%(k,len(xs)))
        print('| Method | URI | Name | Action | Middleware |\n|---|---|---|---|---|')
        for x in xs:
            act=(x['action'] or '').replace('App\\Http\\Controllers\\','').replace('App\\Livewire\\','Livewire\\')
            print('| %s | `/%s` | %s | `%s` | %s |'%(x['method'].replace('|HEAD',''),x['uri'],x['name'] or '',act.replace('|','\\|'),mw(x['middleware']).replace('|','\\|')))
