const { chromium } = require('@playwright/test');
(async()=>{
 const b=await chromium.launch({headless:true}); const p=await b.newPage();
 await p.goto('http://136.119.84.22/admin/login',{waitUntil:'domcontentloaded'});
 await p.fill('input[type="email"]','keks@glf.no');
 await p.fill('input[type="password"]','6636');
 await Promise.all([p.waitForLoadState('networkidle'), p.click('button[type="submit"]')]);
 await p.goto('http://136.119.84.22/admin/a-i-agent-team-chat',{waitUntil:'domcontentloaded'});
 const html=(await p.content()).toLowerCase();
 const idx=html.indexOf('server error');
 console.log('idx',idx,'title',await p.title());
 if(idx>=0){ console.log(html.substring(Math.max(0,idx-120), idx+160)); }
 await b.close();
})();
