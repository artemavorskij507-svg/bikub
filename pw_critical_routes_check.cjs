const fs = require('fs');
const routes = [
'http://136.119.84.22/',
'http://136.119.84.22/category/delivery',
'http://136.119.84.22/category/moving',
'http://136.119.84.22/category/handyman',
'http://136.119.84.22/category/eco',
'http://136.119.84.22/category/personal-task',
'http://136.119.84.22/category/tow',
'http://136.119.84.22/classifieds',
'http://136.119.84.22/catalog',
'http://136.119.84.22/stores',
'http://136.119.84.22/restaurants',
'http://136.119.84.22/account',
'http://136.119.84.22/account/orders',
'http://136.119.84.22/account/profile',
'http://136.119.84.22/lk',
'http://136.119.84.22/lk/orders',
'http://136.119.84.22/lk/support',
'http://136.119.84.22/admin',
'http://136.119.84.22/admin/login',
'http://136.119.84.22/api/v1/health',
'http://136.119.84.22/api/v1/categories',
'http://136.119.84.22/api/v1/service-types',
'http://136.119.84.22/api/v1/restaurants',
'http://136.119.84.22/api/v1/stores',
'http://136.119.84.22/api/v1/public/catalog',
'http://136.119.84.22/api/v1/public/slots',
'http://136.119.84.22/api/ops/map/live',
'http://136.119.84.22/api/ops/jobs',
'http://136.119.84.22/api/ops/executors',
'http://136.119.84.22/api/ops/exceptions',
'http://136.119.84.22/api/ops/workbench/triage',
'http://136.119.84.22/api/ops/workbench/saved-filters',
'http://136.119.84.22/api/ops/workbench/replan-recommendations',
'http://136.119.84.22/api/ops/workbench/routing-shadow-metrics',
'http://136.119.84.22/api/ops/workbench/routing-provider-health'
];
(async () => {
 const out=[];
 for(const url of routes){
  try{
   const res = await fetch(url,{redirect:'manual'});
   out.push({url,status:res.status,location:res.headers.get('location')});
  }catch(e){ out.push({url,status:'ERR',error:String(e.message||e)}); }
 }
 fs.writeFileSync('audit/_critical_route_checks_node.json', JSON.stringify(out,null,2));
 console.log(JSON.stringify({checked:out.length, errors:out.filter(x=>x.status==='ERR').length},null,2));
})();
