<?php
$html = file_get_contents('index.html');

$head = substr($html, 0, strpos($html, '</head>')) . '</head>' . "\n<body>\n";

$navStart = strpos($html, '<!-- =========================================================
     NAVBAR');
$navEnd = strpos($html, '</nav>', $navStart) + 6;
$navbar = substr($html, $navStart, $navEnd - $navStart);

$navbar = str_replace('<li><a href="#propiedades">Propiedades</a></li>', '<li><a href="index.html">Inicio</a></li>' . "\n" . '        <li><a href="index.html#propiedades">Propiedades</a></li>', $navbar);
$navbar = str_replace('<li><a href="#categorias">Explorar</a></li>', '<li><a href="explorar.html">Explorar</a></li>', $navbar);

$propStart = strpos($html, '<div class="property-grid" id="propertyGrid">');
$propEnd = strpos($html, '</section>', $propStart);
$propGrid = substr($html, $propStart, $propEnd - $propStart);

$footerStart = strpos($html, '<!-- =========================================================
     FOOTER');
$footer = substr($html, $footerStart);
$footer = str_replace('href="#propiedades"', 'href="index.html#propiedades"', $footer);
$footer = str_replace('href="#categorias"', 'href="explorar.html"', $footer);

$explorar_content = <<<HTML
<style>
.explorar-header {
    padding: 140px 5% 40px;
    background: #141414;
    color: white;
    text-align: center;
}
.explorar-header h1 {
    font-family: Georgia, serif;
    font-size: clamp(40px, 6vw, 65px);
    margin-bottom: 15px;
}
.explorar-header p {
    font-size: 16px;
    color: #aeb6c4;
    max-width: 600px;
    margin: 0 auto 40px;
}
.category-tabs {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.tab-btn {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 12px 30px;
    border-radius: 30px;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: 0.3s;
    cursor: pointer;
}
.tab-btn:hover, .tab-btn.active {
    background: #c9a86a;
    border-color: #c9a86a;
    color: white;
}
.properties-container {
    padding: 40px 5% 80px;
    background: #f5f7fb;
}
</style>

$navbar

<section class="explorar-header">
    <h1 id="catTitle">Casas</h1>
    <p id="catDesc">Encuentra la casa de tus sueños, ideal para ti y tu familia.</p>
    
    <div class="category-tabs">
        <button class="tab-btn active" id="tab-casa" onclick="switchCategory('casa')">Casas</button>
        <button class="tab-btn" id="tab-departamento" onclick="switchCategory('departamento')">Departamentos</button>
        <button class="tab-btn" id="tab-terreno" onclick="switchCategory('terreno')">Terrenos</button>
    </div>
</section>

<section class="properties-container">
    $propGrid
</section>

<script>
    const descriptions = {
        casa: { title: 'Casas', desc: 'Encuentra la casa de tus sueños, ideal para ti y tu familia.' },
        departamento: { title: 'Departamentos', desc: 'Espacios modernos y céntricos diseñados para tu estilo de vida.' },
        terreno: { title: 'Terrenos', desc: 'Invierte en tu futuro con las mejores opciones de terrenos disponibles.' }
    };

    function switchCategory(type) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tab-' + type).classList.add('active');

        document.getElementById('catTitle').innerText = descriptions[type].title;
        document.getElementById('catDesc').innerText = descriptions[type].desc;

        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => {
            if (card.dataset.type === type || type === 'todos') {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update URL to reflect current category without reloading
        const url = new URL(window.location);
        url.searchParams.set('categoria', type);
        window.history.pushState({}, '', url);
    }

    window.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        let cat = urlParams.get('categoria') || 'casa';
        if (!['casa', 'departamento', 'terreno'].includes(cat)) cat = 'casa';
        switchCategory(cat);
        
        // We need to override some of app.js behavior because it hides properties by default
        const propSection = document.querySelector('.properties-container');
        if (propSection) propSection.style.display = 'block';
    });
</script>

$footer
HTML;

file_put_contents('explorar.html', $head . $explorar_content);
echo "explorar.html created successfully.\n";
?>
