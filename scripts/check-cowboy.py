"""Verify the secure Cowboy connection using only discovery and site-info reads.

No key values are accepted as arguments, saved to files, or included in output.
"""
import os,sys,json,urllib.request,urllib.error
URL='https://www.aurenfx.com/wp-json/cowboy-mcp/v1/endpoint'
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*args,**kwargs):return None
opener=urllib.request.build_opener(NoRedirect)
key=os.environ.get('COWBOY_MCP_API_KEY')
session=None;protocol=None

def rpc(method,params=None,request_id=1):
 global session,protocol
 body={'jsonrpc':'2.0','method':method}
 if params is not None:body['params']=params
 if request_id is not None:body['id']=request_id
 headers={'Authorization':'Bearer '+key,'Content-Type':'application/json','Accept':'application/json, text/event-stream','User-Agent':'AurenCodexMCP/0.1 (+https://www.aurenfx.com)'}
 if session:headers['Mcp-Session-Id']=session
 if protocol:headers['MCP-Protocol-Version']=protocol
 req=urllib.request.Request(URL,data=json.dumps(body).encode(),headers=headers,method='POST')
 with opener.open(req,timeout=25) as response:
  if response.headers.get('Mcp-Session-Id'):session=response.headers['Mcp-Session-Id']
  if request_id is None or response.status==202:return {'http_status':response.status}
  if 'text/event-stream' in response.headers.get('Content-Type',''):
   lines=[]
   while True:
    line=response.readline(1048576)
    if not line:break
    text=line.decode().strip('\r\n')
    if text.startswith('data:'):lines.append(text[5:].lstrip())
    elif not text and lines:
     data=json.loads('\n'.join(lines));lines=[]
     if data.get('id')==request_id:return data
   raise RuntimeError('MCP stream ended without the requested response')
  return json.loads(response.read(1048576))

def safe_error(error):
 if isinstance(error,urllib.error.HTTPError):return {'error_type':'http','status':error.code}
 if isinstance(error,urllib.error.URLError):
  message=str(error.reason)
  return {'error_type':'transport','proxy_tunnel_403':'Tunnel connection failed: 403' in message,'tls_error':'CERTIFICATE_VERIFY_FAILED' in message}
 return {'error_type':type(error).__name__}
def tool_result(reply):
 if reply.get('error') or not isinstance(reply.get('result'),dict):
  raise RuntimeError('MCP request failed')
 result=reply['result']
 if result.get('isError'):raise RuntimeError('Cowboy tool rejected the read-only request')
 if isinstance(result.get('structuredContent'),dict):return result['structuredContent']
 for block in result.get('content',[]):
  if block.get('type')=='text':return json.loads(block['text'])
 raise RuntimeError('No structured Cowboy response')

if __name__=='__main__':
 try:
  if not key:raise RuntimeError('Configure COWBOY_MCP_API_KEY through secure environment settings')
  initialized=rpc('initialize',{'protocolVersion':'2025-03-26','capabilities':{},'clientInfo':{'name':'AurenReadOnlyVerification','version':'0.1.0'}})
  info=initialized.get('result',{})
  if not info.get('protocolVersion'):raise RuntimeError('MCP initialization failed')
  protocol=info['protocolVersion'];rpc('notifications/initialized',request_id=None)
  listing=rpc('tools/list',request_id=2).get('result',{})
  exposed=[t.get('name') for t in listing.get('tools',[])]
  if not {'cowboy_discover','cowboy_run'}.issubset(exposed):raise RuntimeError('Cowboy discovery tools unavailable')
  discovered=tool_result(rpc('tools/call',{'name':'cowboy_discover','arguments':{'query':'site info','category':'system'}},3))
  site_tool=next((t for t in discovered.get('tools',[]) if t.get('name')=='wp_site_info'),None)
  if not site_tool or site_tool.get('annotations',{}).get('readOnlyHint') is not True:
   raise RuntimeError('Read-only site info tool not confirmed')
  site=tool_result(rpc('tools/call',{'name':'cowboy_run','arguments':{'tool':'wp_site_info','arguments':{}}},4))
  if site.get('url','').rstrip('/')!='https://www.aurenfx.com':raise RuntimeError('Unexpected target site')
  from datetime import datetime,timezone
  evidence={'checked_at':datetime.now(timezone.utc).isoformat(),'authenticated':True,'transport':'MCP streamable HTTP over verified HTTPS','server_info':info.get('serverInfo'),'protocol':protocol,'tools_exposed':exposed,'read_only_tool':'wp_site_info','site':{k:site.get(k) for k in ['name','url','wp_version','php_version','active_theme']},'auren_bridge_active':'auren-app-bridge/auren-app-bridge.php' in site.get('active_plugins',[]),'mutating_tools_called':False,'client_user_agent':'AurenCodexMCP/0.1 (+https://www.aurenfx.com)'}
  print(json.dumps(evidence,ensure_ascii=False,indent=2))
 except Exception as error:
  print(json.dumps({'verified':False,**safe_error(error)}));sys.exit(1)
 finally:key=None
