<?php
$idx = file_get_contents('index.html');

$styles = '
<style>
.filter-btn {
    background: transparent;
    border: 2px solid #e2e8f0;
    color: #64748b;
    padding: 10px 25px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}
.filter-btn:hover, .filter-btn.active {
    background: #c9a86a;
    border-color: #c9a86a;
    color: white;
}
.property-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #f1f5f9;
}
.property-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
.property-image {
    height: 240px;
    position: relative;
    overflow: hidden;
}
.property-image::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,.1), transparent 50%, rgba(0,0,0,.6));
    pointer-events: none;
}
.property-image video {
    transition: transform 0.6s ease;
}
.property-card:hover .property-image video {
    transform: scale(1.08);
}
.property-tag {
    position: absolute;
    top: 15px;
    left: 15px;
    background: #c9a86a;
    color: white;
    padding: 6px 14px;
    font-size: 11px;
    letter-spacing: 1px;
    text-transform: uppercase;
    font-weight: 800;
    border-radius: 20px;
    z-index: 2;
    box-shadow: 0 4px 10px rgba(201,168,106,0.3);
}
.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    cursor: pointer;
    z-index: 2;
    transition: 0.3s;
}
.favorite-btn:hover {
    background: #fff;
    color: #ff3b30;
    transform: scale(1.1);
}
.favorite-btn.active {
    color: #ff3b30;
}
.favorite-btn.active svg {
    fill: #ff3b30;
}
.legal-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: 0.3s;
}
.legal-summary {
    padding: 20px 25px;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.legal-summary::-webkit-details-marker {
    display: none;
}
.legal-summary::after {
    content: "+";
    color: #c9a86a;
    font-size: 24px;
    font-weight: normal;
    transition: 0.3s transform;
}
.legal-item[open] .legal-summary::after {
    content: "−";
    transform: rotate(180deg);
}
.legal-item[open] {
    border-color: #c9a86a;
    box-shadow: 0 5px 15px rgba(201,168,106,0.1);
}
.legal-content {
    padding: 0 25px 25px;
    color: #475569;
    font-size: 14px;
    line-height: 1.6;
}
</style>
';

// append styles to end of head
$headEnd = strpos($idx, '</head>');
$idx = substr_replace($idx, $styles, $headEnd, 0);
file_put_contents('index.html', $idx);
echo "Added styles\n";
?>
