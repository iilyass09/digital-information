export class ApiError extends Error { constructor(message,status,errors={}) { super(message); this.status=status; this.errors=errors; } }
export async function apiRequest(path,{method='GET',body,signal}={}) {
 const headers={Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content??''}; let requestMethod=method; let requestBody=body;
 if(body instanceof FormData) { if(method==='PUT'||method==='PATCH') { body.set('_method',method); requestMethod='POST'; } }
 else if(body!==undefined) { headers['Content-Type']='application/json'; requestBody=JSON.stringify(body); }
 const response=await fetch(path,{method:requestMethod,body:requestBody,headers,signal,credentials:'same-origin'}); const payload=await response.json().catch(()=>({}));
 if(!response.ok) throw new ApiError(payload.message||'Permintaan gagal diproses.',response.status,payload.errors||{}); return payload.data;
}
export async function getDisplayData({signal}={}) { const response=await fetch('/api/display',{headers:{Accept:'application/json'},signal}); if(!response.ok) throw new Error('Display data request failed.'); return response.json(); }
