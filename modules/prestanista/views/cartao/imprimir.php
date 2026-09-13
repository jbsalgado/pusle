<?php
/** @var yii\web\View $this */
/** @var app\modules\vendas\models\Venda $cartao */
/** @var string $formato */

use yii\helpers\Html;
use yii\helpers\Url;

$usuario = Yii::$app->user->identity;
$lojaNome = $usuario->nome_loja ?? $usuario->nome ?? 'CREDIÁRIOS PULSE';
$cliente = $cartao->cliente;
$itens = $cartao->itens;
$parcelas = $cartao->parcelas;

$totalPago = 0;
foreach ($parcelas as $p) {
    if ($p->status_parcela_codigo === 'PAGA') {
        $totalPago += (float)($p->valor_pago ?: $p->valor_parcela);
    }
}
$saldoDevedor = max(0, (float)$cartao->valor_total - $totalPago);
$slugCliente = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cliente->nome ?? 'cliente');
$nomeArquivo = 'cartao_' . str_pad($cartao->id, 5, '0', STR_PAD_LEFT) . '_' . $slugCliente;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartão #<?= str_pad($cartao->id, 5, '0', STR_PAD_LEFT) ?> - <?= Html::encode($cliente->nome ?? 'Cliente') ?></title>
    
    <!-- Bibliotecas para Geração de Imagem e PDF via Client-side -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; }
        
        body { 
            background: #e2e8f0; 
            color: #000; 
            padding: 16px 8px; 
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* Barra de Ações Superior (Não sai na impressão) */
        .no-print-bar {
            width: 100%;
            max-width: 105mm;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: bold;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }
        .btn-action:active { transform: scale(0.97); }
        .btn-back { background: #475569; color: #fff; }
        .btn-back:hover { background: #334155; }
        .btn-print { background: #0f172a; color: #fff; }
        .btn-print:hover { background: #1e293b; }
        .btn-img { background: #0284c7; color: #fff; }
        .btn-img:hover { background: #0369a1; }
        .btn-pdf { background: #dc2626; color: #fff; }
        .btn-pdf:hover { background: #b91c1c; }

        /* Status Toast */
        #statusToast {
            display: none;
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            color: #fff;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-family: sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        /* CARTÃO FÍSICO COM DIMENSÕES DE 1/4 DE FOLHA A4 (105mm x 148.5mm) */
        .cartao-container {
            width: 105mm;
            max-width: 105mm;
            min-height: 148.5mm;
            background: #fff;
            border: 2px solid #000;
            padding: 5mm;
            font-size: 8px;
            line-height: 1.2;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 3px; margin-bottom: 3px; }
        .header .motto { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h1 { font-size: 13px; font-weight: 900; text-transform: uppercase; line-height: 1.1; margin: 1px 0; }
        .header .segmento { font-size: 7.5px; text-transform: uppercase; font-weight: bold; }
        .meta-grid { display: flex; justify-content: space-between; border-top: 1px solid #000; padding-top: 2px; margin-top: 2px; font-size: 7.5px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
        th, td { border: 1px solid #000; padding: 1.5px 2px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; }

        .cliente-box { border-top: 1.5px solid #000; border-bottom: 1.5px solid #000; padding: 3px 0; margin: 3px 0; font-size: 7.5px; line-height: 1.25; }
        .frequencia-grid { font-size: 7px; font-weight: bold; margin-top: 2px; }

        .grade-tabela { width: 100%; border-collapse: collapse; text-align: center; }
        .grade-tabela th { background: #e2e8f0; font-size: 7px; padding: 2px 1px; font-weight: 900; }
        .grade-tabela td { font-size: 7px; padding: 1.5px 1px; height: 13.5px; font-family: 'Courier New', Courier, monospace; }
        .col-prest { font-weight: bold; }
        .col-saldo { font-weight: 900; }

        .footer { text-align: center; font-size: 6.5px; border-top: 1.5px solid #000; padding-top: 2px; margin-top: 3px; line-height: 1.2; }

        /* CONFIGURAÇÃO DE IMPRESSÃO EXATA 1/4 A4 (A6) */
        @page {
            size: 105mm 148.5mm; /* 1/4 da folha A4 */
            margin: 2.5mm;
        }

        @media print {
            body { 
                background: #fff !important; 
                padding: 0 !important; 
                margin: 0 !important; 
            }
            .no-print-bar, #statusToast { 
                display: none !important; 
            }
            .cartao-container {
                box-shadow: none !important;
                border: 1.5px solid #000 !important;
                margin: 0 auto !important;
                width: 100mm !important;
                max-width: 100mm !important;
                min-height: auto !important;
                padding: 3mm !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div id="statusToast">Processando...</div>

<!-- Barra Superior de Comandos -->
<div class="no-print-bar">
    <a href="<?= Url::to(['view', 'id' => $cartao->id]) ?>" class="btn-action btn-back">
        ← Voltar
    </a>
    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn-action btn-print" title="Imprimir (1/4 folha A4)">
            🖨️ Imprimir
        </button>
        <button onclick="baixarImagem()" id="btnBaixarImg" class="btn-action btn-img" title="Baixar imagem em PNG">
            🖼️ Imagem
        </button>
        <button onclick="baixarPDF()" id="btnBaixarPdf" class="btn-action btn-pdf" title="Baixar arquivo PDF">
            📄 PDF
        </button>
    </div>
</div>

<!-- O Cartão Físico com Dimensões 1/4 A4 (105mm x 148.5mm) -->
<div id="cartao-imprimir" class="cartao-container">
    <div>
        <!-- Cabeçalho -->
        <div class="header">
            <p class="motto">Nosso prazer é atendê-lo bem</p>
            <h1><?= Html::encode($lojaNome) ?></h1>
            <p class="segmento">CREDIÁRIOS & UTILIDADES</p>
            <div class="meta-grid">
                <span>DATA: <?= date('d/m/Y', strtotime($cartao->data_venda)) ?></span>
                <span>FLS: 01</span>
                <span>Nº: #<?= str_pad($cartao->id, 5, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Tabela de Objetos / Mercadorias -->
        <table>
            <thead>
                <tr>
                    <th>OBJETOS</th>
                    <th style="width: 70px; text-align: right;">VALOR R$</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <?php 
                        $qtd = (float)$item->quantidade;
                        $qtdFormatada = (floor($qtd) == $qtd) ? number_format($qtd, 0, ',', '.') : rtrim(rtrim(number_format($qtd, 2, ',', '.'), '0'), ',');
                    ?>
                    <tr>
                        <td><?= Html::encode($item->produto->nome ?? 'Mercadoria') ?> (<?= $qtdFormatada ?>x)</td>
                        <td style="text-align: right; font-weight: bold;">R$ <?= number_format($item->valor_total_item, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php for ($i = count($itens); $i < 2; $i++): ?>
                    <tr>
                        <td style="color: #94a3b8;">___________________________</td>
                        <td style="text-align: right; color: #94a3b8;">R$ ________</td>
                    </tr>
                <?php endfor; ?>
                <tr style="font-weight: 900; background: #f8fafc;">
                    <td>TOTAL DO CARTÃO:</td>
                    <td style="text-align: right;">R$ <?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Dados do Cliente -->
        <div class="cliente-box">
            <div><strong>Sr.(a):</strong> <?= Html::encode($cliente->nome ?? '—') ?> Nº <?= Html::encode($cliente->numero ?? 'S/N') ?></div>
            <div><strong>Rua:</strong> <?= Html::encode($cliente->endereco ?? '—') ?></div>
            <div><strong>Bairro:</strong> <?= Html::encode($cliente->bairro ?? '—') ?> - <?= Html::encode($cliente->cidade ?? '—') ?></div>
            <div><strong>Vendedor:</strong> <?= Html::encode($cartao->vendedor->nome ?? 'Ambulante') ?> | <strong>Tel:</strong> <?= Html::encode($cliente->telefone ?? '—') ?></div>
            <?php $freqCartao = $cartao->getFrequenciaPrestanista(); ?>
            <div class="frequencia-grid">
                [<?= $freqCartao === 'DIÁRIA' ? 'X' : ' ' ?>] DIÁRIA &nbsp;&nbsp;
                [<?= $freqCartao === 'SEMANAL' ? 'X' : ' ' ?>] SEMANAL &nbsp;&nbsp;
                [<?= $freqCartao === 'QUINZENAL' ? 'X' : ' ' ?>] QUINZENAL &nbsp;&nbsp;
                [<?= $freqCartao === 'MENSAL' ? 'X' : ' ' ?>] MENSAL
            </div>
        </div>

        <!-- Grade de Prestações com 7 Colunas -->
        <table class="grade-tabela">
            <thead>
                <tr>
                    <th style="width: 14%;">DATA PREST.</th>
                    <th style="width: 14%;">VL. PREST.</th>
                    <th style="width: 15%;">VL. COMPRA</th>
                    <th style="width: 14%;">DATA PAG.</th>
                    <th style="width: 14%;">VL. RECEB.</th>
                    <th style="width: 14%;">TIPO</th>
                    <th style="width: 15%;">SALDO</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $saldo = (float)$cartao->valor_total;
                    foreach ($parcelas as $p): 
                        $isPaga = ($p->status_parcela_codigo === 'PAGA');
                        if ($isPaga) {
                            $valorBaixado = (float)($p->valor_pago ?: $p->valor_parcela);
                            $saldo = max(0, $saldo - $valorBaixado);
                            $saldoFormatado = number_format($saldo, 2, ',', '.');
                            $dataPag = date('d/m/Y', strtotime($p->data_pagamento ?: $p->data_vencimento));
                            $vlReceb = number_format($p->valor_pago ?: $p->valor_parcela, 2, ',', '.');
                            $tipoNome = $p->formaPagamento ? ($p->formaPagamento->nome ?: $p->formaPagamento->tipo) : 'DINHEIRO';
                        } else {
                            $saldoFormatado = '';
                            $dataPag = '';
                            $vlReceb = '';
                            $tipoNome = '';
                        }
                ?>
                    <tr>
                        <td class="col-prest"><?= date('d/m/Y', strtotime($p->data_vencimento)) ?></td>
                        <td><?= number_format($p->valor_parcela, 2, ',', '.') ?></td>
                        <td><?= number_format($cartao->valor_total, 2, ',', '.') ?></td>
                        <td><?= $dataPag ?></td>
                        <td><?= $vlReceb ?></td>
                        <td style="font-size: 6px;"><?= Html::encode($tipoNome) ?></td>
                        <td class="col-saldo"><?= $saldoFormatado ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        <p>Obs.: Não aceitamos devolução. &nbsp; • &nbsp; « Deus é Fiel » &nbsp; • &nbsp; Devolução paga 20%</p>
    </div>
</div>

<script>
    const nomeArquivo = "<?= $nomeArquivo ?>";

    function showToast(msg) {
        const t = document.getElementById('statusToast');
        t.innerText = msg;
        t.style.display = 'block';
    }

    function hideToast() {
        const t = document.getElementById('statusToast');
        t.style.display = 'none';
    }

    // Baixar como Imagem PNG
    function baixarImagem() {
        const btn = document.getElementById('btnBaixarImg');
        const element = document.getElementById('cartao-imprimir');
        btn.disabled = true;
        showToast('Gerando imagem PNG em alta resolução...');

        html2canvas(element, {
            scale: 3, // 3x para qualidade máxima de impressão
            useCORS: true,
            backgroundColor: '#ffffff'
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = nomeArquivo + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            hideToast();
            btn.disabled = false;
        }).catch(err => {
            alert('Erro ao gerar imagem: ' + err.message);
            hideToast();
            btn.disabled = false;
        });
    }

    // Baixar como PDF no formato 1/4 A4 (105mm x 148.5mm)
    function baixarPDF() {
        const btn = document.getElementById('btnBaixarPdf');
        const element = document.getElementById('cartao-imprimir');
        btn.disabled = true;
        showToast('Gerando arquivo PDF (1/4 folha A4)...');

        html2canvas(element, {
            scale: 3,
            useCORS: true,
            backgroundColor: '#ffffff'
        }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            // A6: 105mm x 148.5mm
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: [105, 148.5]
            });

            // Ajusta o cartão na página com margem de 2.5mm
            const imgWidth = 100; // 105mm - 5mm
            const pageHeight = 148.5;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            pdf.addImage(imgData, 'PNG', 2.5, 2.5, imgWidth, Math.min(imgHeight, 143.5));
            pdf.save(nomeArquivo + '.pdf');

            hideToast();
            btn.disabled = false;
        }).catch(err => {
            alert('Erro ao gerar PDF: ' + err.message);
            hideToast();
            btn.disabled = false;
        });
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (window.location.search.includes('autoprint=1')) {
            window.print();
        }
    });
</script>
</body>
</html>
