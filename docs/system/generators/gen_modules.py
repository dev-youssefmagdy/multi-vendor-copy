import json,sys,re
from collections import OrderedDict
r=json.load(open(sys.argv[1]));C={c['class']:c for c in json.load(open(sys.argv[2]))}
reqs={k.split('\\')[-1]:v for k,v in C.items() if '\\Requests\\' in k}
reqfull={k:v for k,v in C.items() if '\\Requests\\' in k}
def perm(x):
    p=[m.split(':',1)[1] for m in x['middleware'] if ('AdminPermission:' in m or 'TenantPermission:' in m)]
    s=[m.split(':',1)[1] for m in x['middleware'] if 'SetupGuard:' in m]
    o=', '.join(p) if p else '—'
    if s:o+=' (setup: %s)'%','.join(s)
    return o
def cls(x):
    a=x['action'] or ''
    return a.split('@')[0]
def fmt_rules(rules):
    return '; '.join('`%s`: %s'%(k,v[:90]) for k,v in list(rules.items())[:40])
def describe(cn,methods_used):
    c=C.get(cn)
    if not c:return '- `%s` (closure or not reflected)\n'%cn
    out='- **`%s`** — [`%s`](../../%s) (%d lines)\n'%(cn.replace('App\\',''),c['file'],c['file'],c['loc'])
    if c['doc']:out+='  - Doc: %s\n'%c['doc'][:300]
    props=[p for p in c['props'] if p not in('paginators',)]
    if props:out+='  - Public state / form fields: %s\n'%', '.join('`%s`'%p for p in props[:60])
    NOISE={'mount','queryStringHandlesPagination','getPage','previousPage','nextPage','gotoPage','resetPage','setPage','paginationView','paginationSimpleView','updatingSearch'}
    ms=[m for m in c['methods'] if m not in NOISE]
    if ms:out+='  - Actions/methods: %s\n'%', '.join('`%s()`'%m for m in ms[:60])
    if c['rules']:out+='  - Validation: %s\n'%fmt_rules(c['rules'])
    if c['views']:out+='  - Views: %s\n'%', '.join('`%s`'%v for v in c['views'][:8])
    # form requests used
    try:src=open(c['file']).read()
    except:src=''
    used=sorted(set(re.findall(r'\b(\w+Request)\s+\$',src))-{'Request'})
    for u in used:
        rq=reqs.get(u)
        if rq and rq['rules']:out+='  - FormRequest `%s`: %s\n'%(u,fmt_rules(rq['rules']))
    return out
areas=[
 ('Super Admin panel','lambda',lambda x:x['domain']=='localhost' and x['uri'].startswith('admin'),1),
 ('Tenant (vendor) admin panel','lambda',lambda x:x['domain'] is None and x['uri'].startswith('admin'),1),
 ('Affiliate panel','',lambda x:x['domain']=='localhost' and x['uri'].startswith('affiliate'),0),
 ('Central website, registration & tenant-owner portal','',lambda x:x['domain']=='localhost' and not x['uri'].startswith(('admin','affiliate')),0),
 ('Tenant storefront (domain)','',lambda x:x['domain'] is None and not x['uri'].startswith(('admin','api','vendor','s/','_debug','livewire','storage','.well','apple','up')),0),
 ('Storefront JSON API (/api)','',lambda x:x['domain'] is None and x['uri'].startswith('api') and not x['uri'].startswith('api/mobile'),1),
 ('Mobile API (/api/mobile/v1)','',lambda x:x['domain'] is None and x['uri'].startswith('api/mobile'),3),
]
print('# Module & Component Inventory (generated)\n')
print('> Generated 2026-11-24 by joining `php artisan route:list --json` with PHP reflection of every class in `app/Livewire`, `app/Http/Controllers` and `app/Http/Requests`. For each route group (URI segment) it lists the routes with their required permission, then every handler class with its public state (Livewire form fields), public actions, validation rules (parsed from `rules()`, `#[Validate]`, inline `validate([...])` and type-hinted FormRequests) and views. Rules are truncated at 90 chars. This file is the traceability index behind the narrative chapters of `COMPLETE_SYSTEM_DOCUMENTATION.md`.\n')
for title,_,f,depth in areas:
    rows=[x for x in r if f(x)]
    print('\n## %s (%d routes)\n'%(title,len(rows)))
    groups=OrderedDict()
    for x in sorted(rows,key=lambda x:x['uri']):
        parts=x['uri'].split('/')
        key=parts[depth] if len(parts)>depth else '(root)'
        groups.setdefault(key or '(root)',[]).append(x)
    for g,xs in groups.items():
        print('\n### `%s`\n'%g)
        print('| Method | URI | Name | Permission | Handler |\n|---|---|---|---|---|')
        for x in xs:
            a=(x['action'] or '').replace('App\\Http\\Controllers\\','').replace('App\\Livewire\\','Livewire\\')
            print('| %s | `/%s` | `%s` | %s | `%s` |'%(x['method'].replace('|HEAD',''),x['uri'],x['name'] or '',perm(x),a))
        print()
        seen=[]
        for x in xs:
            c=cls(x)
            if c and c not in seen and c!='Closure' and not c.startswith('Illuminate'):seen.append(c)
        for c in seen:print(describe(c,None),end='')
