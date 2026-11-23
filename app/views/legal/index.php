<!-- Página legal independiente para que Vender y Explorar no redirijan a Inicio. -->
<style>
.legal-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:.3s; }
.legal-summary { padding:20px 25px; font-size:18px; font-weight:600; color:#1e293b; cursor:pointer; list-style:none; display:flex; justify-content:space-between; align-items:center; }
.legal-summary::-webkit-details-marker { display:none; }
.legal-summary::after { content:"+"; color:#c9a86a; font-size:24px; font-weight:normal; }
.legal-item[open] { border-color:#c9a86a; box-shadow:0 5px 15px rgba(201,168,106,.1); }
.legal-item[open] .legal-summary::after { content:"−"; }
.legal-content { padding:0 25px 25px; color:#475569; font-size:15px; line-height:1.6; }
</style>
<section class="section legal" id="legal" style="min-height:100vh;background:#fff;padding:145px 5% 90px;">
    <div style="max-width:800px;margin:0 auto;">
        <div style="text-align:center;margin-bottom:50px;">
            <div style="color:#c9a86a;font-weight:bold;letter-spacing:2px;font-size:12px;text-transform:uppercase;margin-bottom:10px;">Certeza Jurídica</div>
            <h1 style="font-family:Georgia,serif;font-size:clamp(36px,5vw,52px);color:#172033;margin:0;">Términos y Legalidad</h1>
            <p style="color:#64748b;font-size:16px;margin-top:15px;line-height:1.6;">Información clara, resumida y directa para tu tranquilidad en cada operación.</p>
        </div>

        <div style="display:flex;flex-direction:column;gap:15px;">
            <details class="legal-item">
                <summary class="legal-summary">Promesa de Compraventa</summary>
                <div class="legal-content"><p>Contrato preparatorio que establece condiciones, precio y plazos antes de la escrituración formal.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Arrendamiento Seguro</summary>
                <div class="legal-content"><p>Para renta se pueden solicitar depósito en garantía, fiador con propiedad o póliza jurídica, según las condiciones de cada inmueble.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Auditoría Registral</summary>
                <div class="legal-content"><p>Revisamos la situación del inmueble ante el Registro Público para identificar gravámenes y dar mayor certeza a la operación.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Protección de Datos</summary>
                <div class="legal-content"><p>Los datos compartidos se utilizan exclusivamente para atención, seguimiento y formalización de operaciones inmobiliarias.</p></div>
            </details>
        </div>

        <div style="margin-top:36px;text-align:center;">
            <a class="btn-dark" href="<?= BASE_URL ?>" style="display:inline-block;text-decoration:none;">Volver a explorar propiedades</a>
        </div>
    </div>
</section>
