(function () {
    'use strict';

    const raw = window.RECORRIDO_VISOR_INIT;
    if (!raw || !window.InfonatecTourContract) return;

    const tour = window.InfonatecTourContract.normalizeTour(raw);
    const scenes = tour.escenas || [];
    const connections = tour.conexiones || [];

    const elName = document.getElementById('visorSceneName');
    const elImgWrap = document.getElementById('visorImageWrap');
    const elImg = document.getElementById('visorSceneImg');
    const elHotspots = document.getElementById('visorHotspots');
    const elPano = document.getElementById('visorPanorama');
    const elThumbs = document.getElementById('visorThumbs');
    const btnPlayFragment = document.getElementById('btnPlayFragment');
    const overlay = document.getElementById('visorVideoOverlay');
    const videoEl = document.getElementById('visorFragmentVideo');
    const videoLabel = document.getElementById('visorVideoLabel');

    let activeIndex = 0;
    if (tour.recorrido.escena_inicial_id) {
        const idx = scenes.findIndex(s => s.id === tour.recorrido.escena_inicial_id);
        if (idx >= 0) activeIndex = idx;
    }

    let pannellumInstance = null;
    let activeFragmentEnd = null;
    let activeFragmentStart = 0;

    function fmtTime(sec) {
        const s = Math.max(0, Number(sec || 0));
        const m = Math.floor(s / 60);
        const r = Math.floor(s % 60).toString().padStart(2, '0');
        return m + ':' + r;
    }

    function esc(v) {
        const div = document.createElement('div');
        div.textContent = String(v ?? '');
        return div.innerHTML;
    }

    function closeFragment() {
        if (videoEl) {
            videoEl.pause();
        }
        if (overlay) {
            overlay.hidden = true;
        }
    }

    function playSceneFragment() {
        const scene = scenes[activeIndex];
        if (!scene || !scene.video_fragmento_url || !videoEl || !overlay) return;

        activeFragmentStart = scene.inicio_segundos !== null ? scene.inicio_segundos : (scene.momento_segundos || 0);
        activeFragmentEnd = scene.fin_segundos !== null ? scene.fin_segundos : null;

        const rangeTxt = activeFragmentEnd !== null
            ? ` (${fmtTime(activeFragmentStart)} – ${fmtTime(activeFragmentEnd)})`
            : '';
        videoLabel.textContent = scene.nombre + rangeTxt;
        overlay.hidden = false;

        const startSeek = () => {
            videoEl.currentTime = activeFragmentStart;
            videoEl.play().catch(() => {});
        };

        if (videoEl.dataset.sceneUrl !== scene.video_fragmento_url) {
            videoEl.dataset.sceneUrl = scene.video_fragmento_url;
            videoEl.onloadedmetadata = startSeek;
            videoEl.src = scene.video_fragmento_url;
            videoEl.load();
        } else {
            startSeek();
        }
    }

    if (videoEl) {
        videoEl.addEventListener('timeupdate', () => {
            if (activeFragmentEnd !== null && videoEl.currentTime >= activeFragmentEnd) {
                videoEl.pause();
            }
        });
    }

    function selectSceneById(sceneId) {
        const idx = scenes.findIndex(s => s.id === Number(sceneId));
        if (idx >= 0) {
            activeIndex = idx;
            renderScene();
        }
    }

    function renderScene() {
        closeFragment();
        if (!scenes.length) {
            elName.textContent = tour.recorrido.titulo + ' (Sin escenas)';
            elImgWrap.hidden = true;
            elPano.hidden = true;
            btnPlayFragment.hidden = true;
            elThumbs.innerHTML = '';
            return;
        }

        const scene = scenes[activeIndex];
        elName.textContent = `${scene.nombre} (${activeIndex + 1}/${scenes.length})`;

        const outgoing = connections.filter(c => c.escena_origen_id === scene.id);

        if (scene.tipo_visual === 'panorama' && typeof window.pannellum !== 'undefined') {
            elImgWrap.hidden = true;
            elPano.hidden = false;
            if (pannellumInstance && typeof pannellumInstance.destroy === 'function') {
                pannellumInstance.destroy();
            }
            pannellumInstance = window.pannellum.viewer('visorPanorama', {
                type: 'equirectangular',
                panorama: scene.imagen_url,
                autoLoad: true,
                hotSpots: outgoing.map(c => ({
                    pitch: c.posicion?.pitch ?? 0,
                    yaw: c.posicion?.yaw ?? 0,
                    type: 'scene',
                    text: c.etiqueta,
                    clickHandlerFunc: () => selectSceneById(c.escena_destino_id)
                }))
            });
        } else {
            elPano.hidden = true;
            elImgWrap.hidden = false;
            elImg.src = scene.imagen_url;
            elImg.alt = scene.nombre;
            elHotspots.innerHTML = outgoing.map(c => {
                const x = c.posicion?.x ?? 50;
                const y = c.posicion?.y ?? 50;
                return `<button type="button" class="visor-hotspot" data-goto-scene="${c.escena_destino_id}" style="left:${x}%;top:${y}%;">📍 ${esc(c.etiqueta)}</button>`;
            }).join('');
        }

        if (scene.video_fragmento_url) {
            const hasRange = scene.inicio_segundos !== null && scene.fin_segundos !== null;
            btnPlayFragment.textContent = hasRange
                ? `▶ Ver fragmento (${fmtTime(scene.inicio_segundos)} – ${fmtTime(scene.fin_segundos)})`
                : '▶ Ver video del espacio';
            btnPlayFragment.hidden = false;
        } else {
            btnPlayFragment.hidden = true;
        }

        elThumbs.innerHTML = scenes.map((s, idx) => `
            <button type="button" class="visor-thumb ${idx === activeIndex ? 'active' : ''}" data-thumb-idx="${idx}">
                <img src="${esc(s.imagen_url)}" alt="${esc(s.nombre)}">
                <span>${esc(s.nombre)}</span>
            </button>
        `).join('');
    }

    elHotspots?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-goto-scene]');
        if (btn) selectSceneById(btn.dataset.gotoScene);
    });

    elThumbs?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-thumb-idx]');
        if (btn) {
            activeIndex = Number(btn.dataset.thumbIdx);
            renderScene();
        }
    });

    document.getElementById('btnPrevScene')?.addEventListener('click', () => {
        if (!scenes.length) return;
        activeIndex = (activeIndex - 1 + scenes.length) % scenes.length;
        renderScene();
    });
    document.getElementById('btnNextScene')?.addEventListener('click', () => {
        if (!scenes.length) return;
        activeIndex = (activeIndex + 1) % scenes.length;
        renderScene();
    });

    document.addEventListener('keydown', (e) => {
        if (!overlay.hidden) {
            if (e.key === 'Escape') closeFragment();
            return;
        }
        if (e.key === 'ArrowRight') document.getElementById('btnNextScene')?.click();
        if (e.key === 'ArrowLeft') document.getElementById('btnPrevScene')?.click();
    });

    btnPlayFragment?.addEventListener('click', playSceneFragment);
    document.getElementById('btnReplayFragment')?.addEventListener('click', playSceneFragment);
    document.getElementById('btnCloseFragment')?.addEventListener('click', closeFragment);

    renderScene();
})();
