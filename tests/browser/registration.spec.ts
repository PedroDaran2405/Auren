import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
const run=(code:string)=>execFileSync('docker',['compose','-f','compose.test.yml','exec','-T','wp','php','-r','require "/var/www/html/wp-load.php"; if(wp_get_environment_type()!=="local")exit(1);'+code],{encoding:'utf8'});
let original='0';const email=`signup-browser-${Date.now()}@example.invalid`;
test.beforeAll(()=>{original=run('echo (int)get_option("users_can_register");update_option("users_can_register",true);');});
test.afterAll(()=>{run(`update_option('users_can_register',${Number(original)?'true':'false'});require_once ABSPATH.'wp-admin/includes/user.php';$u=get_user_by('email','${email}');if($u)wp_delete_user($u->ID);`);});
test('new user can register from the app with the platform fields and an activation notice',async({page})=>{
 // This browser case uses a clearly identified BuddyBoss signup fixture and real WP storage.
 await page.goto('/?auren_app=1');await page.getByRole('button',{name:'Crear una cuenta',exact:true}).click();await expect(page.getByRole('heading',{name:'Crea tu cuenta'})).toBeVisible();
 await expect(page.getByRole('button',{name:'Crear mi cuenta',exact:true})).toBeDisabled();
 await page.getByRole('textbox',{name:'Correo electrónico',exact:true}).fill(email);await page.getByLabel('Contraseña',{exact:true}).fill('Fixture-Signup-Only-123!');await page.getByRole('textbox',{name:'Nombre',exact:true}).fill('Alumno de prueba');
 await page.getByRole('checkbox',{name:'Youtube',exact:true}).click();await page.getByRole('checkbox',{name:'Acepto los términos y la privacidad',exact:true}).click();
 await page.getByRole('button',{name:'Crear mi cuenta',exact:true}).click();await expect(page.getByRole('heading',{name:'Revisa tu correo',exact:true})).toBeVisible();
 await expect(page.getByText(/Tu solicitud se envió/)).toBeVisible();expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
 await page.screenshot({path:'artifacts/registration-submitted.png',fullPage:true});
});
