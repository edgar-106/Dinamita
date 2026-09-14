import re

with open('index.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Extract parts
head = html[:html.find('</head>')] + '</head>\n<body>\n'
navbar_start = html.find('<!-- =========================================================\n     NAVBAR')
navbar_end = html.find('</nav>') + 6
navbar = html[navbar_start:navbar_end]

# Modify navbar to include Inicio link
navbar = navbar.replace('<li><a href=\"#propiedades\">Propiedades</a></li>', '<li><a href=\"index.html\">Inicio</a></li>\n        <li><a href=\"index.html#propiedades\">Propiedades</a></li>')
navbar = navbar.replace('<li><a href=\"#categorias\">Explorar</a></li>', '<li><a href=\"explorar.html\">Explorar</a></li>')

mobile_menu_start = html.find('<!-- =========================================================\n     MENÚ MÓVIL')
mobile_menu_end = html.find('</div>\n\n\n<!-- =========================================================\n     NAVBAR')
mobile_menu = html[mobile_menu_start:mobile_menu_end]

properties_start = html.find('<div class=\"property-grid\" id=\"propertyGrid\">')
properties_end = html.find('</section>', properties_start)
properties_grid = html[properties_start:properties_end]

# Remove the display:none and absolute positioning from the property-grid if they are hardcoded
# Actually they are on the section, not the grid

# Extract modals and footer
footer_start = html.find('<!-- =========================================================\n     FOOTER')
footer = html[footer_start:]
footer = footer.replace('href="#propiedades"', 'href="index.html#propiedades"')
footer = footer.replace('href="#categorias"', 'href="explorar.html"')

explorar_content = f'''
<!-- =========================================================
     EXPLORAR VISTA
========================================================= -->
<style>
.explorar-header {{
    padding: 140px 5% 40px;
    background: #141414;
    color: white;
    text-align: center;
}}
.explorar-header h1 {{
    font-family: Georgia, serif;
    font-size: clamp(40px, 6vw, 65px);
    margin-bottom: 15px;
}}
.explorar-header p {{
    font-size: 16px;
    color: #aeb6c4;
    max-width: 600px;
    margin: 0 auto 40px;
}}
.category-tabs {{
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}}
.tab-btn {{
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 12px 30px;
    border-radius: 30px;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: 0.3s;
}}
.tab-btn:hover, .tab-btn.active {{
    background: #c9a86a;
    border-color: #c9a86a;
    color: white;
}}
.properties-container {{
    padding: 40px 5% 80px;
    background: #f5f7fb;
}}
</style>

<div class=\"mobile-menu\" id=\"mobileMenu\" role=\"dialog\" aria-modal=\"true\" aria-label=\"Menú de navegación\">
    <button
        class=\"mobile-menu-close\"
        id=\"mobileMenuClose\"
        aria-label=\"Cerrar menú\"
    >×</button>
    <a href=\"index.html\">Inicio</a>
    <a href=\"index.html#propiedades\">Propiedades</a>
    <a href=\"explorar.html\">Explorar</a>
</div>

{navbar}

<section class=\"explorar-header\">
    <h1 id=\"catTitle\">Casas</h1>
    <p id=\"catDesc\">Encuentra la casa de tus sueños, ideal para ti y tu familia.</p>
    
    <div class=\"category-tabs\">
        <button class=\"tab-btn active\" id=\"tab-casa\" onclick=\"switchCategory('casa')\">Casas</button>
        <button class=\"tab-btn\" id=\"tab-departamento\" onclick=\"switchCategory('departamento')\">Departamentos</button>
        <button class=\"tab-btn\" id=\"tab-terreno\" onclick=\"switchCategory('terreno')\">Terrenos</button>
    </div>
</section>

<section class=\"properties-container\">
    {properties_grid}
</section>

<script>
    const descriptions = {{
        casa: {{ title: 'Casas', desc: 'Encuentra la casa de tus sueños, ideal para ti y tu familia.' }},
        departamento: {{ title: 'Departamentos', desc: 'Espacios modernos y céntricos diseñados para tu estilo de vida.' }},
        terreno: {{ title: 'Terrenos', desc: 'Invierte en tu futuro con las mejores opciones de terrenos disponibles.' }}
    }};

    function switchCategory(type) {{
        // Update tabs
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tab-' + type).classList.add('active');

        // Update header
        document.getElementById('catTitle').innerText = descriptions[type].title;
        document.getElementById('catDesc').innerText = descriptions[type].desc;

        // Filter grid
        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => {{
            if (card.dataset.type === type || type === 'todos') {{
                card.style.display = '';
            }} else {{
                card.style.display = 'none';
            }}
        }});
    }}

    window.addEventListener('DOMContentLoaded', () => {{
        const urlParams = new URLSearchParams(window.location.search);
        let cat = urlParams.get('categoria') || 'casa';
        if (!['casa', 'departamento', 'terreno'].includes(cat)) cat = 'casa';
        switchCategory(cat);
    }});
</script>

{footer}
'''

with open('explorar.html', 'w', encoding='utf-8') as f:
    f.write(head + explorar_content)
