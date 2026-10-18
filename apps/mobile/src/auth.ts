import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import * as Crypto from 'expo-crypto';
import * as WebBrowser from 'expo-web-browser';
import { AurenApi, type Bootstrap, type Session, validateBaseUrl } from '@auren/api-client';

declare global { var __AUREN_BOOTSTRAP__: Bootstrap | undefined; }
const key = 'auren.session.v1';
const redirect = 'auren://auth/callback';
export function bootstrap(): Bootstrap | undefined { return Platform.OS === 'web' ? globalThis.__AUREN_BOOTSTRAP__ : undefined; }
export function apiBase() {
  const b = bootstrap();
  if (Platform.OS === 'web') {
    if (!b || new URL(b.api_url).origin !== globalThis.location.origin) return null;
    return b.api_url;
  }
  return validateBaseUrl(process.env.EXPO_PUBLIC_WORDPRESS_URL ?? 'https://www.aurenfx.com') + 'wp-json/auren/v1/';
}
export async function restoreSession(): Promise<Session | null> {
  if (Platform.OS === 'web') return null;
  const raw = await SecureStore.getItemAsync(key);
  if (!raw) return null;
  try {
    const session = JSON.parse(raw) as Session;
    if (typeof session.access_token !== 'string' || session.expires_at <= Date.now()/1000) { await clearSession(); return null; }
    return session;
  } catch { await clearSession(); return null; }
}
export async function clearSession() { if (Platform.OS !== 'web') await SecureStore.deleteItemAsync(key); }
export async function signIn(): Promise<Session | null> {
  const b = bootstrap();
  if (Platform.OS === 'web') {
    if (!b) throw new Error('El acceso estará disponible desde la app conectada a tu academia.');
    if (new URL(b.login_url).origin !== globalThis.location.origin) throw new Error('Dirección de acceso no válida.');
    globalThis.location.assign(b.login_url); return null;
  }
  const base = apiBase(); if (!base) throw new Error('No se encontró la academia.');
  const verifier = Array.from(Crypto.getRandomBytes(32),b=>b.toString(16).padStart(2,'0')).join('');
  const state = Array.from(Crypto.getRandomBytes(32),b=>b.toString(16).padStart(2,'0')).join('');
  const challenge = (await Crypto.digestStringAsync(Crypto.CryptoDigestAlgorithm.SHA256,verifier,{encoding:Crypto.CryptoEncoding.BASE64})).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
  const api = new AurenApi({baseUrl:base});
  try {
    const start = await api.request<{request_id:string;authorize_url:string}>('/auth/start','POST',{challenge,state});
    if (new URL(start.authorize_url).origin !== new URL(base).origin) throw new Error('Dirección de acceso no válida.');
    const result = await WebBrowser.openAuthSessionAsync(start.authorize_url,redirect);
    if (result.type !== 'success') return null;
    const callback = new URL(result.url);
    if (callback.protocol !== 'auren:' || callback.host !== 'auth' || callback.pathname !== '/callback' || callback.searchParams.get('state') !== state || callback.searchParams.get('request_id') !== start.request_id) throw new Error('No se pudo verificar el acceso. Intenta de nuevo.');
    const session = await api.request<Session>('/auth/exchange','POST',{request_id:start.request_id,code:callback.searchParams.get('code'),verifier});
    await SecureStore.setItemAsync(key,JSON.stringify(session),{keychainAccessible:SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY});
    return session;
  } finally { api.dispose(); }
}
