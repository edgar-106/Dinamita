"""Prueba HTTP con dos cuentas desechables. Requiere XAMPP local y Python 3.
Ejecutar desde la raíz: python scripts/test_mis_propiedades.py
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
STATE = pathlib.Path(tempfile.gettempdir()) / 'infonatec-owner-qa.json'
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
                boundary = 'qa' + secrets.token_hex(12)
                parts = []
                for key, value in data.items():
                    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode())
                image = ROOT / 'public/tour/panoramas/industrial/lote.jpg'
                parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photos[]"; filename="prueba.jpg"\r\nContent-Type: image/jpeg\r\n\r\n'.encode() + image.read_bytes() + b'\r\n')
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

if '--cleanup' in sys.argv:
    cleanup(json.loads(STATE.read_text()))
    sys.exit(0)
if STATE.exists():
    raise SystemExit('Hay fixtures pendientes; ejecutar primero --cleanup.')
state = {'users': [], 'password': secrets.token_urlsafe(16)}
STATE.write_text(json.dumps(state))
try:
    guest, a, b = Client(), Client(), Client()
    check('Página de invitado solicita acceso', 'Ingresa a tu cuenta' in guest.request('mis-propiedades')[1])
    check('Publicar sin cuenta devuelve 401', guest.request('vender/publicar', {})[0] == 401)
    for label, client in [('a', a), ('b', b)]:
        email = f'qa-owner-{secrets.token_hex(5)}@example.invalid'
        status, result = client.request('auth/register', dict(name='Prueba', last='Propietario', email=email, phone='9511234567', password=state['password']))
        check('Registrar cuenta ' + label, status == 200 and result['success'])
        state['users'].append(result['user']['id'])
        state['email_' + label] = email
        client.token = result['csrf_token']
        STATE.write_text(json.dumps(state))
    publication = dict(title='QA propiedad privada de prueba', type='CASA', operation='VENTA', price='250000', location='Colonia Centro, Oaxaca', description='Descripción de prueba para validar la gestión de anuncios propios.', area='120', bedrooms='2', bathrooms='1', owner_name='Otra persona', owner_phone='9511234567', owner_email=state['email_b'])
    status, result = a.request('vender/publicar', publication, photo=True)
    check('Publicación con fotografía', status == 200 and result['success'])
    pid = int(result['id'])
    state['property'] = pid
    STATE.write_text(json.dumps(state))
    check('Correo manipulado no cambia propietario', int(sql(f'SELECT id_propietario FROM sistema_inmobiliario.propiedad WHERE id_propiedad={pid}')) == state['users'][0])
    check('Propietario ve su publicación', publication['title'] in a.request('mis-propiedades')[1])
    check('Otra cuenta no la ve en su lista', publication['title'] not in b.request('mis-propiedades')[1].split('<main id="main-content"')[1].split('</main>')[0])
    check('Otra cuenta no abre edición', b.request(f'mis-propiedades/{pid}/editar')[0] == 404)
    changes = dict(titulo='QA propiedad editada', descripcion=publication['description'], ubicacion=publication['location'], precio='260000', superficie='125', operacion='RENTA', habitaciones='3', banos='2')
    check('Sin CSRF se rechaza edición', a.request(f'mis-propiedades/{pid}/editar', changes, csrf=False, json_accept=True)[0] == 403)
    check('Otra cuenta no puede editar', not b.request(f'mis-propiedades/{pid}/editar', changes, json_accept=True)[1]['success'])
    check('Otra cuenta no puede retirar', not b.request(f'mis-propiedades/{pid}/retirar', {}, json_accept=True)[1]['success'])
    check('Validación rechaza precio negativo', not a.request(f'mis-propiedades/{pid}/editar', dict(changes, precio='-1'), json_accept=True)[1]['success'])
    check('Propietario guarda cambios', a.request(f'mis-propiedades/{pid}/editar', changes, json_accept=True)[1]['success'])
    check('Catálogo muestra cambios', a.request(f'api/propiedades/{pid}')[1]['title'] == changes['titulo'])
    a.request('auth/logout', {})
    check('Sesión cerrada no permite editar', a.request(f'mis-propiedades/{pid}/editar', changes, json_accept=True)[0] == 401)
    a.refresh()
    status, result = a.request('auth/login', dict(email=state['email_a'], password=state['password']))
    check('Volver a iniciar sesión', status == 200 and result['success'])
    a.token = result['csrf_token']
    check('Publicación persiste al volver', changes['titulo'] in a.request('mis-propiedades')[1])
    transaction = sql(f"INSERT INTO sistema_inmobiliario.transaccion (id_propiedad,id_comprador,id_vendedor,monto,tipo,estado) VALUES ({pid},{state['users'][1]},{state['users'][0]},260000,'RENTA','PENDIENTE'); SELECT LAST_INSERT_ID();")
    check('Operación pendiente impide edición', not a.request(f'mis-propiedades/{pid}/editar', changes, json_accept=True)[1]['success'])
    check('Operación pendiente impide retiro', not a.request(f'mis-propiedades/{pid}/retirar', {}, json_accept=True)[1]['success'])
    sql(f'DELETE FROM sistema_inmobiliario.transaccion WHERE id_transaccion={int(transaction)}')
    check('Retirar sin CSRF se rechaza', a.request(f'mis-propiedades/{pid}/retirar', {}, csrf=False, json_accept=True)[0] == 403)
    check('Propietario retira', a.request(f'mis-propiedades/{pid}/retirar', {}, json_accept=True)[1]['success'])
    check('Retirada desaparece del catálogo', a.request(f'api/propiedades/{pid}')[0] == 404)
    check('Retirada permanece en historial', 'RETIRADA' in a.request('mis-propiedades')[1])
    check('Foto permanece guardada', int(sql(f'SELECT COUNT(*) FROM sistema_inmobiliario.imagen_propiedad WHERE id_propiedad={pid}')) == 1)
    check('No se puede editar tras retirar', not a.request(f'mis-propiedades/{pid}/editar', changes, json_accept=True)[1]['success'])
    appointment = dict(id_propiedad=pid, fecha='2099-01-01', hora='12:00', telefono='9511234567')
    check('Anuncio retirado no acepta citas', a.request('api/citas', appointment)[0] == 404)
    php = r'C:\xampp\php\php.exe'
    recovery = subprocess.check_output([php, str(ROOT / 'scripts/habilitar_propietario.php'), '--identidad-verificada', state['email_a']], text=True, encoding='utf-8')
    recovered_password = re.search(r'Contraseña nueva: (\w+)', recovery).group(1)
    recovered = Client()
    status, login = recovered.request('auth/login', dict(email=state['email_a'], password=recovered_password))
    check('Recuperación manual conserva cuenta y propiedades', status == 200 and login['user']['id'] == state['users'][0] and 'QA propiedad editada' in recovered.request('mis-propiedades')[1])
    # Actualizar la contraseña de la fixture que se conservará para inspección visual.
    state['password'] = recovered_password
    STATE.write_text(json.dumps(state))
    # Otra publicación activa queda disponible para comprobar los botones en navegador.
    if '--keep' in sys.argv:
        _, result = a.request('vender/publicar', dict(publication, title='QA revisión visual de propiedades'), photo=True)
        check('Fixture visual', result['success'])
    print(f'{len(checks)} comprobaciones satisfactorias.')
finally:
    if '--keep' not in sys.argv:
        cleanup(state)
