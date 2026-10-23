export type Progress = { completed: number; total: number; percent: number };
export type LessonSummary = { id: number; title: string; completed: boolean; available: boolean };
export type Course = { id: number; title: string; summary: string; image_url: string | null; url: string; access: {allowed: true; source: string; label: string}; progress: Progress; lessons?: LessonSummary[] };
export type Lesson = { id: number; course_id: number; title: string; text: string; url: string; completed: boolean; completion_mode: 'app' | 'web'; video: {kind: 'direct' | 'web' | 'youtube'; url: string | null; video_id?: string} | null };
export type GamiRequirement = {id:number;title:string;earned:boolean;optional:boolean;current:number;total:number;unit:string;percent:number|null};
export type GamiProgress = {percent:number|null;requirements:GamiRequirement[]};
export type GamiRank = {type:string;current:{id:number;name:string;number:number;image_url:string|null};next:{id:number;name:string;number:number;image_url:string|null}|null;progress:GamiProgress};
export type GamiAchievement = {id:number;name:string;description:string;earned:boolean;image_url:string|null;progress:GamiProgress;reward:number|null};
export type Gamification = {available:boolean;credits:{type:string;label:string;balance:number;image_url:string|null}|null;rank:GamiRank|null;achievements:GamiAchievement[];achievement_total:number;achievement_earned:number};
export type Identity = { id: number; name: string; avatar_url: string; site_url: string; environment: string; profile_url?:string|null; community_url?:string|null; cover_url?:string|null; branding?:{name:string;logo_url:string|null}; gamification?:Gamification; joined?:string; unread_notifications?:number; capabilities: { academy: boolean; community: boolean; notifications?:boolean; checkout: boolean; profile?:boolean;members?:boolean;report?:boolean;block?:boolean;post_title?:boolean;groups?:boolean;forums?:boolean } };
export type Author = {id?:number;name:string;avatar_url:string|null};
export type ActivityComment = {id:number;author:Author;text:string;date:string;replies:ActivityComment[]};
export type Activity = {id:number;author:Author;text:string;date:string;type:string;image_url:string|null;url:string|null;group_name:string;reaction_count:number;comment_count:number;comments?:ActivityComment[];liked?:boolean;privacy?:string;title?:string;permissions?:{like:boolean;comment:boolean;edit:boolean;delete:boolean}};
export type Notification = {id:number;text:string;date:string;url:string|null;avatar_url:string|null;unread:boolean;target?:{type:'activity';id:number}|null};
export type ProfileField = {id:number;group_id:number;group:string;label:string;type:string;required:boolean;value:string|string[];options:{value:string;label:string}[]};
export type Profile = {fields:ProfileField[]};
export type Member = {id:number;name:string;avatar_url:string|null;joined:string;fields?:{label:string;value:string}[]};
export type Page<T> = {items:T[];next_page:number|null};
export type Group={id:number;name:string;description:string;status:string;member_count:number;avatar_url:string|null;cover_url:string|null;is_member:boolean;pending:boolean;can_read:boolean;can_join:boolean;has_forum:boolean};
export type Forum={id:number;title:string;description:string;parent:number;topic_count:number;reply_count:number;closed:boolean;category:boolean;can_read:boolean;can_create:boolean};
export type Topic={id:number;forum_id:number;title:string;text:string;author:Author;date:string;reply_count:number;sticky:boolean;closed:boolean;can_reply:boolean};
export type ForumReply={id:number;text:string;author:Author;date:string;reply_to:number};
export type GroupDetail={group:Group;forums:Forum[];activity:Page<Activity>|null};
export type ForumDetail={forum:Forum;forums:Page<Forum>;topics:Page<Topic>};
export type TopicDetail={topic:Topic;replies:Page<ForumReply>};
export type Spaces={groups:Page<Group>|null;forums:Page<Forum>|null};
export type SpaceSaved={saved:true;id?:number;pending:boolean};
export type RegistrationField={id:string;label:string;type:string;required:boolean;options:{value:string;label:string}[]};
export type RegistrationConfig={enabled:boolean;native_form_only:boolean;url:string;fields:RegistrationField[];challenge:string|null;privacy_url:string;terms_url:string|false};
export type Startup = {identity:Identity;catalog:Page<Course>|null;profile:Profile|null;notifications:(Page<Notification>&{unread_count:number})|null;community:Page<Activity>|null};
export type Session = { access_token: string; expires_at: number; token_type: 'Bearer' };
export type Bootstrap = { api_url: string; site_url: string; nonce: string | null; login_url: string; register_url: string; lost_password_url: string; environment: string; registration?:RegistrationConfig|null; release?: { version:string; build_id:string }; release_url?:string };
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
  private readEpoch = 0;
  private reads = new Map<string,{data:unknown;at:number}>();
  private pending = new Map<string,{epoch:number;promise:Promise<unknown>}>();
  /** Session-only memory: no browser cache, disk, password or cross-account reuse. */
  peek<T>(path:string):T|undefined {
    const entry=this.reads.get(path);
    return !this.disposed&&entry&&Date.now()-entry.at<120000?entry.data as T:undefined;
  }
  private invalidateReads(){this.readEpoch++;this.reads.clear();this.pending.clear();}
  request<T>(path:string,method='GET',body?:unknown,fresh=false):Promise<T>{
    if(this.disposed)return Promise.reject(new ApiError('session_closed','La sesión está cerrada.',401));
    if(method!=='GET'){this.invalidateReads();return this.send<T>(path,method,body);}
    const cacheable=/^\/(profile|community|notifications|members|blocked|reports)(\?|\/|$)/.test(path)||/^\/(spaces|groups|forums)(\?|$)/.test(path);
    const entry=this.reads.get(path);
    if(cacheable&&!fresh&&entry&&Date.now()-entry.at<30000)return Promise.resolve(entry.data as T);
    const running=this.pending.get(path);
    if(running&&running.epoch===this.readEpoch)return running.promise as Promise<T>;
    const epoch=this.readEpoch;
    const promise=this.send<T>(path,method,body).then(data=>{
      if(epoch!==this.readEpoch)throw new ApiError('request_superseded','Los datos cambiaron. Actualiza para consultar el estado actual.',0);
      if(cacheable){if(this.reads.size>=40)this.reads.delete(this.reads.keys().next().value!);this.reads.set(path,{data,at:Date.now()});}
      return data;
    }).finally(()=>{if(this.pending.get(path)?.promise===promise)this.pending.delete(path);});
    this.pending.set(path,{epoch,promise});return promise;
  }
  constructor(private options: Options) { this.base = validateBaseUrl(options.baseUrl,options.allowLocal); }
  dispose() { this.disposed = true; this.invalidateReads(); this.controllers.forEach(c=>c.abort()); this.controllers.clear(); this.options.token = undefined; this.options.nonce = null; }
  private async send<T>(path: string, method = 'GET', body?: unknown): Promise<T> {
    if (this.disposed) throw new ApiError('session_closed','La sesión está cerrada.',401);
    const rawPath=path.split('?')[0]??'';if(!/^\/[a-z0-9][a-z0-9/_-]*$/.test(rawPath))throw new Error('Ruta no válida');
    const route=new URL(path,'https://route.invalid');
    if(route.origin!=='https://route.invalid'||route.hash||!/^\/[a-z0-9][a-z0-9/_-]*$/.test(route.pathname))throw new Error('Ruta no válida');
    const seen=new Set<string>();
    for(const [key,value] of route.searchParams){
      if(seen.has(key)||!['page','filter','search'].includes(key)||(key==='page'&&!/^[1-9][0-9]{0,3}$/.test(value))||(key==='filter'&&!/^[a-z-]{1,20}$/.test(value))||(key==='search'&&value.length>80))throw new Error('Ruta no válida');
      seen.add(key);
    }
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
      if (response.status === 401 || response.status === 403) this.invalidateReads();
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
  async startup():Promise<Startup>{
    const epoch=this.readEpoch;let data:Startup;
    try{data=await this.request<Startup>('/startup');}
    catch(error){
      // Existing binaries remain compatible with bridges deployed before this batch.
      if(!(error instanceof ApiError)||error.status!==404)throw error;
      const [identity,catalog]=await Promise.all([this.me(),this.courses()]);
      return {identity,catalog,profile:null,notifications:null,community:null};
    }
    if(this.disposed||epoch!==this.readEpoch)throw new ApiError('request_superseded','La sesión cambió. Actualiza para continuar.',0);
    for(const [path,value] of [['/profile',data.profile],['/notifications?page=1&filter=unread',data.notifications],['/community?page=1&filter=all&search=',data.community]] as const){
      if(value)this.reads.set(path,{data:value,at:Date.now()});
    }
    return data;
  }
  async spaces(fresh=false){
    const epoch=this.readEpoch,data=await this.request<Spaces>('/spaces','GET',undefined,fresh);
    if(this.disposed||epoch!==this.readEpoch)throw new ApiError('request_superseded','La sesión cambió.',0);
    for(const [path,value] of [['/groups?page=1&filter=mine&search=',data.groups],['/forums?page=1&search=',data.forums]] as const){if(value)this.reads.set(path,{data:value,at:Date.now()});}
    return data;
  }
  groups(page=1,filter='mine',search='',fresh=false){return this.request<Page<Group>>(`/groups?${new URLSearchParams({page:String(positiveId(page)),filter,search:search.slice(0,80)})}`,'GET',undefined,fresh);}
  group(id:number){return this.request<GroupDetail>(`/groups/${positiveId(id)}`);}
  groupMembers(id:number,page=1){return this.request<Page<Member>>(`/groups/${positiveId(id)}/members?page=${positiveId(page)}`);}
  groupActivity(id:number,page=1){return this.request<Page<Activity>>(`/groups/${positiveId(id)}/activity?page=${positiveId(page)}`);}
  joinGroup(id:number,request_key:string){return this.request<SpaceSaved>(`/groups/${positiveId(id)}/join`,'POST',{request_key});}
  forums(page=1,search='',fresh=false){return this.request<Page<Forum>>(`/forums?${new URLSearchParams({page:String(positiveId(page)),search:search.slice(0,80)})}`,'GET',undefined,fresh);}
  forum(id:number,page=1,search=''){return this.request<ForumDetail>(`/forums/${positiveId(id)}?${new URLSearchParams({page:String(positiveId(page)),search:search.slice(0,80)})}`);}
  topic(id:number,page=1){return this.request<TopicDetail>(`/topics/${positiveId(id)}?page=${positiveId(page)}`);}
  createTopic(id:number,title:string,content:string,request_key:string){return this.request<SpaceSaved>(`/forums/${positiveId(id)}/topics`,'POST',{title,content,request_key});}
  replyTopic(id:number,content:string,request_key:string){return this.request<SpaceSaved>(`/topics/${positiveId(id)}/replies`,'POST',{content,request_key});}
  registrationConfig(){return this.request<RegistrationConfig>('/registration');}
  registerAccount(data:{fields:Record<string,string|string[]>;challenge:string;website:string}){return this.request<{submitted:true;message:string}>('/registration','POST',data);}
  me() { return this.request<Identity>('/me'); }
  gamification() { return this.request<Gamification>('/gamification'); }
  courses(page=1) { return this.request<{items:Course[];next_page:number|null}>(`/courses?page=${page}`); }
  course(id:number) { return this.request<Course>(`/courses/${positiveId(id)}`); }
  lesson(course:number,lesson:number) { return this.request<Lesson>(`/courses/${positiveId(course)}/lessons/${positiveId(lesson)}`); }
  complete(course:number,lesson:number) { return this.request<{lesson:Lesson;progress:Progress}>(`/courses/${positiveId(course)}/lessons/${positiveId(lesson)}/complete`,'POST',{}); }
  logout() { return this.request<{logged_out:boolean}>('/logout','POST',{}); }
  community(page=1,filter='all',search='',fresh=false) { return this.request<Page<Activity>>(`/community?${new URLSearchParams({page:String(positiveId(page)),filter,search:search.slice(0,80)})}`,'GET',undefined,fresh); }
  activity(id:number) { return this.request<Activity>(`/community/${positiveId(id)}`); }
  notifications(page=1,filter='unread',fresh=false) { return this.request<Page<Notification>&{unread_count:number}>(`/notifications?page=${positiveId(page)}&filter=${filter}`,'GET',undefined,fresh); }
  readVisibleNotifications(ids:number[]) { return this.request<{changed:number;unread_count:number}>('/notifications/read','PUT',{ids}); }
  readNotification(id:number) { return this.request<{id:number;unread:false;unread_count:number}>(`/notifications/${positiveId(id)}/read`,'PUT',{}); }
  profile(fresh=false) { return this.request<Profile>('/profile','GET',undefined,fresh); }
  saveProfile(fields:{id:number;value:string|string[]}[]) { return this.request<Profile>('/profile','PATCH',{fields}); }
  createActivity(content:string,privacy:string,request_key:string,title='') { return this.request<Activity>('/community','POST',{content,privacy,request_key,title}); }
  editActivity(id:number,content:string) { return this.request<Activity>(`/community/${positiveId(id)}`,'PATCH',{content}); }
  deleteActivity(id:number) { return this.request<{deleted:true;id:number}>(`/community/${positiveId(id)}`,'DELETE'); }
  comment(id:number,content:string,request_key:string) { return this.request<Activity>(`/community/${positiveId(id)}/comments`,'POST',{content,request_key}); }
  like(id:number,liked:boolean) { return this.request<Activity>(`/community/${positiveId(id)}/like`,'PUT',{liked}); }
  reportForm() { return this.request<{categories:{id:string;label:string}[]}>('/reports'); }
  report(id:number,category:string,note:string) { return this.request<{reported:true}>(`/community/${positiveId(id)}/report`,'POST',{category,note}); }
  members(page=1,search='',fresh=false) { return this.request<Page<Member>>(`/members?${new URLSearchParams({page:String(positiveId(page)),search:search.slice(0,80)})}`,'GET',undefined,fresh); }
  blocked(page=1) { return this.request<Page<Member>>(`/blocked?page=${positiveId(page)}`); }
  blockMember(id:number,blocked:boolean) { return this.request<{id:number;blocked:boolean}>(`/members/${positiveId(id)}/block`,'PUT',{blocked}); }
  member(id:number) { return this.request<Member>(`/members/${positiveId(id)}`); }
}
function positiveId(id:number) { if (!Number.isSafeInteger(id) || id < 1) throw new Error('ID inválido'); return id; }
function publicMessage(code:string,status:number) {
  if (['login_required','session_expired','rest_cookie_invalid_nonce','invalid_grant'].includes(code)) return 'Tu sesión terminó. Vuelve a entrar para continuar.';
  if(code==='registration_validation'||code==='invalid_registration')return 'Revisa los campos requeridos y usa una contraseña de al menos 12 caracteres. Si ya tienes cuenta, inicia sesión o recupera tu acceso.';
  if(code==='registration_expired')return 'El formulario venció. Lo renovaremos; revisa los campos y vuelve a enviar.';
  if(code==='registration_disabled')return 'El registro no está disponible por ahora.';
  if(code==='registration_web_required')return 'Completa el registro protegido en la academia.';
  if(code==='space_forbidden')return 'Este espacio es privado o ya no está disponible para tu cuenta.';
  if(code==='spaces_unavailable')return 'No pudimos consultar este espacio. Intenta actualizar.';
  if (code === 'course_forbidden') return 'Este curso no está incluido en tu acceso actual.';
  if (code === 'lesson_locked' || code === 'prerequisites') return 'Completa las actividades anteriores para continuar.';
  if (code === 'lesson_scheduled') return 'Esta lección todavía no está disponible.';
  if (code === 'web_required') return 'Esta actividad se completa desde la academia.';
  if (code === 'lms_not_verified') return 'Estamos preparando el acceso a la academia desde la app.';
  if (code === 'pilot_only') return 'Tu cuenta todavía no está habilitada para esta versión de la app.';
  if (code === 'account_unavailable') return 'Tu cuenta no está disponible. Contacta al equipo de Auren.';
  if (code === 'community_unavailable') return 'Este contenido de la comunidad no está disponible para tu cuenta.';
  if (code === 'notifications_unavailable') return 'No pudimos consultar tus avisos. Intenta de nuevo.';
  if (['invalid_content','profile_validation','invalid_privacy'].includes(code)) return 'Revisa los campos antes de guardar. Si WordPress rechazó un campo, actualiza para comprobar lo guardado.';
  if (code === 'write_in_progress') return 'Hay un cambio guardándose. Espera un momento.';
  if (code === 'duplicate_request') return 'Esta solicitud ya se utilizó. Actualiza para comprobar lo guardado.';
  if (code === 'action_forbidden') return 'Tu cuenta no tiene permiso para realizar esta acción.';
  if (status === 429) return 'Espera un momento antes de volver a intentar.';
  return 'No pudimos completar la solicitud. Intenta de nuevo en un momento.';
}
