import re,os,sys,json
from collections import OrderedDict,defaultdict
d=sys.argv[1]
tables=OrderedDict()
views=[]
for f in sorted(os.listdir(d)):
    if not f.endswith('.php'):continue
    src=re.sub(r'(?m)^\s*//.*$','',open(os.path.join(d,f)).read())
    src=re.sub(r'/\*.*?\*/','',src,flags=re.S)
    up=src
    i=src.find('function down')
    if i>=0:
        j=src.find('{',i);depth=0;k=j
        while k<len(src):
            if src[k]=='{':depth+=1
            elif src[k]=='}':
                depth-=1
                if depth==0:break
            k+=1
        up=src[:i]+src[k+1:]
    for m in re.finditer(r"""Schema::(create|table)\(\s*['"]([\w]+)['"]\s*,\s*(?:static\s+)?function[^{]*\{(.*?)\n\s*\}\);""",up,re.S):
        kind,t,body=m.groups()
        T=tables.setdefault(t,{'created':None,'cols':OrderedDict(),'fks':[],'idx':[],'files':[],'dropped':[]})
        if kind=='create':T['created']=f
        T['files'].append(f)
        for fe in re.finditer(r"foreach\s*\(\s*\[([^\]]*)\]\s*as\s*\$(\w+)\s*\)",body):
            if re.search(r"dropColumn\(\s*\$"+fe.group(2),body):
                for n in re.findall(r"'([\w]+)'",fe.group(1)):T['cols'].pop(n,None);T['dropped'].append(n)
        for c in re.finditer(r"\$table->(\w+)\(([^;]*?)\)([^;]*);",body):
            typ,args,chain=c.groups()
            names=re.findall(r"'([\w]+)'",args)
            if typ in('id',):T['cols']['id']='bigint PK';continue
            if typ in('timestamps','timestampsTz'):T['cols']['created_at']='timestamp?';T['cols']['updated_at']='timestamp?';continue
            if typ=='softDeletes':T['cols']['deleted_at']='timestamp?';continue
            if typ=='rememberToken':T['cols']['remember_token']='string?';continue
            if typ in('dropColumn','dropColumnIfExists'):
                for n in re.findall(r"'([\w]+)'",args):T['cols'].pop(n,None);T['dropped'].append(n)
                continue
            if typ=='renameColumn' and len(names)==2:
                v=T['cols'].pop(names[0],'?');T['cols'][names[1]]=v;continue
            if typ in('index','unique','fullText','spatialIndex'):
                T['idx'].append('%s(%s)'%(typ,','.join(n for n in re.findall(r"'([\w]+)'",args.split(']')[0] if '[' in args else args)[:6])));continue
            if typ=='primary':T['idx'].append('primary(%s)'%','.join(names));continue
            if typ in('foreign',):
                ref=re.search(r"references\('(\w+)'\)->on\('(\w+)'\)",chain)
                if names:T['fks'].append('%s → %s'%(names[0],('%s.%s'%(ref.group(2),ref.group(1))) if ref else '?'));continue
            if typ.startswith('drop') or typ in('after',):continue
            if typ=='morphs' or typ=='nullableMorphs':
                if names:T['cols'][names[0]+'_type']='string';T['cols'][names[0]+'_id']='bigint';continue
            if not names:continue
            n=names[0]
            nullable='->nullable()' in chain
            T['cols'][n]=typ+('?' if nullable else '')+(' UNI' if '->unique()' in chain else '')
            con=re.search(r"constrained\(\s*'?(\w*)'?",chain)
            if typ=='foreignId' and con:
                T['fks'].append('%s → %s.id%s'%(n,con.group(1) or (n[:-3]+'s'),' (cascade)' if 'cascadeOnDelete' in chain or "onDelete('cascade')" in chain else ''))
    for v in re.finditer(r"CREATE\s+(?:OR\s+REPLACE\s+)?(?:ALGORITHM=\w+\s+)?VIEW\s+`?(\w+)`?",src,re.I):views.append((v.group(1),f))
    for m in re.finditer(r"Schema::(dropIfExists|drop)\('(\w+)'\)",up):
        if m.group(2) in tables:tables[m.group(2)]['dropped_table']=f
json.dump({'tables':tables,'views':views},open(sys.argv[2],'w'))
print(len(tables),len(views))
