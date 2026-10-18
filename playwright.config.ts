import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir:'./tests/browser',workers:1,timeout:45000,
  use:{baseURL:'http://localhost:8080',browserName:'chromium',launchOptions:{executablePath:process.env.CHROMIUM_PATH??'/usr/bin/chromium',args:['--no-sandbox']},viewport:{width:390,height:844},trace:'off'},
  reporter:'list'
});
