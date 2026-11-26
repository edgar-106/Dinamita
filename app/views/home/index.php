<!-- =========================================================
     SECCIÓN EXPLORAR / ADQUIRIR
========================================================= -->

<section class="section" id="adquirir">
    <div class="category-chooser" id="categoryChooser">
        <div class="chooser-header">
            <div>
                <div class="chooser-kicker">Explorar</div>
                <h2>¿Qué estás buscando?</h2>
            </div>
            <p>Selecciona el tipo de inmueble que necesitas para abrir la vista completa del catálogo y filtrar por operación, ubicación, precio o favoritos.</p>
        </div>
        <div class="chooser-grid">
            <a class="chooser-card" href="<?= BASE_URL ?>explorar?tipo=casa" style="background-image:url('https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Casas</strong><small>Compra o renta</small></span>
            </a>
            <a class="chooser-card" href="<?= BASE_URL ?>explorar?tipo=departamento" style="background-image:url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Departamentos</strong><small>Espacios modernos</small></span>
            </a>
            <a class="chooser-card" href="<?= BASE_URL ?>explorar?tipo=terreno" style="background-image:url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85')">
                <span><strong>Terrenos</strong><small>Invierte en tu futuro</small></span>
            </a>
        </div>
    </div>
</section>

<!-- =========================================================
     ASESOR
========================================================= -->

<section class="section advisor-section" id="asesor">
    <div class="advisor-wrap">
        <div class="advisor-profile">
            <img
                class="advisor-photo"
                src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=300&q=80"
                alt="Foto de Lic. Sofía Valenzuela, asesora inmobiliaria"
                loading="lazy"
            >
            <div class="advisor-kicker">● ASESORA INMOBILIARIA</div>
            <h2 class="advisor-name">Lic. Sofía Valenzuela</h2>
            <div class="advisor-role">Especialista en orientación inmobiliaria</div>
            <p class="advisor-quote">"Te acompaño paso a paso en la revisión de información, validación de antecedentes y orientación del proceso inmobiliario."</p>
            <div class="advisor-actions">
                <button type="button" class="btn-advisor-dark" onclick="toggleChat()" aria-label="Abrir chat en vivo">▣ Chat en Vivo</button>
                <button type="button" class="btn-advisor-green" onclick="openInterestModal(null)" aria-label="Contactar asesor">▣ Contactar asesor</button>
            </div>
        </div>
        <div class="advisor-form">
            <h3>¿Deseas atención personalizada o agendar un recorrido guiado?</h3>
            <div class="small">Déjanos tus datos y te orientaremos sobre propiedades, requisitos y citas.</div>
            <form onsubmit="requestAdvisor(event)">
                <div class="advisor-form-grid">
                    <div>
                        <label for="advisorName" class="sr-only">Nombre completo</label>
                        <input id="advisorName" name="nombre" required placeholder="Nombre completo">
                    </div>
                    <div>
                        <label for="advisorPhone" class="sr-only">Teléfono o WhatsApp</label>
                        <input id="advisorPhone" name="telefono" type="tel" required placeholder="Teléfono / WhatsApp">
                    </div>
                    <div>
                        <label for="advisorTime" class="sr-only">Horario preferido</label>
                        <select id="advisorTime" name="horario_preferido">
                            <option>Inmediato (9:00 - 19:00)</option>
                            <option>Por la mañana</option>
                            <option>Por la tarde</option>
                        </select>
                    </div>
                </div>
                <div class="advisor-security">🔒 Tus datos se utilizarán para comunicación, seguimiento y citas relacionadas con el servicio.</div>
                <button class="advisor-submit" type="submit">SOLICITAR ASESORÍA GRATUITA</button>
            </form>
        </div>
    </div>
</section>

<!-- =========================================================
     INFORMACIÓN LEGAL
========================================================= -->

<section class="section legal" id="legal">
    <div class="legal-container">
        <div class="legal-header-block">
            <div class="legal-kicker">Certeza Jurídica</div>
            <h2>Términos y Legalidad</h2>
            <p>Información clara, resumida y directa para tu tranquilidad en cada operación.</p>
        </div>
        <div class="legal-accordion">
            <details class="legal-item">
                <summary class="legal-summary">Promesa de Compraventa</summary>
                <div class="legal-content"><p>Contrato preparatorio que asegura condiciones, precio y plazos antes de la escrituración formal ante notario.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Arrendamiento Seguro</summary>
                <div class="legal-content"><p>Requisitos transparentes de contratación: depósito en garantía, fiador con propiedad libre de gravamen o póliza jurídica.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Auditoría Registral (RPP)</summary>
                <div class="legal-content"><p>Revisión exhaustiva ante el Registro Público de la Propiedad para garantizar que cada inmueble esté libre de gravámenes.</p></div>
            </details>
            <details class="legal-item">
                <summary class="legal-summary">Protección de Datos y Sesiones</summary>
                <div class="legal-content"><p>Tu información se transmite de forma protegida y se utiliza únicamente para dar seguimiento a tus citas o solicitudes de contacto.</p></div>
            </details>
        </div>
    </div>
</section>
