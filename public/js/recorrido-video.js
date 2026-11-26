(function () {
    'use strict';

    const init = window.RECORRIDO_STUDIO_INIT;
    if (!init || !init.propiedad) return;

    const BASE_URL = window.APP_BASE_URL || '/';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || window.CSRF_TOKEN || '';
    const PROP_ID = Number(init.propiedad.id);
    const EDITABLE = Boolean(init.propiedad.editable);

    let state = {
        recorrido: init.recorrido,
        videos: Array.isArray(init.videos) ? init.videos : [],
        escenas: Array.isArray(init.escenas) ? init.escenas : [],
        conexiones: Array.isArray(init.conexiones) ? init.conexiones : [],
        procesador: init.procesador || {},
        activeVideoId: init.videos && init.videos[0] ? Number(init.videos[0].id) : 0,
        connOriginId: init.escenas && init.escenas[0] ? Number(init.escenas[0].id) : 0,
        draftCoords: { sistema: 'plano_xy', x: 50, y: 50 },
        fragmentEndLimit: null
    };

    const elStatus = document.getElementById('rvGlobalStatus');
    const elVideos = document.getElementById('listaVideos');
    const elEscenas = document.getElementById('listaEscenas');
    const elCountEscenas = document.getElementById('countEscenas');
    const elSinVideo = document.getElementById('sinVideoActivo');
    const elWorkspace = document.getElementById('extractorWorkspace');
    const player = document.getElementById('studioVideoPlayer');
    const elTimeLabel = document.getElementById('studioTimeLabel');
    const inputMoment = document.getElementById('sceneMoment');
    const inputStart = document.getElementById('sceneStart');
    const inputEnd = document.getElementById('sceneEnd');
    const inputNombre = document.getElementById('sceneNombre');

    function esc(v) {
        const div = document.createElement('div');
        div.textContent = String(v ?? '');
        return div.innerHTML;
    }

    function fmtTime(sec) {
        const s = Math.max(0, Number(sec || 0));
        const m = Math.floor(s / 60);
        const r = (s % 60).toFixed(2).padStart(5, '0');
        return m + ':' + r;
    }

    function fmtMb(bytes) {
        return (Number(bytes || 0) / 1048576).toFixed(2) + ' MB';
    }

    function showStatus(msg, kind) {
        if (!elStatus) return;
        elStatus.className = 'rv-banner rv-banner-' + (kind || 'info');
        elStatus.textContent = msg;
        elStatus.hidden = false;
        elStatus.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    async function apiJson(path, bodyObj) {
        const opts = bodyObj !== undefined
            ? {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify(bodyObj)
            }
            : { method: 'GET', headers: { 'Accept': 'application/json' } };

        const res = await fetch(BASE_URL + path, opts);
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'No se pudo completar la operación.');
        }
        return data;
    }

    async function reloadTour() {
        const data = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video');
        state.recorrido = data.recorrido;
        state.videos = data.videos || [];
        state.escenas = data.escenas || [];
        state.conexiones = data.conexiones || [];
        state.procesador = data.procesador || state.procesador;

        if (!state.videos.some(v => Number(v.id) === state.activeVideoId)) {
            state.activeVideoId = state.videos[0] ? Number(state.videos[0].id) : 0;
        }
        if (!state.escenas.some(s => Number(s.id) === state.connOriginId)) {
            state.connOriginId = state.escenas[0] ? Number(state.escenas[0].id) : 0;
        }
        renderAll();
    }

    function renderTourHeader() {
        const badge = document.getElementById('rvTourBadge');
        const btnToggle = document.getElementById('btnToggleTourState');
        const pub = state.recorrido?.estado === 'PUBLICADO';
        if (badge) {
            badge.textContent = state.recorrido?.estado || 'BORRADOR';
            badge.className = 'rv-badge ' + (pub ? 'rv-badge-published' : 'rv-badge-draft');
        }
        if (btnToggle) {
            btnToggle.textContent = pub ? 'Pasar a borrador' : 'Publicar recorrido';
        }
    }

    function renderVideos() {
        if (!state.videos.length) {
            elVideos.innerHTML = '<p class="rv-muted" style="margin:0;">Aún no has subido videos para esta propiedad.</p>';
        } else {
            elVideos.innerHTML = state.videos.map(v => {
                const active = Number(v.id) === state.activeVideoId;
                const dur = v.duracion_segundos ? fmtTime(v.duracion_segundos) : 'Duración en navegador';
                return `
                    <div class="rv-video-item ${active ? 'active' : ''}">
                        <div style="min-width:0;">
                            <strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(v.nombre_original)}</strong>
                            <small class="rv-muted">${esc(fmtMb(v.tamano_bytes))} · ${esc(dur)}</small>
                        </div>
                        <div style="display:flex;gap:6px;flex-shrink:0;">
                            <button type="button" class="rv-btn-sm" data-select-video="${v.id}">${active ? 'Activo' : 'Usar'}</button>
                            ${EDITABLE ? `<button type="button" class="rv-btn-sm rv-btn-danger" data-delete-video="${v.id}">Eliminar</button>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        const activeVideo = state.videos.find(v => Number(v.id) === state.activeVideoId);
        if (!activeVideo) {
            elSinVideo.hidden = false;
            elWorkspace.hidden = true;
            if (player.getAttribute('src')) {
                player.removeAttribute('src');
                player.load();
            }
            return;
        }

        elSinVideo.hidden = true;
        elWorkspace.hidden = false;
        if (player.dataset.loadedVideoId !== String(activeVideo.id)) {
            player.dataset.loadedVideoId = String(activeVideo.id);
            player.src = activeVideo.stream_url;
            player.load();
        }
    }

    function renderScenes() {
        elCountEscenas.textContent = String(state.escenas.length);
        if (!state.escenas.length) {
            elEscenas.innerHTML = '<p class="rv-muted" style="margin:0;">Todavía no hay escenas extraídas. Reproduce un video y guarda la primera.</p>';
            return;
        }

        elEscenas.innerHTML = state.escenas.map(s => {
            const frag = (s.inicio_segundos !== null && s.fin_segundos !== null)
                ? `${fmtTime(s.inicio_segundos)} – ${fmtTime(s.fin_segundos)}`
                : 'Sin fragmento';
            const mom = s.momento_segundos !== null ? fmtTime(s.momento_segundos) : 'N/A';

            return `
                <article class="rv-scene-card" data-scene-id="${s.id}">
                    <img class="rv-scene-thumb" src="${esc(s.imagen_url)}" alt="${esc(s.nombre)}">
                    <div class="rv-scene-meta">
                        <h3>#${s.orden} · ${esc(s.nombre)}</h3>
                        <div class="rv-scene-sub">
                            Tipo: <strong>${esc(s.tipo_visual)}</strong> · Foto en: <strong>${esc(mom)}</strong> · Fragmento: <strong>${esc(frag)}</strong> (${esc(s.origen_extraccion)})
                        </div>
                        ${EDITABLE ? `
                        <form class="rv-scene-edit-form" data-edit-scene="${s.id}">
                            <div class="rv-form-row" style="grid-template-columns:1.5fr 0.7fr 0.7fr 0.6fr;margin-bottom:8px;">
                                <div class="rv-field"><label>Nombre</label><input name="nombre" value="${esc(s.nombre)}" required maxlength="120"></div>
                                <div class="rv-field"><label>Inicio (s)</label><input name="inicio_segundos" type="number" min="0" step="0.01" value="${s.inicio_segundos ?? ''}"></div>
                                <div class="rv-field"><label>Fin (s)</label><input name="fin_segundos" type="number" min="0.01" step="0.01" value="${s.fin_segundos ?? ''}"></div>
                                <div class="rv-field"><label>Orden</label><input name="orden" type="number" min="1" max="999" value="${s.orden}"></div>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="submit" class="rv-btn-sm">Guardar cambios</button>
                                <button type="button" class="rv-btn-sm rv-btn-danger" data-delete-scene="${s.id}">Eliminar</button>
                            </div>
                        </form>
                        ` : ''}
                    </div>
                </article>
            `;
        }).join('');
    }

    function renderConnectionEditor() {
        const noConn = document.getElementById('sinConexionesPosibles');
        const box = document.getElementById('editorConexionesBox');
        const selOrigin = document.getElementById('connOriginSelect');
        const selTarget = document.getElementById('connTargetSelect');
        const img = document.getElementById('connStageImg');
        const pinsLayer = document.getElementById('connPinsLayer');
        const hint = document.getElementById('connCoordsHint');
        const listOrigin = document.getElementById('listaConexionesOrigen');

        if (state.escenas.length < 2) {
            noConn.hidden = false;
            box.hidden = true;
            return;
        }
        noConn.hidden = true;
        box.hidden = false;

        if (!state.escenas.some(s => Number(s.id) === state.connOriginId)) {
            state.connOriginId = Number(state.escenas[0].id);
        }

        selOrigin.innerHTML = state.escenas.map(s => `<option value="${s.id}" ${Number(s.id) === state.connOriginId ? 'selected' : ''}>#${s.orden} ${esc(s.nombre)}</option>`).join('');
        const targetOptions = state.escenas.filter(s => Number(s.id) !== state.connOriginId);
        const prevTarget = Number(selTarget.value || 0);
        selTarget.innerHTML = targetOptions.map(s => `<option value="${s.id}" ${Number(s.id) === prevTarget ? 'selected' : ''}>#${s.orden} ${esc(s.nombre)}</option>`).join('');

        const originScene = state.escenas.find(s => Number(s.id) === state.connOriginId);
        if (originScene) {
            img.src = originScene.imagen_url;
        }

        const outgoing = state.conexiones.filter(c => Number(c.escena_origen_id) === state.connOriginId);
        let pinsHtml = outgoing.map(c => {
            const dest = state.escenas.find(s => Number(s.id) === Number(c.escena_destino_id));
            const x = c.posicion?.x ?? 50;
            const y = c.posicion?.y ?? 50;
            return `<span class="rv-pin" style="left:${x}%;top:${y}%;" title="${esc(c.etiqueta)} → ${esc(dest?.nombre || '')}">📍 ${esc(c.etiqueta)}</span>`;
        }).join('');

        if (EDITABLE) {
            pinsHtml += `<span class="rv-pin rv-pin-draft" style="left:${state.draftCoords.x}%;top:${state.draftCoords.y}%;">✚ Nuevo punto</span>`;
        }
        pinsLayer.innerHTML = pinsHtml;

        if (hint) {
            hint.innerHTML = `Coordenada seleccionada: <strong>x: ${state.draftCoords.x}%, y: ${state.draftCoords.y}%</strong> (haz clic en la imagen para moverla).`;
        }

        listOrigin.innerHTML = outgoing.length
            ? outgoing.map(c => {
                const dest = state.escenas.find(s => Number(s.id) === Number(c.escena_destino_id));
                return `
                    <div class="rv-video-item">
                        <div>
                            <strong>${esc(c.etiqueta)}</strong>
                            <small class="rv-muted" style="display:block;">Lleva a: ${esc(dest?.nombre || 'Escena #' + c.escena_destino_id)} (${esc(c.posicion?.x ?? 50)}%, ${esc(c.posicion?.y ?? 50)}%)</small>
                        </div>
                        ${EDITABLE ? `<button type="button" class="rv-btn-sm rv-btn-danger" data-delete-conn="${c.id}">Quitar</button>` : ''}
                    </div>
                `;
            }).join('')
            : '<p class="rv-muted" style="margin:0;">Esta escena todavía no tiene puntos interactivos salientes.</p>';
    }

    function renderContractJson() {
        const pre = document.getElementById('rvContractJson');
        if (!pre || !window.InfonatecTourContract) return;
        const normalized = window.InfonatecTourContract.normalizeTour(state);
        pre.textContent = JSON.stringify(normalized, null, 2);
    }

    function renderAll() {
        renderTourHeader();
        renderVideos();
        renderScenes();
        renderConnectionEditor();
        renderContractJson();
    }

    function readSelectedFileMeta(file) {
        return new Promise((resolve) => {
            const tempVideo = document.createElement('video');
            const objectUrl = URL.createObjectURL(file);
            tempVideo.preload = 'metadata';
            tempVideo.onloadedmetadata = () => {
                const meta = {
                    duration: Number.isFinite(tempVideo.duration) ? tempVideo.duration : null,
                    width: tempVideo.videoWidth || null,
                    height: tempVideo.videoHeight || null
                };
                URL.revokeObjectURL(objectUrl);
                resolve(meta);
            };
            tempVideo.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                resolve({ duration: null, width: null, height: null });
            };
            tempVideo.src = objectUrl;
        });
    }

    function captureVideoFrameBlob() {
        return new Promise((resolve, reject) => {
            if (!player || player.readyState < 2 || !player.videoWidth || !player.videoHeight) {
                reject(new Error('El reproductor aún no tiene el fotograma listo. Espera un instante o mueve la reproducción al segundo deseado.'));
                return;
            }
            const maxW = 1280;
            const scale = player.videoWidth > maxW ? (maxW / player.videoWidth) : 1;
            const w = Math.max(160, Math.round(player.videoWidth * scale));
            const h = Math.max(90, Math.round(player.videoHeight * scale));
            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                reject(new Error('Tu navegador no permite capturar fotogramas en canvas.'));
                return;
            }
            try {
                ctx.drawImage(player, 0, 0, w, h);
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('No se pudo generar la imagen JPEG desde el video.'));
                        return;
                    }
                    resolve(blob);
                }, 'image/jpeg', 0.88);
            } catch (e) {
                reject(new Error('No fue posible capturar el fotograma desde el navegador: ' + e.message));
            }
        });
    }

    function seekTo(targetSec) {
        if (!player || !Number.isFinite(targetSec)) return Promise.resolve();
        const clamped = Math.max(0, Math.min(Number.isFinite(player.duration) ? player.duration : targetSec, targetSec));
        if (Math.abs(player.currentTime - clamped) < 0.04 && player.readyState >= 2) {
            return Promise.resolve();
        }
        return new Promise((resolve) => {
            let done = false;
            const finish = () => {
                if (done) return;
                done = true;
                player.removeEventListener('seeked', finish);
                resolve();
            };
            player.addEventListener('seeked', finish);
            player.currentTime = clamped;
            setTimeout(finish, 900);
        });
    }

    // Subida de video con progreso real
    const formUpload = document.getElementById('formUploadVideo');
    if (formUpload) {
        formUpload.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('inputVideoFile');
            const file = fileInput?.files?.[0];
            if (!file) {
                showStatus('Selecciona un archivo de video antes de subir.', 'err');
                return;
            }
            const maxBytes = Number(state.procesador?.max_video_bytes || 36700160);
            if (file.size > maxBytes) {
                showStatus('El video supera el límite permitido (' + state.procesador?.max_video_mb + ' MB).', 'err');
                return;
            }

            const btn = document.getElementById('btnUploadVideo');
            const wrap = document.getElementById('uploadProgressWrap');
            const bar = document.getElementById('uploadProgressBar');
            const text = document.getElementById('uploadProgressText');
            btn.disabled = true;
            wrap.hidden = false;
            bar.style.width = '0%';
            text.textContent = 'Analizando metadatos del video...';

            const meta = await readSelectedFileMeta(file);
            const fd = new FormData();
            fd.append('video', file);
            if (meta.duration !== null) fd.append('duracion_segundos', meta.duration.toFixed(2));
            if (meta.width !== null) fd.append('ancho', String(meta.width));
            if (meta.height !== null) fd.append('alto', String(meta.height));

            const xhr = new XMLHttpRequest();
            xhr.open('POST', BASE_URL + 'api/mis-propiedades/' + PROP_ID + '/recorrido-video/videos', true);
            xhr.setRequestHeader('X-CSRF-Token', CSRF);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (ev) => {
                if (ev.lengthComputable) {
                    const pct = Math.round((ev.loaded / ev.total) * 100);
                    bar.style.width = pct + '%';
                    text.textContent = `Subiendo video: ${pct}% (${fmtMb(ev.loaded)} / ${fmtMb(ev.total)})`;
                }
            };

            xhr.onload = async () => {
                btn.disabled = false;
                let resp = null;
                try { resp = JSON.parse(xhr.responseText); } catch (_) {}
                if (xhr.status >= 200 && xhr.status < 300 && resp?.success) {
                    formUpload.reset();
                    text.textContent = 'Carga completa.';
                    state.activeVideoId = Number(resp.video?.id || 0);
                    await reloadTour();
                    showStatus(resp.message || 'Video cargado.', 'ok');
                } else {
                    wrap.hidden = true;
                    text.textContent = '';
                    showStatus(resp?.message || 'Error al subir el video (HTTP ' + xhr.status + ').', 'err');
                }
            };

            xhr.onerror = () => {
                btn.disabled = false;
                wrap.hidden = true;
                text.textContent = '';
                showStatus('Se interrumpió la conexión durante la carga del video.', 'err');
            };

            xhr.send(fd);
        });
    }

    // Eventos del reproductor
    if (player) {
        player.addEventListener('timeupdate', () => {
            elTimeLabel.textContent = fmtTime(player.currentTime) + ' / ' + fmtTime(player.duration || 0);
            if (state.fragmentEndLimit !== null && player.currentTime >= state.fragmentEndLimit) {
                player.pause();
                state.fragmentEndLimit = null;
            }
        });
        player.addEventListener('loadedmetadata', () => {
            elTimeLabel.textContent = fmtTime(player.currentTime) + ' / ' + fmtTime(player.duration || 0);
        });
    }

    document.querySelectorAll('[data-seek]').forEach(btn => {
        btn.addEventListener('click', () => {
            const delta = Number(btn.dataset.seek || 0);
            seekTo((player.currentTime || 0) + delta).then(() => {
                inputMoment.value = (player.currentTime || 0).toFixed(2);
            });
        });
    });

    document.getElementById('btnSetMoment')?.addEventListener('click', () => {
        inputMoment.value = (player.currentTime || 0).toFixed(2);
    });
    document.getElementById('btnSetStart')?.addEventListener('click', () => {
        inputStart.value = (player.currentTime || 0).toFixed(2);
    });
    document.getElementById('btnSetEnd')?.addEventListener('click', () => {
        inputEnd.value = (player.currentTime || 0).toFixed(2);
    });
    document.getElementById('btnTestFragment')?.addEventListener('click', async () => {
        const start = Number(inputStart.value || 0);
        const end = Number(inputEnd.value || 0);
        if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
            showStatus('Define inicio y fin válidos (fin mayor que inicio) para probar el fragmento.', 'warn');
            return;
        }
        state.fragmentEndLimit = end;
        await seekTo(start);
        player.play().catch(() => {});
    });

    document.querySelectorAll('[data-chip]').forEach(chip => {
        chip.addEventListener('click', () => {
            inputNombre.value = chip.dataset.chip || '';
            inputNombre.focus();
        });
    });

    // Crear escena desde video
    document.getElementById('formCreateScene')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!state.activeVideoId) {
            showStatus('Selecciona o sube un video primero.', 'err');
            return;
        }
        const moment = Number(inputMoment.value);
        if (!Number.isFinite(moment) || moment < 0) {
            showStatus('Indica un momento válido en segundos.', 'err');
            return;
        }

        const btn = document.getElementById('btnSaveScene');
        btn.disabled = true;
        btn.textContent = 'Extrayendo escena...';

        try {
            const fd = new FormData();
            fd.append('video_id', String(state.activeVideoId));
            fd.append('nombre', inputNombre.value.trim());
            fd.append('momento_segundos', moment.toFixed(2));
            if (inputStart.value !== '') fd.append('inicio_segundos', Number(inputStart.value).toFixed(2));
            if (inputEnd.value !== '') fd.append('fin_segundos', Number(inputEnd.value).toFixed(2));

            if (state.procesador?.ffmpeg_available) {
                fd.append('modo_extraccion', 'ffmpeg');
            } else {
                await seekTo(moment);
                const blob = await captureVideoFrameBlob();
                fd.append('modo_extraccion', 'navegador');
                fd.append('frame_image', blob, 'escena.jpg');
            }

            const res = await fetch(BASE_URL + 'api/mis-propiedades/' + PROP_ID + '/recorrido-video/escenas', {
                method: 'POST',
                headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No se pudo guardar la escena.');
            }

            inputNombre.value = '';
            inputStart.value = '';
            inputEnd.value = '';
            await reloadTour();
            showStatus(data.message || 'Escena guardada.', 'ok');
        } catch (err) {
            showStatus(err.message, 'err');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Extraer imagen y guardar escena';
        }
    });

    // Lista de videos: usar / eliminar
    elVideos?.addEventListener('click', async (e) => {
        const selBtn = e.target.closest('[data-select-video]');
        if (selBtn) {
            state.activeVideoId = Number(selBtn.dataset.selectVideo);
            renderVideos();
            return;
        }
        const delBtn = e.target.closest('[data-delete-video]');
        if (delBtn && confirm('¿Eliminar este video? Las imágenes de escenas ya extraídas se conservarán.')) {
            try {
                const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/videos/' + delBtn.dataset.deleteVideo + '/eliminar', {});
                await reloadTour();
                showStatus(res.message, 'ok');
            } catch (err) {
                showStatus(err.message, 'err');
            }
        }
    });

    // Lista de escenas: editar / eliminar
    elEscenas?.addEventListener('submit', async (e) => {
        const form = e.target.closest('[data-edit-scene]');
        if (!form) return;
        e.preventDefault();
        const sceneId = form.dataset.editScene;
        const payload = {
            nombre: form.elements.nombre.value.trim(),
            inicio_segundos: form.elements.inicio_segundos.value,
            fin_segundos: form.elements.fin_segundos.value,
            orden: form.elements.orden.value
        };
        try {
            const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/escenas/' + sceneId + '/actualizar', payload);
            await reloadTour();
            showStatus(res.message, 'ok');
        } catch (err) {
            showStatus(err.message, 'err');
        }
    });

    elEscenas?.addEventListener('click', async (e) => {
        const delBtn = e.target.closest('[data-delete-scene]');
        if (delBtn && confirm('¿Eliminar esta escena y sus conexiones asociadas?')) {
            try {
                const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/escenas/' + delBtn.dataset.deleteScene + '/eliminar', {});
                await reloadTour();
                showStatus(res.message, 'ok');
            } catch (err) {
                showStatus(err.message, 'err');
            }
        }
    });

    // Editor compartido de conexiones
    document.getElementById('connOriginSelect')?.addEventListener('change', (e) => {
        state.connOriginId = Number(e.target.value);
        renderConnectionEditor();
    });

    document.getElementById('connStageWrap')?.addEventListener('click', (e) => {
        if (!EDITABLE) return;
        const img = document.getElementById('connStageImg');
        if (!img) return;
        const rect = img.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;
        const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
        const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
        state.draftCoords = {
            sistema: 'plano_xy',
            x: Math.round(x * 100) / 100,
            y: Math.round(y * 100) / 100
        };
        renderConnectionEditor();
    });

    document.getElementById('formCreateConnection')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const targetId = Number(document.getElementById('connTargetSelect').value || 0);
        const label = document.getElementById('connLabel').value.trim();
        try {
            const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/conexiones', {
                escena_origen_id: state.connOriginId,
                escena_destino_id: targetId,
                etiqueta: label,
                posicion: state.draftCoords
            });
            document.getElementById('connLabel').value = '';
            await reloadTour();
            showStatus(res.message, 'ok');
        } catch (err) {
            showStatus(err.message, 'err');
        }
    });

    document.getElementById('listaConexionesOrigen')?.addEventListener('click', async (e) => {
        const delBtn = e.target.closest('[data-delete-conn]');
        if (!delBtn) return;
        try {
            const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/conexiones/' + delBtn.dataset.deleteConn + '/eliminar', {});
            await reloadTour();
            showStatus(res.message, 'ok');
        } catch (err) {
            showStatus(err.message, 'err');
        }
    });

    // Publicar / pasar a borrador
    document.getElementById('btnToggleTourState')?.addEventListener('click', async () => {
        const nextState = state.recorrido?.estado === 'PUBLICADO' ? 'BORRADOR' : 'PUBLICADO';
        try {
            const res = await apiJson('api/mis-propiedades/' + PROP_ID + '/recorrido-video/estado', { estado: nextState });
            await reloadTour();
            showStatus(res.message, 'ok');
        } catch (err) {
            showStatus(err.message, 'err');
        }
    });

    renderAll();
})();
