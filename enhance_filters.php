<?php
$html = file_get_contents('index.html');

$oldFilters = '<!-- Filtros -->
    <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; margin-bottom: 40px;">
        <div class="operation-tabs" style="display: flex; gap: 10px;">
            <button class="filter-btn active" id="op-comprar" onclick="filterProps(\'op\', \'comprar\')">Adquirir</button>
            <button class="filter-btn" id="op-rentar" onclick="filterProps(\'op\', \'rentar\')">Rentar</button>
        </div>
        <div class="type-tabs" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            <button class="filter-btn active" id="type-all" onclick="filterProps(\'type\', \'all\')">Todos</button>
            <button class="filter-btn" id="type-casa" onclick="filterProps(\'type\', \'casa\')">Casas</button>
            <button class="filter-btn" id="type-departamento" onclick="filterProps(\'type\', \'departamento\')">Departamentos</button>
            <button class="filter-btn" id="type-terreno" onclick="filterProps(\'type\', \'terreno\')">Terrenos</button>
        </div>
    </div>';

$newFilters = '<!-- Filtros -->
    <div style="display: flex; flex-direction: column; align-items: center; gap: 15px; margin-bottom: 50px;">
        <div class="operation-tabs" style="display: inline-flex; background: #fff; padding: 5px; border-radius: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;">
            <button class="filter-btn active" id="op-comprar" onclick="filterProps(\'op\', \'comprar\')" style="border: none; padding: 10px 30px;">Adquirir</button>
            <button class="filter-btn" id="op-rentar" onclick="filterProps(\'op\', \'rentar\')" style="border: none; padding: 10px 30px;">Rentar</button>
        </div>
        <div class="type-tabs" style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-top: 10px;">
            <button class="filter-btn active" id="type-all" onclick="filterProps(\'type\', \'all\')">Todos</button>
            <button class="filter-btn" id="type-casa" onclick="filterProps(\'type\', \'casa\')">Casas</button>
            <button class="filter-btn" id="type-departamento" onclick="filterProps(\'type\', \'departamento\')">Departamentos</button>
            <button class="filter-btn" id="type-terreno" onclick="filterProps(\'type\', \'terreno\')">Terrenos</button>
        </div>
    </div>';

$html = str_replace($oldFilters, $newFilters, $html);
file_put_contents('index.html', $html);
echo "Enhanced filters UI\n";
?>
