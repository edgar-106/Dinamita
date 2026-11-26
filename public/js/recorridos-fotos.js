(() => {
    'use strict';
    const boot = JSON.parse(document.getElementById('ptBoot').textContent);
    const el = id => document.getElementById(id);
    const editor = boot.editor;
    let graph = boot.data.graph, version = boot.data.version, selected = graph.escena_inicial_id;
    let dirty = false, busy = false;
    let panorama = null, panoramaUrl = null, sceneGeneration = 0, flatView = false;
    const status = (message, error = false) => { el('ptStatus').textContent = message; el('ptStatus').classList.toggle('error', error); };
    const current = () => graph.escenas.find(s => s.id === selected);
    const button = (text, action) => { const b = document.createElement('button'); b.type = 'button'; b.textContent = text; b.addEventListener('click', action); return b; };
    const changed = () => { dirty = true; status('Tienes cambios sin guardar.'); };
    const base = window.APP_BASE_URL.replace(/\/$/, '') + '/';
    async function request(route, options = {}) {
        const response = await fetch(base + route, { ...options, headers: { 'X-CSRF-Token': window.CSRF_TOKEN, ...(options.headers || {}) } });
        let data;
        try { data = await response.json(); } catch (_) { throw new Error('El servidor no pudo procesar el archivo. Revisa el tamaño o inténtalo de nuevo.'); }
        if (!response.ok || !data.success) throw new Error(data.message || 'No fue posible completar la operación.');
        return data;
    }
    function lock(value) {
        busy = value;
        if (editor) {
            for (const id of ['ptSave', 'ptPublish', 'ptHide', 'ptCleanup', 'ptUpload']) el(id).disabled = value;
            el('ptSceneForm').querySelectorAll('input,select,button').forEach(n => { n.disabled = value; });
            el('ptLinkForm').querySelectorAll('input,select,button').forEach(n => { n.disabled = value; });
        }
    }
    function sceneList() {
        el('ptScenes').replaceChildren();
        el('ptSceneCount').textContent = graph.escenas.length + (graph.escenas.length === 1 ? ' espacio' : ' espacios');
        graph.escenas.forEach((s, index) => {
            const li = document.createElement('li');
            const b = button(s.nombre + (s.id === graph.escena_inicial_id ? ' · Inicio' : ''), () => select(s.id));
            const name = document.createElement('span'); name.textContent = s.nombre;
            const meta = document.createElement('small'); meta.textContent = String(index + 1).padStart(2, '0') + (s.id === graph.escena_inicial_id ? ' · Inicio' : ' · ' + (s.tipo_visual === 'panorama' ? 'Panorámica' : 'Fotografía'));
            const text = document.createElement('span'); text.className = 'pt-scene-text'; text.append(name, meta);
            const thumbnail = document.createElement('img'); thumbnail.src = s.imagen_url; thumbnail.alt = ''; thumbnail.loading = 'lazy';
            b.replaceChildren(thumbnail, text);
            b.setAttribute('aria-current', String(s.id === selected)); li.append(b); el('ptScenes').append(li);
        });
    }
    function select(id) {
        selected = id; flatView = false; render(); el('ptViewport').scrollLeft = 0;
    }
    function panoramaAngles(s) {
        const horizontal = Number(s.angulo_horizontal || 180);
        return { horizontal, vertical: Math.min(180, horizontal * s.alto / s.ancho) };
    }
    function panoramaFit(s) {
        const a = panoramaAngles(s), rect = el('ptPanorama').getBoundingClientRect();
        const aspect = Math.max(.3, rect.width / Math.max(1, rect.height));
        const verticalFit = 2 * Math.atan(Math.tan(Math.min(170,a.vertical) * Math.PI / 360) * aspect) * 180 / Math.PI;
        return Math.max(5,Math.min(110,a.horizontal,verticalFit*.92));
    }
    function resizePanorama() {
        if (!panorama || !current()) return;
        const fit = panoramaFit(current());
        panorama.setHfovBounds([Math.min(25,fit*.5),fit]);
        panorama.setHfov(Math.min(panorama.getHfov(),fit),0);
    }
    window.addEventListener('resize',resizePanorama);
    function disposePanorama() {
        sceneGeneration++;
        if (panorama) { panorama.destroy(); panorama = null; }
        if (panoramaUrl) { URL.revokeObjectURL(panoramaUrl); panoramaUrl = null; }
    }
    function openPanorama(s) {
        const generation = sceneGeneration;
        const image = new Image();
        image.onload = () => {
            if (generation !== sceneGeneration) return;
            // Reducir solo la textura de visualización; el archivo original se conserva.
            if (image.naturalWidth > 4096 || image.naturalHeight > 4096) {
                const scale = Math.min(4096 / image.naturalWidth, 4096 / image.naturalHeight);
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(image.naturalWidth * scale); canvas.height = Math.round(image.naturalHeight * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => {
                    if (generation !== sceneGeneration) return;
                    if (!blob) { fallback(); return; }
                    panoramaUrl = URL.createObjectURL(blob); build(panoramaUrl);
                }, 'image/jpeg', .92);
            } else build(s.imagen_url);
        };
        const fallback = () => { if (generation !== sceneGeneration) return; flatView = true; render(); status('No se pudo iniciar el visor interactivo en este dispositivo. Puedes ver la fotografía completa.', true); };
        image.onerror = fallback;
        image.src = s.imagen_url;
        function build(url) {
            if (generation !== sceneGeneration) return;
            if (!window.pannellum) { fallback(); return; }
            const a = panoramaAngles(s);
            const fit = panoramaFit(s);
            const hotSpots = graph.conexiones.filter(l => l.escena_origen_id === s.id).map(l => ({
                pitch:(.5-l.y)*a.vertical, yaw:(l.x-.5)*a.horizontal,
                cssClass:'pt-pano-link', createTooltipFunc:node => {
                    const b = button('↗ ' + l.etiqueta, () => select(l.escena_destino_id)); node.append(b);
                }
            }));
            try {
                panorama = window.pannellum.viewer('ptPanorama', {
                    type:'equirectangular', panorama:url, autoLoad:true, escapeHTML:true, ignoreGPanoXMP:true,
                    haov:a.horizontal, vaov:a.vertical, minYaw:-a.horizontal/2, maxYaw:a.horizontal/2,
                    minPitch:-a.vertical/2, maxPitch:a.vertical/2, avoidShowingBackground:true,
                    hfov:Math.min(90,fit), minHfov:Math.min(25,fit*.5), maxHfov:fit,
                    mouseZoom:'fullscreenonly', showFullscreenCtrl:true, hotSpots,
                    strings:{loadingLabel:'Cargando panorámica…',bylineLabel:'por %s',noPanoramaError:'No se pudo cargar la panorámica.',genericWebGLError:'El visor no está disponible en este dispositivo.'}
                });
                panorama.on('error', fallback);
                panorama.on('fullscreenchange',resizePanorama);
            } catch (_) { fallback(); }
        }
    }
    function marker() {
        if (!editor || !current()) return;
        el('ptPoints').querySelector('.pt-marker')?.remove();
        const point = document.createElement('span'); point.className = 'pt-marker';
        point.style.left = el('ptX').value + '%'; point.style.top = el('ptY').value + '%';
        el('ptPoints').append(point);
        if (panorama && current()) {
            const a = panoramaAngles(current()); panorama.removeHotSpot('draft-position');
            panorama.addHotSpot({id:'draft-position',pitch:(.5-Number(el('ptY').value)/100)*a.vertical,yaw:(Number(el('ptX').value)/100-.5)*a.horizontal,cssClass:'pt-pano-marker'});
        }
    }
    function render() {
        disposePanorama();
        sceneList();
        const s = current();
        el('ptImage').hidden = !s; el('ptEmpty').hidden = !!s;
        el('ptTitle').textContent = s ? s.nombre : (editor ? 'Agrega tu primer espacio' : 'No hay escenas guardadas');
        el('ptStartLabel').textContent = s && s.id === graph.escena_inicial_id ? 'Escena inicial' : '';
        el('ptPoints').replaceChildren(); el('ptLinks').replaceChildren();
        el('ptInstructions').textContent = s ? (s.tipo_visual === 'panorama' ? (Number(s.angulo_horizontal || 180) === 360 ? 'Arrastra para mirar alrededor. Usa + y − para acercarte. Cobertura declarada: 360°.' : 'Arrastra para mirar alrededor y usa + y − para acercarte. La vista respeta los extremos de la foto.') : 'Selecciona un punto para visitar otro espacio.') : 'Selecciona una fotografía para comenzar.';
        const immersive = s && s.tipo_visual === 'panorama' && !flatView;
        el('ptViewport').hidden = !!immersive; el('ptPanorama').hidden = !immersive;
        el('ptFlatView').hidden = !s || s.tipo_visual !== 'panorama';
        el('ptFlatView').textContent = flatView ? 'Abrir visor interactivo' : 'Ver fotografía completa';
        if (editor) {
            el('ptSceneForm').hidden = !s; el('ptLinkForm').hidden = !s || graph.escenas.length < 2;
        }
        if (!s) { el('ptImage').removeAttribute('src'); return; }
        el('ptImage').src = s.imagen_url; el('ptImage').alt = s.nombre;
        el('ptCanvas').classList.toggle('panorama', s.tipo_visual === 'panorama');
        if (editor) {
            el('ptName').value = s.nombre; el('ptType').value = s.tipo_visual;
            el('ptCoverageLabel').hidden = s.tipo_visual !== 'panorama';
            el('ptCoverage').value = String(s.angulo_horizontal || 180);
            el('ptDestination').replaceChildren();
            graph.escenas.filter(other => other.id !== selected).forEach(other => {
                const option = document.createElement('option'); option.value = other.id; option.textContent = other.nombre; el('ptDestination').append(option);
            });
            el('ptLabel').value = ''; el('ptX').value = 50; el('ptY').value = 50;
        }
        graph.conexiones.filter(l => l.escena_origen_id === selected).forEach(l => {
            const p = button(l.etiqueta, () => select(l.escena_destino_id));
            p.className = 'pt-point'; p.style.left = (l.x * 100) + '%'; p.style.top = (l.y * 100) + '%';
            el('ptPoints').append(p);
            const li = document.createElement('li'); li.append(button(l.etiqueta, () => select(l.escena_destino_id)));
            if (editor) li.append(button('Quitar conexión', () => {
                if (busy) return;
                graph.conexiones = graph.conexiones.filter(item => item !== l); changed(); render();
            }));
            el('ptLinks').append(li);
        });
        if (!el('ptLinks').children.length) { const li = document.createElement('li'); li.textContent = 'No hay conexiones desde este espacio. Puedes usar la lista de espacios.'; el('ptLinks').append(li); }
        if (immersive) openPanorama(s);
    }
    el('ptFlatView').addEventListener('click', () => { flatView = !flatView; render(); });
    let pointerStart = null;
    el('ptPanorama').addEventListener('pointerdown', e => { pointerStart = [e.clientX,e.clientY]; });
    el('ptPanorama').addEventListener('pointerup', e => {
        if (!editor || busy || !panorama || !pointerStart || e.target.closest('button,.pnlm-controls') || Math.hypot(e.clientX-pointerStart[0],e.clientY-pointerStart[1])>5) return;
        const coords = panorama.mouseEventToCoords(e), a = panoramaAngles(current());
        el('ptX').value = Math.max(0,Math.min(100,(coords[1]/a.horizontal+.5)*100)).toFixed(1);
        el('ptY').value = Math.max(0,Math.min(100,(.5-coords[0]/a.vertical)*100)).toFixed(1); marker();
    });
    if (editor) {
        el('ptUpload').addEventListener('change', async event => {
            const file = event.target.files[0]; if (!file || busy) return;
            if (graph.escenas.length >= 30) { status('Máximo 30 espacios por recorrido.', true); return; }
            if (file.size > 8 * 1024 * 1024) { status('La imagen supera los 8 MB.', true); event.target.value = ''; return; }
            const data = new FormData(); data.append('foto', file); lock(true); status('Subiendo fotografía…');
            try {
                const result = await request('mis-propiedades/' + boot.propertyId + '/recorrido/foto', { method: 'POST', body: data });
                graph.escenas.push(result.scene);
                if (!graph.escena_inicial_id) graph.escena_inicial_id = result.scene.id;
                selected = result.scene.id; changed(); render();
            } catch (e) { status(e.message, true); }
            finally { event.target.value = ''; lock(false); }
        });
        el('ptName').addEventListener('input', () => { if (!busy && current()) { current().nombre = el('ptName').value; el('ptTitle').textContent = current().nombre; sceneList(); changed(); } });
        el('ptType').addEventListener('change', () => {
            if (busy || !current()) return;
            const s = current();
            if (el('ptType').value === 'panorama' && s.ancho / s.alto < 1.5) { el('ptType').value = s.tipo_visual; status('Para una panorámica horizontal usa una foto al menos 1.5 veces más ancha que alta.', true); return; }
            s.tipo_visual = el('ptType').value; changed(); render();
        });
        el('ptCoverage').addEventListener('change', () => {
            if (busy || !current()) return;
            current().angulo_horizontal = Number(el('ptCoverage').value); changed(); render();
        });
        el('ptSceneForm').addEventListener('submit', e => e.preventDefault());
        el('ptSetStart').addEventListener('click', () => { if (busy || !current()) return; graph.escena_inicial_id = selected; changed(); render(); });
        el('ptRemove').addEventListener('click', () => {
            if (busy || !current() || !confirm('¿Quitar este espacio y sus conexiones del borrador? La versión publicada se conserva hasta volver a publicar.')) return;
            graph.escenas = graph.escenas.filter(s => s.id !== selected);
            graph.conexiones = graph.conexiones.filter(l => l.escena_origen_id !== selected && l.escena_destino_id !== selected);
            if (graph.escena_inicial_id === selected) graph.escena_inicial_id = graph.escenas[0]?.id || null;
            selected = graph.escena_inicial_id; changed(); render();
        });
        el('ptImage').addEventListener('click', event => {
            if (busy) return;
            const rect = event.target.getBoundingClientRect();
            el('ptX').value = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100)).toFixed(1);
            el('ptY').value = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100)).toFixed(1); marker();
        });
        ['ptX','ptY'].forEach(id => el(id).addEventListener('input', marker));
        el('ptLinkForm').addEventListener('submit', event => {
            event.preventDefault(); if (busy || !current()) return;
            const to = Number(el('ptDestination').value);
            const x = Number(el('ptX').value) / 100, y = Number(el('ptY').value) / 100;
            const label = el('ptLabel').value.trim();
            if (!label || !graph.escenas.some(s => s.id === to && s.id !== selected) || !Number.isFinite(x) || !Number.isFinite(y) || x < 0 || x > 1 || y < 0 || y > 1) { status('Revisa el destino, texto y posición del punto.', true); return; }
            graph.conexiones.push({ escena_origen_id:selected, escena_destino_id:to, etiqueta:label, coordenadas:'imagen_normalizada', x,y }); changed(); render();
        });
        async function save(action) {
            if (busy) return; lock(true);
            try {
                const result = await request('mis-propiedades/' + boot.propertyId + '/recorrido/guardar', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({version, graph, accion:action}) });
                version = result.version; graph = result.graph; dirty = false;
                status(action === 'publicar' ? 'Recorrido publicado. Ya está disponible en la ficha de tu propiedad.' : action === 'ocultar' ? 'Recorrido oculto para visitantes. Tu borrador se conserva.' : 'Borrador guardado. La versión pública no cambió.'); render();
            } catch (e) { status(e.message, true); } finally { lock(false); }
        }
        el('ptSave').addEventListener('click', () => save('guardar'));
        el('ptPublish').addEventListener('click', () => save('publicar'));
        el('ptHide').addEventListener('click', () => save('ocultar'));
        el('ptPreview').addEventListener('click', e => { if (dirty || busy) { e.preventDefault(); status('Guarda el borrador antes de abrir la vista previa.', true); } });
        el('ptCleanup').addEventListener('click', async () => {
            if (dirty) { status('Guarda primero el borrador.', true); return; }
            if (busy || !confirm('Se eliminarán las cargas que no aparecen en el borrador guardado ni en el recorrido publicado. Guarda antes los cambios de otras pestañas.')) return;
            lock(true);
            try { const result = await request('mis-propiedades/' + boot.propertyId + '/recorrido/limpiar',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({version})}); status(result.deleted + ' imágenes sin usar eliminadas.'); }
            catch(e) { status(e.message,true); } finally { lock(false); }
        });
        window.addEventListener('beforeunload', e => { if (dirty || busy) { e.preventDefault(); e.returnValue = ''; } });
        status(boot.data.published ? 'Hay una versión publicada. Los cambios se guardan primero como borrador.' : 'Tu recorrido aún no está publicado.');
    }
    el('ptImage').addEventListener('error', () => status('No se pudo cargar la fotografía. Comprueba tu conexión o recarga la página.', true));
    render();
})();
