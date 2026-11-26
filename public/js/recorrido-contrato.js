/**
 * Contrato de integración compartido entre:
 * - Módulo de Recorridos por Video (este módulo)
 * - Módulo de Recorridos por Fotografías / Panorámicas (compañero)
 *
 * Ambos módulos producen y consumen escenas y conexiones bajo esta estructura común.
 */
(function (global) {
    'use strict';

    const VERSION = 'infonatec-tour-v1';

    function toNullableNumber(val) {
        if (val === null || val === undefined || val === '') return null;
        const n = Number(val);
        return Number.isFinite(n) ? n : null;
    }

    function validatePosition(pos, visualType) {
        if (!pos || typeof pos !== 'object') {
            return { valid: false, message: 'La posición debe ser un objeto con sistema y coordenadas.' };
        }
        const sistema = String(pos.sistema || (visualType === 'panorama' ? 'panorama_yaw_pitch' : 'plano_xy'));
        if (sistema === 'plano_xy') {
            const x = Number(pos.x);
            const y = Number(pos.y);
            if (!Number.isFinite(x) || !Number.isFinite(y) || x < 0 || x > 100 || y < 0 || y > 100) {
                return { valid: false, message: 'En plano_xy las coordenadas x y y deben estar entre 0 y 100 (%).' };
            }
            return { valid: true, value: { sistema: 'plano_xy', x: Math.round(x * 100) / 100, y: Math.round(y * 100) / 100 } };
        }
        if (sistema === 'panorama_yaw_pitch') {
            const yaw = Number(pos.yaw);
            const pitch = Number(pos.pitch);
            if (!Number.isFinite(yaw) || !Number.isFinite(pitch) || yaw < -180 || yaw > 180 || pitch < -90 || pitch > 90) {
                return { valid: false, message: 'En panorama_yaw_pitch yaw debe estar entre -180..180 y pitch entre -90..90.' };
            }
            return { valid: true, value: { sistema: 'panorama_yaw_pitch', yaw: Math.round(yaw * 100) / 100, pitch: Math.round(pitch * 100) / 100 } };
        }
        return { valid: false, message: 'Sistema de coordenadas no soportado: ' + sistema };
    }

    function normalizeScene(raw) {
        const s = raw || {};
        const tipo = s.tipo_visual === 'panorama' ? 'panorama' : 'imagen';
        return {
            id: Number(s.id || 0),
            id_recorrido: Number(s.id_recorrido || 0),
            nombre: String(s.nombre || 'Escena'),
            tipo_visual: tipo,
            imagen_url: String(s.imagen_url || ''),
            video_id: s.video_id !== null && s.video_id !== undefined ? Number(s.video_id) : null,
            momento_segundos: toNullableNumber(s.momento_segundos),
            inicio_segundos: toNullableNumber(s.inicio_segundos),
            fin_segundos: toNullableNumber(s.fin_segundos),
            orden: Number(s.orden || 1),
            origen_extraccion: String(s.origen_extraccion || 'manual'),
            video_fragmento_url: s.video_fragmento_url ? String(s.video_fragmento_url) : null
        };
    }

    function normalizeConnection(raw) {
        const c = raw || {};
        const checked = validatePosition(c.posicion, 'imagen');
        return {
            id: Number(c.id || 0),
            escena_origen_id: Number(c.escena_origen_id || 0),
            escena_destino_id: Number(c.escena_destino_id || 0),
            etiqueta: String(c.etiqueta || 'Ir a escena'),
            posicion: checked.valid ? checked.value : { sistema: 'plano_xy', x: 50, y: 50 }
        };
    }

    function normalizeTour(payload) {
        const data = payload || {};
        const escenas = Array.isArray(data.escenas) ? data.escenas.map(normalizeScene) : [];
        escenas.sort((a, b) => (a.orden - b.orden) || (a.id - b.id));
        const conexiones = Array.isArray(data.conexiones) ? data.conexiones.map(normalizeConnection) : [];
        const recorrido = data.recorrido || {};

        return {
            contract: VERSION,
            propiedad: data.propiedad || null,
            recorrido: {
                id: Number(recorrido.id || 0),
                id_propiedad: Number(recorrido.id_propiedad || 0),
                titulo: String(recorrido.titulo || 'Recorrido interactivo'),
                descripcion: String(recorrido.descripcion || ''),
                estado: String(recorrido.estado || 'BORRADOR'),
                escena_inicial_id: recorrido.escena_inicial_id ? Number(recorrido.escena_inicial_id) : (escenas[0]?.id || null)
            },
            escenas: escenas,
            conexiones: conexiones
        };
    }

    function getSamplePayload() {
        return normalizeTour({
            propiedad: { id: 99, titulo: 'Casa Ejemplo Integración', ubicacion: 'Oaxaca, Oax.' },
            recorrido: { id: 1, id_propiedad: 99, titulo: 'Recorrido Mixto Video + Fotografías', estado: 'PUBLICADO', escena_inicial_id: 101 },
            escenas: [
                {
                    id: 101,
                    id_recorrido: 1,
                    nombre: 'Sala (desde video)',
                    tipo_visual: 'imagen',
                    imagen_url: 'uploads/recorridos/escenas/ejemplo_sala.jpg',
                    video_id: 10,
                    momento_segundos: 3.5,
                    inicio_segundos: 1.0,
                    fin_segundos: 8.5,
                    orden: 1,
                    origen_extraccion: 'ffmpeg',
                    video_fragmento_url: '/api/recorridos/escenas/101/video'
                },
                {
                    id: 102,
                    id_recorrido: 1,
                    nombre: 'Cocina (desde fotografía panorámica)',
                    tipo_visual: 'panorama',
                    imagen_url: 'tour/panoramas/comedor.jpg',
                    video_id: null,
                    inicio_segundos: null,
                    fin_segundos: null,
                    orden: 2,
                    origen_extraccion: 'foto',
                    video_fragmento_url: null
                }
            ],
            conexiones: [
                {
                    id: 1,
                    escena_origen_id: 101,
                    escena_destino_id: 102,
                    etiqueta: 'Pasar a Cocina',
                    posicion: { sistema: 'plano_xy', x: 64.5, y: 52.0 }
                },
                {
                    id: 2,
                    escena_origen_id: 102,
                    escena_destino_id: 101,
                    etiqueta: 'Regresar a Sala',
                    posicion: { sistema: 'panorama_yaw_pitch', yaw: -110.0, pitch: -4.0 }
                }
            ]
        });
    }

    global.InfonatecTourContract = {
        VERSION: VERSION,
        normalizeTour: normalizeTour,
        normalizeScene: normalizeScene,
        normalizeConnection: normalizeConnection,
        validatePosition: validatePosition,
        getSamplePayload: getSamplePayload
    };
})(window);
