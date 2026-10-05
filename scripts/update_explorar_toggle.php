<?php
$html = file_get_contents('explorar.html');

// Add the operation-tabs HTML
$search = '<div class="category-tabs">';
$replace = '<div class="operation-tabs">
        <button class="op-btn active" id="op-comprar" onclick="switchOperation(\'comprar\')">Comprar</button>
        <button class="op-btn" id="op-rentar" onclick="switchOperation(\'rentar\')">Rentar</button>
    </div>
    <div class="category-tabs">';
$html = str_replace($search, $replace, $html);

// Add CSS for op-btn
$cssSearch = '.category-tabs {';
$cssReplace = '.operation-tabs {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 25px;
}
.op-btn {
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 10px 40px;
    border-radius: 30px;
    font-size: 16px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: 0.3s;
    cursor: pointer;
}
.op-btn:hover, .op-btn.active {
    background: #c9a86a;
    border-color: #c9a86a;
    color: white;
    box-shadow: 0 0 15px rgba(201,168,106,0.4);
}
.category-tabs {';
$html = str_replace($cssSearch, $cssReplace, $html);

// Modify JS to handle both category and operation filtering
// Currently it's:
/*
    function switchCategory(type) {
        // update active tab
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // update titles
        const titles = {
            'casa': 'Casas',
            'departamento': 'Departamentos',
            'terreno': 'Terrenos'
        };
        const descs = {
            'casa': 'Encuentra la casa de tus sueños, ideal para ti y tu familia.',
            'departamento': 'Espacios modernos y funcionales en las mejores ubicaciones.',
            'terreno': 'Invierte en tu futuro con terrenos residenciales y comerciales.'
        };
        document.getElementById('catTitle').innerText = titles[type];
        document.getElementById('catDesc').innerText = descs[type];

        // filter cards
        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => {
            if(card.dataset.type === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // update URL
        window.history.pushState({}, '', '?categoria=' + type);
    }
*/
// Let's replace the script block with a new one that tracks currentCategory and currentOperation.

$scriptSearch = "function switchCategory(type) {";
$scriptReplace = "let currentCategory = new URLSearchParams(window.location.search).get('categoria') || 'casa';
    let currentOperation = 'comprar'; // default

    function filterCards() {
        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => {
            if (card.dataset.type === currentCategory && card.dataset.operation === currentOperation) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function switchOperation(op) {
        currentOperation = op;
        
        // update buttons
        document.querySelectorAll('.op-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('op-' + op).classList.add('active');
        
        filterCards();
    }

    function switchCategory(type) {
        currentCategory = type;
        ";

$html = str_replace($scriptSearch, $scriptReplace, $html);

// Now update the original filter cards logic in switchCategory to just call filterCards()
$filterSearch = "// filter cards
        const cards = document.querySelectorAll('.property-card');
        cards.forEach(card => {
            if(card.dataset.type === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });";
$filterReplace = "// filter cards
        filterCards();";
$html = str_replace($filterSearch, $filterReplace, $html);

// Also need to initialize the correct operation filter when the page loads, we can just call filterCards() at the end.
$initSearch = "if(cat) {";
$initReplace = "filterCards();\n    if(cat) {";
$html = str_replace($initSearch, $initReplace, $html);


file_put_contents('explorar.html', $html);
echo "explorar.html updated\n";
?>
