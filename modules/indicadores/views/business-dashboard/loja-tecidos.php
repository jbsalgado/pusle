<?php

use app\assets\ChartJsAsset;
use yii\helpers\Html;
use yii\helpers\Json;

ChartJsAsset::register($this);
$this->title = 'Dashboard Loja de Tecidos';


/**
 * Função auxiliar melhorada para determinar a cor do card com base no desempenho.
 * Suporta polaridades: MAIOR_MELHOR, MENOR_MELHOR e DENTRO_DA_FAIXA_MELHOR.
 */
function getPerformanceColor($valor, $atributos, $polaridade) {
    if (empty($atributos)) return 'performance-info';

    $valor = (float) $valor;
    $satisfatoriaInf = (float)($atributos['faixa_satisfatoria_inferior'] ?? null);
    $satisfatoriaSup = (float)($atributos['faixa_satisfatoria_superior'] ?? null);
    $alertaInf = (float)($atributos['faixa_alerta_inferior'] ?? null);
    $alertaSup = (float)($atributos['faixa_alerta_superior'] ?? null);
    $criticaInf = (float)($atributos['faixa_critica_inferior'] ?? null);
    $criticaSup = (float)($atributos['faixa_critica_superior'] ?? null);

    if ($polaridade === 'QUANTO_MAIOR_MELHOR') {
        if ($valor >= $satisfatoriaInf) return 'performance-success';
        if ($valor >= $alertaInf) return 'performance-warning';
        return 'performance-danger';
    }
    
    if ($polaridade === 'QUANTO_MENOR_MELHOR') {
        if ($valor <= $satisfatoriaSup) return 'performance-success';
        if ($valor <= $alertaSup) return 'performance-warning';
        return 'performance-danger';
    }

    if ($polaridade === 'DENTRO_DA_FAIXA_MELHOR') {
        if ($valor >= $satisfatoriaInf && $valor <= $satisfatoriaSup) return 'performance-success';
        if ($valor >= $alertaInf && $valor <= $alertaSup) return 'performance-warning';
        return 'performance-danger';
    }

    return 'performance-info';
}

// Registra CSS customizado
$this->registerCss(<<<CSS
/* ==========================================================================
   DASHBOARD APS - MOBILE FIRST DESIGN
   ========================================================================== */

/* Reset e configurações base */
* {
    box-sizing: border-box;
}

.dashboard-index {
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header principal */
.dashboard-header {
    text-align: center;
    margin-bottom: 2rem;
    color: white;
}

.dashboard-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.dashboard-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 300;
}

/* Cards de indicadores */
.metrics-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--card-accent-color);
    border-radius: 16px 16px 0 0;
}

.metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.metric-code {
    background: rgba(0,0,0,0.05);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #666;
}

.metric-status {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--card-accent-color);
    box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    line-height: 1;
}

.metric-unit {
    font-size: 0.9rem;
    font-weight: 400;
    color: #7f8c8d;
    margin-left: 0.25rem;
}

.metric-name {
    font-size: 0.9rem;
    color: #34495e;
    font-weight: 500;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.metric-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #7f8c8d;
}

.metric-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.metric-date::before {
    content: '📅';
    font-size: 0.7rem;
}

.metric-tooltip {
    cursor: help;
    border-bottom: 1px dotted #bdc3c7;
}

/* Cores dos cards baseadas no desempenho */
.performance-success {
    --card-accent-color: #27ae60;
}

.performance-warning {
    --card-accent-color: #f39c12;
}

.performance-danger {
    --card-accent-color: #e74c3c;
}

.performance-info {
    --card-accent-color: #3498db;
}

/* Seção de gráficos */
.charts-section {
    margin-top: 2rem;
}

.section-title {
    color: white;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    text-align: center;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.charts-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.chart-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.chart-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1rem;
    text-align: center;
}

.chart-container {
    position: relative;
    height: 200px;
    margin: 0 auto;
}

.chart-canvas {
    max-width: 100%;
    height: auto;
}

/* Estado vazio */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: white;
    background: rgba(255,255,255,0.1);
    border-radius: 16px;
    backdrop-filter: blur(10px);
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-message {
    font-size: 1.1rem;
    font-weight: 500;
}

/* Loading state */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 8px;
    height: 20px;
    margin: 0.5rem 0;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ==========================================================================
   RESPONSIVE DESIGN - MOBILE FIRST
   ========================================================================== */

/* Tablets (768px+) */
@media (min-width: 768px) {
    .dashboard-index {
        padding: 2rem;
    }
    
    .dashboard-title {
        font-size: 2rem;
    }
    
    .dashboard-subtitle {
        font-size: 1rem;
    }
    
    .metrics-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .charts-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-container {
        height: 250px;
    }
}

/* Desktop pequeno (992px+) */
@media (min-width: 992px) {
    .dashboard-index {
        padding: 3rem;
    }
    
    .metrics-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .metric-value {
        font-size: 3rem;
    }
}

/* Desktop grande (1200px+) */
@media (min-width: 1200px) {
    .metrics-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .charts-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .chart-container {
        height: 300px;
    }
}

/* Desktop muito grande (1400px+) */
@media (min-width: 1400px) {
    .metrics-grid {
        grid-template-columns: repeat(5, 1fr);
    }
    
    .charts-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .dashboard-index {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    }
    
    .metric-card, .chart-card {
        background: rgba(52, 73, 94, 0.9);
        color: white;
    }
    
    .metric-value {
        color: white;
    }
    
    .metric-name {
        color: #ecf0f1;
    }
    
    .chart-title {
        color: white;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .metric-card,
    .chart-card {
        transition: none;
    }
    
    .metric-card:hover,
    .chart-card:hover {
        transform: none;
    }
    
    .loading-skeleton {
        animation: none;
    }
}

/* Print styles */
@media print {
    .dashboard-index {
        background: white;
        padding: 1rem;
    }
    
    .metric-card,
    .chart-card {
        box-shadow: none;
        border: 1px solid #ddd;
        break-inside: avoid;
    }
    
    .dashboard-header {
        color: black;
    }
    
    .section-title {
        color: black;
    }
}
CSS
);

?>

<div class="dashboard-index">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard de Desempenho - Loja de Tecidos</h1>
        <p class="dashboard-subtitle">Principais KPIs do Varejo de Tecidos</p>
    </div>

    </div>