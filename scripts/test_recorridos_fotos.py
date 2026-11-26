"""Prueba HTTP con dos cuentas desechables. Requiere XAMPP local y Python 3.
Ejecutar desde la raíz: python scripts/test_recorridos_fotos.py
--keep conserva fixtures para revisión visual; --cleanup las elimina después.
"""
import http.cookiejar
import json
import pathlib
import re
import secrets
import subprocess
import sys
import tempfile
import urllib.error
import urllib.parse
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1]
STATE = pathlib.Path(tempfile.gettempdir()) / 'infonatec-photo-qa.json'
MYSQL = r'C:\xampp\mysql\bin\mysql.exe'
BASE = 'http://localhost/infonatec/public/'
checks = []

def sql(statement):
    return subprocess.check_output([MYSQL, '-u', 'root', 'sistema_inmobiliario', '-N', '-e', statement], text=True, encoding='utf-8').strip()

def cleanup(state):
    ids = [int(value) for value in state.get('users', [])]
    if not ids:
        return
    joined = ','.join(map(str, ids))
    paths = sql(f'SELECT i.url_imagen FROM sistema_inmobiliario.imagen_propiedad i JOIN sistema_inmobiliario.propiedad p ON p.id_propiedad=i.id_propiedad WHERE p.id_propietario IN ({joined})').splitlines()
    sql(f'DELETE t FROM sistema_inmobiliario.transaccion t JOIN sistema_inmobiliario.propiedad p ON p.id_propiedad=t.id_propiedad WHERE p.id_propietario IN ({joined}); DELETE FROM sistema_inmobiliario.propiedad WHERE id_propietario IN ({joined}); DELETE FROM sistema_inmobiliario.usuario WHERE id_usuario IN ({joined});')
    for path in paths:
        image = (ROOT / 'public' / path).resolve()
        if image.is_relative_to((ROOT / 'public/uploads/propiedades').resolve()):
            image.unlink(missing_ok=True)
    STATE.unlink(missing_ok=True)

class Client:
    def __init__(self):
        self.http = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.token = ''
        self.refresh()

    def request(self, route, data=None, csrf=True, photo=False, json_accept=False):
        headers = {}
        if data is not None:
            if csrf:
                headers['X-CSRF-Token'] = self.token
            if json_accept:
                headers['Accept'] = 'application/json'
            if photo:
                file_field = 'foto' if route.endswith('/foto') else 'photos[]'
                boundary = 'qa' + secrets.token_hex(12)
                parts = []
                for key, value in data.items():
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
                image = pathlib.Path(sys.argv[sys.argv.index('--photo')+1]) if '--photo' in sys.argv else ROOT / 'public/tour/panoramas/industrial/lote.jpg'
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{file_field}"; filename="prueba.jpg"\r\nContent-Type: image/jpeg\r\n\r\n'.encode() + image.read_bytes() + b'\r\n')
                parts.append(f'--{boundary}--\r\n'.encode())
                body = b''.join(parts)
                headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
            else:
                body = urllib.parse.urlencode(data).encode()
                headers['Content-Type'] = 'application/x-www-form-urlencoded'
        else:
            body = None
        try:
            response = self.http.open(urllib.request.Request(BASE + route, data=body, headers=headers), timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        content = response.read().decode('utf-8')
        try:
            result = json.loads(content)
        except ValueError:
            result = content
        return response.code, result

    def refresh(self):
        _, page = self.request('mis-propiedades')
        self.token = re.search(r'name="csrf-token" content="([^"]+)"', page).group(1)

def check(name, condition):
    if not condition:
        raise AssertionError(name)
    checks.append(name)
    print('OK', name)

def cleanup_photos(state):
    ids = ','.join(str(int(i)) for i in state.get('users', []))
    if ids:
        files = sql(f'SELECT a.ruta FROM recorrido_archivo a JOIN recorrido_foto r ON r.id_recorrido=a.id_recorrido JOIN propiedad p ON p.id_propiedad=r.id_propiedad WHERE p.id_propietario IN ({ids})').splitlines()
        cleanup(state)
        for file in files:
            (ROOT / 'storage/recorridos' / pathlib.Path(file).name).unlink(missing_ok=True)

def post_json(client, route, data, csrf=True):
    headers = {'Content-Type':'application/json'}
    if csrf:
        headers['X-CSRF-Token'] = client.token
    try:
        response = client.http.open(urllib.request.Request(BASE+route, data=json.dumps(data).encode(), headers=headers), timeout=20)
    except urllib.error.HTTPError as e:
        response = e
    return response.code, json.loads(response.read())

def image_status(client, aid):
    try:
        response = client.http.open(BASE+'recorridos/imagen/'+str(aid))
    except urllib.error.HTTPError as e:
        response = e
    content = response.read()
    return response.code, response.headers.get('Content-Type',''), len(content)

def invalid_upload(client, route, payload):
    boundary='qa'+secrets.token_hex(12)
    body=(f'--{boundary}\r\nContent-Disposition: form-data; name="foto"; filename="imagen.jpg"\r\nContent-Type: image/jpeg\r\n\r\n'.encode()+payload+f'\r\n--{boundary}--\r\n'.encode())
    try:
        response=client.http.open(urllib.request.Request(BASE+route,data=body,headers={'X-CSRF-Token':client.token,'Content-Type':'multipart/form-data; boundary='+boundary}))
    except urllib.error.HTTPError as e:
        response=e
    response.read()
    return response.code

if '--cleanup' in sys.argv:
    cleanup_photos(json.loads(STATE.read_text()))
    sys.exit(0)
if STATE.exists():
    raise SystemExit('Ejecutar --cleanup antes de repetir.')
state={'users':[], 'password':secrets.token_urlsafe(16)}
STATE.write_text(json.dumps(state))
try:
    guest,a,b=Client(),Client(),Client()
    for label,client in [('a',a),('b',b)]:
        email=f'qa-fotos-{secrets.token_hex(5)}@example.invalid'
        status,result=client.request('auth/register',dict(name='Prueba',last='Fotos',email=email,password=state['password'],phone='9511234567'))
        check('Registro '+label,status==200 and result['success'])
        client.token=result['csrf_token']; state['users'].append(result['user']['id']); state['email_'+label]=email
        STATE.write_text(json.dumps(state))
    publication=dict(title='QA recorrido con fotos',type='CASA',operation='VENTA',price='250000',location='Oaxaca Centro',description='Propiedad de prueba para validar recorridos con fotografías.',area='120',bedrooms='2',bathrooms='1',owner_phone='9511234567')
    status,result=a.request('vender/publicar',publication,photo=True)
    check('Publicación de prueba',status==200 and result['success'])
    pid=int(result['id']); state['property']=pid; STATE.write_text(json.dumps(state))
    route=f'mis-propiedades/{pid}/recorrido'
    check('Invitado no abre editor',guest.request(route)[0]==401)
    check('Otra cuenta no abre editor',b.request(route)[0]==404)
    check('Propietario abre editor',a.request(route)[0]==200)
    check('Invitado no carga foto',guest.request(route+'/foto',{},photo=True)[0]==401)
    check('Carga exige CSRF',a.request(route+'/foto',{},photo=True,csrf=False)[0]==403)
    check('Otra cuenta no carga foto',b.request(route+'/foto',{},photo=True)[0]==404)
    check('Rechaza carga sin archivo',a.request(route+'/foto',{})[0]==400)
    check('Rechaza HTML disfrazado de imagen',invalid_upload(a,route+'/foto',b'<html><script>alert(1)</script></html>')==400)
    check('Rechaza archivos de mas de 8 MB',invalid_upload(a,route+'/foto',b'x'*(8*1024*1024+1))==400)
    scenes=[]
    for name,kind in [('Sala','imagen'),('Cocina panorámica','panorama')]:
        code,result=a.request(route+'/foto',{},photo=True)
        check('Carga '+name,code==200 and result['success'])
        scene=result['scene']; scene['nombre']=name; scene['tipo_visual']=kind; scenes.append(scene)
    aid,bid=[s['id'] for s in scenes]
    check('Foto borrador privada para visitante',image_status(guest,aid)[0]==404)
    check('Foto borrador privada para otra cuenta',image_status(b,aid)[0]==404)
    check('Propietario ve foto',image_status(a,aid)[0]==200)
    graph={'schema_version':1,'escena_inicial_id':aid,'escenas':scenes,'conexiones':[]}
    code,result=post_json(a,route+'/guardar',dict(version=0,accion='publicar',graph=graph))
    check('No publica escenas desconectadas',code==400)
    code,result=post_json(a,route+'/guardar',dict(version=0,accion='guardar',graph=graph))
    check('Guarda borrador',code==200 and result['version']==1)
    check('Borrador no visible públicamente',guest.request(f'recorridos/{pid}')[0]==404)
    check('Otra cuenta no puede ver preview',b.request(f'recorridos/{pid}?preview=1')[0]==404)
    check('Propietario ve preview',a.request(f'recorridos/{pid}?preview=1')[0]==200)
    links=[dict(escena_origen_id=aid,escena_destino_id=bid,etiqueta='Ir a cocina',x=.75,y=.5),dict(escena_origen_id=bid,escena_destino_id=aid,etiqueta='Volver a sala',x=.25,y=.5)]
    graph['conexiones']=links
    check('Guardar requiere CSRF',post_json(a,route+'/guardar',dict(version=1,graph=graph),csrf=False)[0]==403)
    check('Otra cuenta no guarda',post_json(b,route+'/guardar',dict(version=1,graph=graph))[0]==404)
    invalid=json.loads(json.dumps(graph)); invalid['conexiones'][0]['x']=1.1
    check('Rechaza coordenadas fuera de foto',post_json(a,route+'/guardar',dict(version=1,graph=invalid))[0]==400)
    invalid=json.loads(json.dumps(graph)); invalid['escenas'][0]['id']=999999
    check('Rechaza imagen ajena o inexistente',post_json(a,route+'/guardar',dict(version=1,graph=invalid))[0]==400)
    check('Detecta conflicto entre pestañas',post_json(a,route+'/guardar',dict(version=0,graph=graph))[0]==409)
    invalid=json.loads(json.dumps(graph)); invalid['escenas'][1]['angulo_horizontal']=123
    check('Rechaza cobertura angular inválida',post_json(a,route+'/guardar',dict(version=1,graph=invalid))[0]==400)
    graph['escenas'][1]['angulo_horizontal']=360
    code,result=post_json(a,route+'/guardar',dict(version=1,accion='publicar',graph=graph))
    check('Publica recorrido conectado',code==200 and result['published'])
    check('Conserva cobertura 360 declarada',result['graph']['escenas'][1]['angulo_horizontal']==360)
    check('Visitante abre recorrido',guest.request(f'recorridos/{pid}')[0]==200)
    check('Visitante ve imagen publicada',image_status(guest,aid)[0]==200 and image_status(guest,aid)[1]=='image/jpeg')
    check('Ficha usa recorrido nuevo',guest.request(f'api/propiedades/{pid}')[1]['virtualTour']['url']==f'recorridos/{pid}')
    graph['escenas'][0]['nombre']='Sala borrador nuevo'
    code,result=post_json(a,route+'/guardar',dict(version=2,accion='guardar',graph=graph))
    check('Editar no cambia versión pública',code==200 and 'Sala borrador nuevo' not in guest.request(f'recorridos/{pid}')[1])
    a.request('auth/logout',{}); a.refresh()
    _,login=a.request('auth/login',dict(email=state['email_a'],password=state['password'])); a.token=login['csrf_token']
    check('Borrador persiste al volver a entrar','Sala borrador nuevo' in a.request(route)[1])
    code,result=a.request(route+'/foto',{},photo=True); orphan=result['scene']['id']
    code,result=post_json(a,route+'/limpiar',dict(version=3))
    check('Limpia únicamente archivos sin usar',code==200 and result['deleted']==1 and image_status(a,orphan)[0]==404 and image_status(guest,aid)[0]==200)
    code,result=post_json(a,route+'/guardar',dict(version=3,accion='ocultar',graph=graph))
    check('Oculta sin borrar borrador',code==200 and guest.request(f'recorridos/{pid}')[0]==404 and a.request(route)[0]==200)
    check('Ocultar bloquea imágenes públicas',image_status(guest,aid)[0]==404)
    # Volver a publicar para la verificación visual opcional.
    code,result=post_json(a,route+'/guardar',dict(version=4,accion='publicar',graph=graph))
    check('Puede volver a publicar',code==200)
    check('Almacenamiento privado bloqueado por Apache',guest.request('../storage/recorridos/')[0] in (403,404))
    print(f'{len(checks)} comprobaciones satisfactorias.')
finally:
    if '--keep' not in sys.argv:
        cleanup_photos(state)
