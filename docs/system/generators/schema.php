<?php
require '/var/www/multi-vendor/vendor/autoload.php';
$app = require '/var/www/multi-vendor/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = DB::connection(config('tenancy.database.central_connection'));
$central = $db->getDatabaseName();
$tenants = $db->select("select SCHEMA_NAME s from information_schema.SCHEMATA where SCHEMA_NAME like ? order by s", [str_replace('_','\\_',config('tenancy.database.prefix')).'%']);
$tenantDbs = array_map(fn($r)=>$r->s, $tenants);
$pick = null; $best=0;
foreach ($tenantDbs as $t) { if ($db->selectOne("select count(*) c from information_schema.TABLES where TABLE_SCHEMA=? and TABLE_NAME in ('tenants')",[$t])->c > 0 || $db->selectOne("select count(*) c from information_schema.TABLES where TABLE_SCHEMA=? and TABLE_NAME='orders'",[$t])->c == 0) continue; $c = $db->selectOne("select count(*) c from information_schema.TABLES where TABLE_SCHEMA=?",[$t])->c; if ($c>$best){$best=$c;$pick=$t;} }
$out = ['central_name'=>$central,'tenant_db_count'=>count($tenantDbs),'tenant_sample'=>$pick ? 'tenant sample (largest schema)' : null];
foreach (['central'=>$central,'tenant'=>$pick] as $k=>$schema) {
  if(!$schema) continue;
  $out[$k]['tables'] = $db->select("select TABLE_NAME n, TABLE_TYPE t, TABLE_ROWS r from information_schema.TABLES where TABLE_SCHEMA=? order by TABLE_NAME",[$schema]);
  $out[$k]['columns'] = $db->select("select TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty, IS_NULLABLE nl, COLUMN_KEY k, COLUMN_DEFAULT d from information_schema.COLUMNS where TABLE_SCHEMA=? order by TABLE_NAME, ORDINAL_POSITION",[$schema]);
  $out[$k]['fks'] = $db->select("select TABLE_NAME t, COLUMN_NAME c, REFERENCED_TABLE_SCHEMA rs, REFERENCED_TABLE_NAME rt, REFERENCED_COLUMN_NAME rc from information_schema.KEY_COLUMN_USAGE where TABLE_SCHEMA=? and REFERENCED_TABLE_NAME is not null",[$schema]);
  $out[$k]['idx'] = $db->select("select TABLE_NAME t, INDEX_NAME i, NON_UNIQUE nu, group_concat(COLUMN_NAME order by SEQ_IN_INDEX) cols from information_schema.STATISTICS where TABLE_SCHEMA=? group by TABLE_NAME, INDEX_NAME, NON_UNIQUE",[$schema]);
}
file_put_contents($argv[1], json_encode($out));
echo "ok central tables=".count($out['central']['tables'])." tenant tables=".count($out['tenant']['tables'] ?? [])." tenant dbs=".count($tenantDbs)."\n";
