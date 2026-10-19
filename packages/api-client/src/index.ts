export type Progress = { completed: number; total: number; percent: number };
export type LessonSummary = { id: number; title: string; completed: boolean; available: boolean };
export type Course = { id: number; title: string; summary: string; image_url: string | null; url: string; access: {allowed: true; source: string; label: string}; progress: Progress; lessons?: LessonSummary[] };
export type Lesson = { id: number; course_id: number; title: string; text: string; url: string; completed: boolean; completion_mode: 'app' | 'web'; video: {kind: 'direct' | 'web' | 'youtube'; url: string | null; video_id?: string} | null };
export type Identity = { id: number; name: string; avatar_url: string; site_url: string; environment: string; profile_url?:string|null; community_url?:string|null; joined?:string; unread_notifications?:number; capabilities: { academy: boolean; community: boolean; notifications?:boolean; checkout: boolean } };
export type Author = {name:string;avatar_url:string|null};
export type ActivityComment = {id:number;author:Author;text:string;date:string;replies:ActivityComment[]};
export type Activity = {id:number;author:Author;text:string;date:string;type:string;image_url:string|null;url:string|null;group_name:string;reaction_count:number;comment_count:number;comments?:ActivityComment[]};
export type Notification = {id:number;text:string;date:string;url:string|null;avatar_url:string|null;unread:boolean};
export type Page<T> = {items:T[];next_page:number|null};
export type Session = { access_token: string; expires_at: number; token_type: 'Bearer' };
export type Bootstrap = { api_url: string; site_url: string; nonce: string | null; login_url: string; register_url: string; lost_password_url: string; environment: string; release?: { version:string; build_id:string }; release_url?:string };
export class ApiError extends Error {
  constructor(public code: string, message: string, public status: number) { super(message); }
}
export function validateBaseUrl(value: string, allowLocal = false): string {
  const url = new URL(value);
  if (url.username || url.password || url.search || url.hash || (url.protocol !== 'https:' && !(allowLocal && url.protocol === 'http:' && ['localhost','127.0.0.1'].includes(url.hostname)))) {
    throw new Error('La dirección de la academia debe usar HTTPS.');
  }
  return url.href.replace(/\/?$/, '/');
}
export function safeHttpsUrl(value: string): string | null {
  try { const u = new URL(value); return u.protocol === 'https:' && !u.username && !u.password ? u.href : null; } catch { return null; }
}
type Options = { baseUrl: string; nonce?: string | null; token?: string; allowLocal?: boolean; fetcher?: typeof fetch; onExpired?: () => void };
export class AurenApi {
  private base: string;
  private controllers = new Set<AbortController>();
  private disposed = false;
  constructor(private options: Options) { this.base = validateBaseUrl(options.baseUrl,options.allowLocal); }
  dispose() { this.disposed = true; this.controllers.forEach(c=>c.abort()); this.controllers.clear(); this.options.token = undefined; this.options.nonce = null; }
  async request<T>(path: string, method = 'GET', body?: unknown): Promise<T> {
    if (this.disposed) throw new ApiError('session_closed','La sesión está cerrada.',401);
    if (!/^\/[a-z0-9/_-]+(?:\?page=[1-9][0-9]*)?$/.test(path)) throw new Error('Ruta no válida');
    const controller = new AbortController(); this.controllers.add(controller);
    const timeout = setTimeout(()=>controller.abort(),15000);
    try {
      const headers: Record<string,string> = {Accept:'application/json'};
      if (body !== undefined) headers['Content-Type'] = 'application/json';
      if (this.options.token) headers.Authorization = `Bearer ${this.options.token}`;
      else if (this.options.nonce) headers['X-WP-Nonce'] = this.options.nonce;
      const response = await (this.options.fetcher ?? fetch)(this.base + path.slice(1),{
        method, headers, body:body === undefined ? undefined : JSON.stringify(body), signal:controller.signal,
        credentials:this.options.token ? 'omit' : 'same-origin',cache:'no-store',redirect:'error'
      });
      const isJson = response.headers.get('content-type')?.includes('application/json');
      const data = isJson ? await response.json() : null;
      if (response.status === 401 || (response.status === 403 && data?.code === 'rest_cookie_invalid_nonce')) {
        this.options.onExpired?.();
      }
      if (!response.ok) throw new ApiError(data?.code ?? 'http_error', publicMessage(data?.code,response.status),response.status);
      if (!data || typeof data !== 'object') throw new ApiError('invalid_response','No pudimos leer la respuesta de la academia.',502);
      if (this.disposed) throw new ApiError('session_closed','La sesión está cerrada.',401);
      return data as T;
    } catch (error) {
      if (error instanceof ApiError) throw error;
      if (controller.signal.aborted) throw new ApiError('request_timeout','La conexión tardó demasiado. Intenta de nuevo.',0);
      throw new ApiError('network_error','No pudimos conectar con tu academia. Revisa tu conexión e intenta de nuevo.',0);
    } finally { clearTimeout(timeout); this.controllers.delete(controller); }
  }
  me() { return this.request<Identity>('/me'); }
  courses(page=1) { return this.request<{items:Course[];next_page:number|null}>(`/courses?page=${page}`); }
  course(id:number) { return this.request<Course>(`/courses/${positiveId(id)}`); }
  lesson(course:number,lesson:number) { return this.request<Lesson>(`/courses/${positiveId(course)}/lessons/${positiveId(lesson)}`); }
  complete(course:number,lesson:number) { return this.request<{lesson:Lesson;progress:Progress}>(`/courses/${positiveId(course)}/lessons/${positiveId(lesson)}/complete`,'POST',{}); }
  logout() { return this.request<{logged_out:boolean}>('/logout','POST',{}); }
  community(page=1) { return this.request<Page<Activity>>(`/community?page=${positiveId(page)}`); }
  activity(id:number) { return this.request<Activity>(`/community/${positiveId(id)}`); }
  notifications(page=1) { return this.request<Page<Notification>&{unread_count:number}>(`/notifications?page=${positiveId(page)}`); }
}
function positiveId(id:number) { if (!Number.isSafeInteger(id) || id < 1) throw new Error('ID inválido'); return id; }
function publicMessage(code:string,status:number) {
  if (['login_required','session_expired','rest_cookie_invalid_nonce','invalid_grant'].includes(code)) return 'Tu sesión terminó. Vuelve a entrar para continuar.';
  if (code === 'course_forbidden') return 'Este curso no está incluido en tu acceso actual.';
  if (code === 'lesson_locked' || code === 'prerequisites') return 'Completa las actividades anteriores para continuar.';
  if (code === 'lesson_scheduled') return 'Esta lección todavía no está disponible.';
  if (code === 'web_required') return 'Esta actividad se completa desde la academia.';
  if (code === 'lms_not_verified') return 'Estamos preparando el acceso a la academia desde la app.';
  if (code === 'pilot_only') return 'Tu cuenta todavía no está habilitada para esta versión de la app.';
  if (code === 'account_unavailable') return 'Tu cuenta no está disponible. Contacta al equipo de Auren.';
  if (code === 'community_unavailable') return 'Este contenido de la comunidad no está disponible para tu cuenta.';
  if (code === 'notifications_unavailable') return 'No pudimos consultar tus avisos. Intenta de nuevo.';
  if (status === 429) return 'Espera un momento antes de volver a intentar.';
  return 'No pudimos completar la solicitud. Intenta de nuevo en un momento.';
}
