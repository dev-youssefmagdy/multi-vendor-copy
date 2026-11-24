import json,sys
from collections import defaultdict
s=json.load(open(sys.argv[1]));m=json.load(open(sys.argv[2]));tm=json.load(open(sys.argv[3]))['tables']
mod=defaultdict(list)
for x in m:
    scope='tenant' if x['class'].startswith('Tenant\\') and not x['central'] else 'central'
    mod[(scope,x['table'].split('.')[-1])].append(x)
def modline(scope,n):
    for x in mod.get((scope,n),[]):
        print('Model `App\\Models\\%s`%s%s\n'%(x['class'],(' — traits: '+', '.join(x['traits'])) if x['traits'] else '',(' — relations: '+', '.join(x['rels'])) if x['rels'] else ''))
print('# Database Schema Reference (generated)\n')
print('> Generated 2026-11-24. **Central DB**: read from the live local MySQL central schema via `information_schema` (read-only SELECT). **Tenant DB**: no tenant database exists on this machine (the 5 registered tenants point to `dokan_v2_{tenant_id}` schemas that are absent), so the tenant section is **derived by parsing `database/migrations/tenant/*.php`** (create/alter/drop/rename handled; columns added inside dynamic loops or raw SQL may be missing — verify against a real tenant DB). Accuracy check of the same parser against the live central DB: 932 of 998 live columns reproduced. Model mapping comes from reflecting every class in `app/Models` (`CentralConnection` trait = always uses the central DB, even inside tenant context).\n')
print('Legend: `PK` primary, `UNI` unique, `MUL` indexed; `?` = nullable.\n')
# central
d=s['central'];cols=defaultdict(list);fks=defaultdict(list);idx=defaultdict(list)
for c in d['columns']:cols[c['t']].append(c)
for f in d['fks']:fks[f['t']].append(f)
for i in d['idx']:idx[i['t']].append(i)
print('\n## A. Central database — %d tables\n'%len(d['tables']))
print('| Table | ~Rows (dev) | Model(s) |\n|---|---|---|')
for t in d['tables']:
    ms=', '.join('`%s`'%x['class'] for x in mod.get(('central',t['n']),[]))
    print('| `%s` | %s | %s |'%(t['n'],t['r'] if t['r'] is not None else '',ms or '—'))
for t in d['tables']:
    n=t['n'];print('\n### central.`%s`\n'%n);modline('central',n)
    print('Columns: '+'; '.join('`%s` %s%s%s'%(c['c'],c['ty'],'?' if c['nl']=='YES' else '',(' '+c['k']) if c['k'] else '') for c in cols[n]))
    if fks[n]:print('\nFKs: '+'; '.join('`%s` → %s.%s'%(f['c'],f['rt'],f['rc']) for f in fks[n]))
    ii=[i for i in idx[n] if i['i']!='PRIMARY']
    if ii:print('\nIndexes: '+'; '.join('%s%s(%s)'%(i['i'],' UNIQUE' if str(i['nu'])=='0' else '',i['cols']) for i in ii))
# tenant
live=[k for k,v in tm.items() if not v.get('dropped_table') and v['created']]
extra=['banner_country','coupon_country','flash_sale_country']
print('\n## B. Tenant database (one per tenant) — %d tables (migration-derived)\n'%(len(live)+len(extra)))
print('Dropped by later migrations (do not rebuild): '+', '.join('`%s`'%k for k,v in tm.items() if v.get('dropped_table'))+'.\n')
print('Created in a loop by `2026_10_28_000001_create_country_pivots_for_banners_coupons_flash_sales.php` (not parsed): `banner_country`, `coupon_country`, `flash_sale_country` (`{entity}_id`, `country_id`; replaced the old single `country_id` columns).\n')
print('| Table | Created in | Model(s) |\n|---|---|---|')
for k in live:
    ms=', '.join('`%s`'%x['class'] for x in mod.get(('tenant',k),[]))
    print('| `%s` | `%s` | %s |'%(k,tm[k]['created'],ms or '—'))
for k in live:
    v=tm[k];print('\n### tenant.`%s`\n'%k);modline('tenant',k)
    print('Columns: '+'; '.join('`%s` %s'%(c,t) for c,t in v['cols'].items()))
    if v['fks']:print('\nFKs: '+'; '.join(v['fks']))
    if v['idx']:print('\nIndexes: '+'; '.join(v['idx']))
    if len(v['files'])>1:print('\nAltered by: '+', '.join('`%s`'%f for f in v['files'][1:]))
