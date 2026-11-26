(() => {
    'use strict';

    const bootNode = document.getElementById('ptBoot');
    const panel = document.getElementById('ptVideoPanel');
    if (!bootNode || !panel) return;

    const boot = JSON.parse(bootNode.textContent);
    if (!boot.editor) return;

    const el = id => document.getElementById(id);
    const propId = Number(boot.propertyId);
    const base = window.APP_BASE_URL.replace(/\/$/, '') + '/';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || window.CSRF_TOKEN || '';

    let videos = [];
    let processor = {};
    let activeVideoId = 0;
    let loaded = false;
    let previewEndLimit = null;

    function fmtTime(sec) {
        const s = Math.max(0, Number(sec || 0));
        const m = Math.floor(s / 60);
        const r = (s % 60).toFixed(2).padStart(5, '0');
        return m + ':' + r;
    }

    function fmtMb(bytes) {
        return (Number(bytes || 0) / 1048576).toFixed(2) + ' MB';
    }

    function setVideoStatus(msg, isError = false) {
        const node = el('ptVideoStatus');
        if (!node) return;
        node.textContent = msg;
        node.classList.toggle('is-error', Boolean(isError));
    }

    function updateProcessorUi() {
        const badge = el('ptVideoProcessorBadge');
        const limitHint = el('ptVideoLimitHint');
        const triggerSub = el('ptOpenVideoStudio')?.querySelector('span:last-child');
        if (triggerSub) {
            triggerSub.textContent = videos.length
                ? videos.length + (videos.length === 1 ? ' video listo · abre para extraer escenas' : ' videos listos · abre para extraer escenas')
                : 'MP4, MOV o WebM · elige escenas';
        }
        if (badge) {
            badge.textContent = processor.ffmpeg_available
                ? 'FFmpeg activo en servidor'
                : 'Captura desde navegador activa';
        }
        if (limitHint && processor.max_video_mb) {
            limitHint.textContent = 'Máximo ' + processor.max_video_mb + ' MB por archivo';
        }
    }

    function renderVideoList() {
        const listEl = el('ptVideoList');
        const emptyEl = el('ptVideoEmpty');
        const workEl = el('ptVideoWorkspace');
        const player = el('ptVideoPlayer');
        if (!listEl || !emptyEl || !workEl || !player) return;

        listEl.replaceChildren();
        if (!videos.length) {
            const p = document.createElement('p');
            p.className = 'pt-hint';
            p.textContent = 'Aún no hay videos subidos para esta propiedad.';
            listEl.append(p);
        } else {
            videos.forEach(v => {
                const row = document.createElement('div');
                row.className = 'rv-video-item' + (Number(v.id) === activeVideoId ? ' active' : '');

                const info = document.createElement('div');
                info.style.minWidth = '0';
                const title = document.createElement('strong');
                title.style.display = 'block';
                title.style.overflow = 'hidden';
                title.style.textOverflow = 'ellipsis';
                title.style.whiteSpace = 'nowrap';
                title.textContent = v.nombre_original;
                const sub = document.createElement('small');
                sub.className = 'pt-hint';
                sub.style.margin = '0';
                sub.textContent = fmtMb(v.tamano_bytes) + ' · ' + (v.duracion_segundos ? fmtTime(v.duracion_segundos) : 'MP4/MOV/WebM');
                info.append(title, sub);

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '6px';
                actions.style.flexShrink = '0';

                const useBtn = document.createElement('button');
                useBtn.type = 'button';
                useBtn.className = 'rv-btn-sm';
                useBtn.textContent = Number(v.id) === activeVideoId ? 'Activo' : 'Usar';
                useBtn.addEventListener('click', () => {
                    activeVideoId = Number(v.id);
                    renderVideoList();
                });

                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'rv-btn-sm rv-btn-danger';
                delBtn.textContent = 'Eliminar';
                delBtn.addEventListener('click', () => handleDeleteVideo(v.id));

                actions.append(useBtn, delBtn);
                row.append(info, actions);
                listEl.append(row);
            });
        }

        const active = videos.find(v => Number(v.id) === activeVideoId);
        if (!active) {
            emptyEl.hidden = false;
            workEl.hidden = true;
            if (player.getAttribute('src')) {
                player.removeAttribute('src');
                player.load();
            }
            delete player.dataset.loadedVideoId;
            return;
        }

        emptyEl.hidden = true;
        workEl.hidden = false;
        if (player.dataset.loadedVideoId !== String(active.id)) {
            player.dataset.loadedVideoId = String(active.id);
            player.src = active.stream_url;
            player.load();
        }
    }

    async function loadVideos(force = false) {
        if (loaded && !force) return;
        try {
            const res = await fetch(base + 'api/mis-propiedades/' + propId + '/recorrido-video/videos', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No se pudieron cargar los videos.');
            }
            processor = data.procesador || {};
            videos = Array.isArray(data.videos) ? data.videos : [];
            if (!videos.some(v => Number(v.id) === activeVideoId)) {
                activeVideoId = videos[0] ? Number(videos[0].id) : 0;
            }
            loaded = true;
            updateProcessorUi();
            renderVideoList();
            if (videos.length) {
                setVideoStatus('Tienes ' + videos.length + (videos.length === 1 ? ' video disponible' : ' videos disponibles') + ' en almacenamiento privado.');
            } else {
                setVideoStatus('Sube un video para reproducirlo y extraer escenas al recorrido compartido.');
            }
        } catch (err) {
            setVideoStatus(err.message, true);
        }
    }

    function openPanel() {
        panel.hidden = false;
        loadVideos();
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function readSelectedFileMeta(file) {
        return new Promise(resolve => {
            const temp = document.createElement('video');
            const url = URL.createObjectURL(file);
            temp.preload = 'metadata';
            temp.onloadedmetadata = () => {
                const meta = {
                    duration: Number.isFinite(temp.duration) ? temp.duration : null,
                    width: temp.videoWidth || null,
                    height: temp.videoHeight || null
                };
                URL.revokeObjectURL(url);
                resolve(meta);
            };
            temp.onerror = () => {
                URL.revokeObjectURL(url);
                resolve({ duration: null, width: null, height: null });
            };
            temp.src = url;
        });
    }

    function seekTo(player, targetSec) {
        if (!player || !Number.isFinite(targetSec)) return Promise.resolve();
        const maxDur = Number.isFinite(player.duration) ? player.duration : targetSec;
        const clamped = Math.max(0, Math.min(maxDur, targetSec));
        if (Math.abs(player.currentTime - clamped) < 0.04 && player.readyState >= 2) {
            return Promise.resolve();
        }
        return new Promise(resolve => {
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

    function captureFrameBlob(player) {
        return new Promise((resolve, reject) => {
            if (!player || player.readyState < 2 || !player.videoWidth || !player.videoHeight) {
                reject(new Error('Reproduce o pausa el video en un segundo válido antes de capturar la escena.'));
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
                reject(new Error('Tu navegador no admite captura de fotogramas en canvas.'));
                return;
            }
            try {
                ctx.drawImage(player, 0, 0, w, h);
                canvas.toBlob(blob => {
                    if (!blob) {
                        reject(new Error('No se pudo generar la imagen JPEG del fotograma.'));
                        return;
                    }
                    resolve(blob);
                }, 'image/jpeg', 0.88);
            } catch (e) {
                reject(new Error('No fue posible capturar el fotograma: ' + e.message));
            }
        });
    }

    async function handleUploadFile(file) {
        if (!file) return;
        const maxBytes = Number(processor.max_video_bytes || 36700160);
        if (file.size > maxBytes) {
            setVideoStatus('El video supera el tamaño máximo permitido (' + (processor.max_video_mb || 35) + ' MB).', true);
            return;
        }

        const btn = el('ptVideoUploadBtn');
        const wrap = el('ptVideoProgressWrap');
        const bar = el('ptVideoProgressBar');
        const text = el('ptVideoProgressText');

        btn.disabled = true;
        wrap.hidden = false;
        bar.style.width = '0%';
        text.textContent = 'Leyendo metadatos del video…';

        const meta = await readSelectedFileMeta(file);
        const fd = new FormData();
        fd.append('video', file);
        if (meta.duration !== null) fd.append('duracion_segundos', meta.duration.toFixed(2));
        if (meta.width !== null) fd.append('ancho', String(meta.width));
        if (meta.height !== null) fd.append('alto', String(meta.height));

        const xhr = new XMLHttpRequest();
        xhr.open('POST', base + 'api/mis-propiedades/' + propId + '/recorrido-video/videos', true);
        xhr.setRequestHeader('X-CSRF-Token', csrf);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = ev => {
            if (ev.lengthComputable) {
                const pct = Math.round((ev.loaded / ev.total) * 100);
                bar.style.width = pct + '%';
                text.textContent = `Subiendo video: ${pct}% (${fmtMb(ev.loaded)} / ${fmtMb(ev.total)})`;
            }
        };

        xhr.onload = async () => {
            btn.disabled = false;
            el('ptVideoFile').value = '';
            let resp = null;
            try { resp = JSON.parse(xhr.responseText); } catch (_) {}
            if (xhr.status >= 200 && xhr.status < 300 && resp?.success) {
                text.textContent = 'Carga completa.';
                activeVideoId = Number(resp.video?.id || 0);
                await loadVideos(true);
                setVideoStatus(resp.message || 'Video cargado en almacenamiento protegido.');
            } else {
                wrap.hidden = true;
                text.textContent = '';
                setVideoStatus(resp?.message || 'No se pudo subir el video (HTTP ' + xhr.status + ').', true);
            }
        };

        xhr.onerror = () => {
            btn.disabled = false;
            wrap.hidden = true;
            text.textContent = '';
            setVideoStatus('Se interrumpió la conexión durante la carga del video.', true);
        };

        xhr.send(fd);
    }

    async function handleDeleteVideo(videoId) {
        if (!confirm('¿Eliminar este video privado? Solo es posible si ninguna escena del borrador o publicación lo utiliza.')) {
            return;
        }
        try {
            const res = await fetch(base + 'api/mis-propiedades/' + propId + '/recorrido-video/videos/' + videoId + '/eliminar', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No se pudo eliminar el video.');
            }
            await loadVideos(true);
            setVideoStatus(data.message || 'Video eliminado.');
        } catch (err) {
            setVideoStatus(err.message, true);
        }
    }

    async function handleExtractScene() {
        const player = el('ptVideoPlayer');
        const nameInput = el('ptVideoSceneName');
        const startInput = el('ptVideoStartInput');
        const endInput = el('ptVideoEndInput');
        const btn = el('ptExtractSceneBtn');

        if (!activeVideoId) {
            setVideoStatus('Selecciona o sube un video primero.', true);
            return;
        }
        if ((window.InfonatecPhotoTour?.getSceneCount?.() || 0) >= 30) {
            setVideoStatus('Alcanzaste el máximo de 30 escenas en el recorrido.', true);
            return;
        }
        const name = nameInput.value.trim();
        if (!name) {
            setVideoStatus('Escribe un nombre para identificar el espacio (ej. Sala, Cocina).', true);
            nameInput.focus();
            return;
        }

        const moment = Number(player?.currentTime || 0);
        if (!Number.isFinite(moment) || moment < 0) {
            setVideoStatus('Posiciónate en un segundo válido del video.', true);
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Extrayendo escena…';

        try {
            const fd = new FormData();
            fd.append('video_id', String(activeVideoId));
            fd.append('nombre', name);
            fd.append('momento_segundos', moment.toFixed(2));
            if (Number.isFinite(player.duration) && player.duration > 0) {
                fd.append('duracion_segundos', player.duration.toFixed(2));
            }
            if (startInput.value !== '') fd.append('inicio_segundos', Number(startInput.value).toFixed(2));
            if (endInput.value !== '') fd.append('fin_segundos', Number(endInput.value).toFixed(2));

            if (processor.ffmpeg_available) {
                fd.append('modo_extraccion', 'ffmpeg');
            } else {
                const blob = await captureFrameBlob(player);
                fd.append('modo_extraccion', 'navegador');
                fd.append('frame', blob, 'escena.jpg');
            }

            const res = await fetch(base + 'api/mis-propiedades/' + propId + '/recorrido-video/escenas', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
                body: fd
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'No fue posible extraer la escena.');
            }

            nameInput.value = '';
            startInput.value = '';
            endInput.value = '';
            window.InfonatecPhotoTour?.addScene(data.scene);
            setVideoStatus('Escena "' + data.scene.nombre + '" agregada al recorrido. Recuerda guardar o publicar.');
        } catch (err) {
            setVideoStatus(err.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Extraer escena al recorrido';
        }
    }

    el('ptOpenVideoStudio')?.addEventListener('click', openPanel);
    el('ptCloseVideoStudio')?.addEventListener('click', () => { panel.hidden = true; });
    el('ptVideoUploadBtn')?.addEventListener('click', () => el('ptVideoFile')?.click());
    el('ptVideoFile')?.addEventListener('change', e => handleUploadFile(e.target.files?.[0]));
    el('ptExtractSceneBtn')?.addEventListener('click', handleExtractScene);

    const player = el('ptVideoPlayer');
    if (player) {
        player.addEventListener('timeupdate', () => {
            if (el('ptVideoTime')) {
                el('ptVideoTime').textContent = fmtTime(player.currentTime) + ' / ' + fmtTime(player.duration || 0);
            }
            if (previewEndLimit !== null && !player.paused && player.currentTime >= previewEndLimit) {
                player.pause();
                previewEndLimit = null;
            }
        });
        player.addEventListener('loadedmetadata', () => {
            if (el('ptVideoTime')) {
                el('ptVideoTime').textContent = fmtTime(player.currentTime) + ' / ' + fmtTime(player.duration || 0);
            }
        });
    }

    panel.querySelectorAll('[data-seek]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!player) return;
            seekTo(player, (player.currentTime || 0) + Number(btn.dataset.seek || 0));
        });
    });

    panel.querySelectorAll('[data-scene-chip]').forEach(chip => {
        chip.addEventListener('click', () => {
            const input = el('ptVideoSceneName');
            if (!input) return;
            input.value = chip.dataset.sceneChip || '';
            input.focus();
        });
    });

    el('ptMarkStart')?.addEventListener('click', () => {
        if (el('ptVideoStartInput') && player) el('ptVideoStartInput').value = (player.currentTime || 0).toFixed(2);
    });
    el('ptMarkEnd')?.addEventListener('click', () => {
        if (el('ptVideoEndInput') && player) el('ptVideoEndInput').value = (player.currentTime || 0).toFixed(2);
    });
    el('ptClearRange')?.addEventListener('click', () => {
        if (el('ptVideoStartInput')) el('ptVideoStartInput').value = '';
        if (el('ptVideoEndInput')) el('ptVideoEndInput').value = '';
        previewEndLimit = null;
    });
    el('ptTestRange')?.addEventListener('click', async () => {
        if (!player) return;
        const start = Number(el('ptVideoStartInput')?.value || 0);
        const end = Number(el('ptVideoEndInput')?.value || 0);
        if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
            setVideoStatus('Define un inicio y un fin válidos (fin mayor que inicio) para probar el fragmento.', true);
            return;
        }
        previewEndLimit = end;
        await seekTo(player, start);
        player.play().catch(() => {});
    });

    if (window.location.hash === '#video') {
        openPanel();
    } else {
        loadVideos();
    }
})();
