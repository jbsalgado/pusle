<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\BridgeWhatsappLoja;
use app\modules\vendas\models\BridgeWhatsappMensagem;

$this->title = 'WhatsApp Local (Pulse Agent)';
$this->params['breadcrumbs'][] = ['label' => 'Vendas', 'url' => ['/vendas/inicio/index']];
$this->params['breadcrumbs'][] = $this->title;

$serverUrl = Yii::$app->request->hostInfo;
$token = $loja->token_agente;
?>

<div class="bridge-whatsapp-index container-fluid py-4" style="color: #e2e8f0;">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div>
            <h2 class="fw-bold mb-1 text-white">
                <i class="fas fa-network-wired text-primary me-2"></i> WhatsApp Local — Pulse Bridge Agent
            </h2>
            <p class="text-muted mb-0">
                Dispare mensagens e atenda clientes usando a conexão de internet e chip da sua própria loja. 
                <span class="badge bg-success-subtle text-success border border-success ms-2">Zero Custo Meta API</span>
                <span class="badge bg-info-subtle text-info border border-info ms-1">IP Residencial Antiban</span>
            </p>
        </div>
        <div>
            <span id="badge-agente" class="badge p-2 px-3 fs-6 rounded-pill <?= $loja->isAgenteOnline() ? 'bg-success' : 'bg-danger' ?>">
                <i class="fas <?= $loja->isAgenteOnline() ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i>
                <span id="badge-agente-texto"><?= $loja->isAgenteOnline() ? 'Agente Online' : 'Agente Offline' ?></span>
            </span>
        </div>
    </div>

    <!-- Cards de Status e Controle -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Status do Agente Local -->
        <div class="col-lg-4 col-md-6">
            <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 me-3">
                            <i class="fas fa-laptop-code fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">Computador da Loja</h5>
                            <small class="text-muted">Serviço de borda local</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-50">
                            <span class="text-muted">Status do Agente:</span>
                            <span id="info-agente-status" class="fw-bold <?= $loja->isAgenteOnline() ? 'text-success' : 'text-danger' ?>">
                                <?= $loja->isAgenteOnline() ? '🟢 Conectado à VPS' : '🔴 Desconectado' ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-50">
                            <span class="text-muted">IP Detectado:</span>
                            <span id="info-agente-ip" class="text-light font-monospace"><?= Html::encode($loja->ip_origem_agente ?: 'Não identificado') ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span class="text-muted">Último Sinal:</span>
                            <span id="info-agente-heartbeat" class="text-light small"><?= $loja->ultimo_heartbeat ? date('d/m/Y H:i:s', strtotime($loja->ultimo_heartbeat)) : 'Nunca' ?></span>
                        </li>
                    </ul>
                    <button class="btn btn-outline-info w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#modalInstalacao">
                        <i class="fas fa-download me-1"></i> Como Instalar o Agente no PC
                    </button>
                </div>
            </div>
        </div>

        <!-- Card 2: Status do WhatsApp -->
        <div class="col-lg-4 col-md-6">
            <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 me-3">
                            <i class="fab fa-whatsapp fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">Sessão WhatsApp</h5>
                            <small class="text-muted">Motor Whatsmeow Local</small>
                        </div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-50">
                            <span class="text-muted">Conexão WhatsApp:</span>
                            <span id="info-wa-status" class="fw-bold <?= $loja->isWhatsappConectado() ? 'text-success' : 'text-warning' ?>">
                                <?= $loja->isWhatsappConectado() ? '🟢 Conectado' : '⚪ ' . ucfirst($loja->status_conexao) ?>
                            </span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-50">
                            <span class="text-muted">Número Vinculado:</span>
                            <span id="info-wa-phone" class="text-light font-monospace"><?= Html::encode($loja->telefone_conectado ?: 'Nenhum') ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2">
                            <span class="text-muted">Nome do Perfil:</span>
                            <span id="info-wa-name" class="text-light"><?= Html::encode($loja->push_name ?: '-') ?></span>
                        </li>
                    </ul>
                    <div class="d-flex gap-2">
                        <button id="btn-conectar" class="btn btn-success flex-grow-1" onclick="conectarWhatsapp()">
                            <i class="fas fa-qrcode me-1"></i> Conectar / QR Code
                        </button>
                        <button id="btn-desconectar" class="btn btn-outline-danger" onclick="desconectarWhatsapp()" title="Desconectar">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Disparo de Teste Rápido -->
        <div class="col-lg-4 col-md-12">
            <div class="card bg-dark border-secondary h-100 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 me-3">
                            <i class="fas fa-paper-plane fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">Disparo de Teste</h5>
                            <small class="text-muted">Envie para o seu próprio celular</small>
                        </div>
                    </div>
                    <form id="form-teste" onsubmit="enviarTeste(event)">
                        <div class="mb-2">
                            <label class="form-label text-muted small mb-1">Telefone de Destino com DDD:</label>
                            <input type="text" id="teste-numero" class="form-control bg-black text-light border-secondary" placeholder="Ex: 5511999998888" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">Mensagem:</label>
                            <input type="text" id="teste-texto" class="form-control bg-black text-light border-secondary" value="Teste de conexão via Pulse Bridge WhatsApp Local!" required>
                        </div>
                        <button type="submit" id="btn-enviar-teste" class="btn btn-warning w-100 text-dark fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Enviar Mensagem de Teste
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Histórico de Mensagens -->
    <div class="card bg-dark border-secondary shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-transparent border-secondary d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title text-white mb-0">
                <i class="fas fa-history text-muted me-2"></i> Histórico Recente de Mensagens (Últimas 20)
            </h5>
            <span class="badge bg-secondary"><?= count($mensagens) ?> mensagens</span>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small border-secondary">
                        <th>DATA/HORA</th>
                        <th>DIREÇÃO</th>
                        <th>NÚMERO</th>
                        <th>CONTEÚDO</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody id="lista-mensagens">
                    <?php if (empty($mensagens)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Nenhuma mensagem registrada ainda. Faça um teste de envio acima!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mensagens as $m): ?>
                            <tr>
                                <td class="small text-muted"><?= date('d/m/Y H:i:s', strtotime($m->created_at)) ?></td>
                                <td>
                                    <?php if ($m->direcao === BridgeWhatsappMensagem::DIRECAO_OUTBOUND): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary"><i class="fas fa-arrow-up me-1"></i> Enviada</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-arrow-down me-1"></i> Recebida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-monospace text-light"><?= Html::encode($m->direcao === BridgeWhatsappMensagem::DIRECAO_OUTBOUND ? $m->numero_destino : $m->numero_remetente) ?></td>
                                <td class="text-light" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= Html::encode($m->conteudo_texto) ?>
                                </td>
                                <td>
                                    <?php
                                        $badgeClass = 'bg-secondary';
                                        if ($m->status === 'delivered') $badgeClass = 'bg-info text-dark';
                                        if ($m->status === 'read') $badgeClass = 'bg-success';
                                        if ($m->status === 'failed') $badgeClass = 'bg-danger';
                                        if ($m->status === 'pending') $badgeClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= strtoupper($m->status) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: QR Code -->
<div class="modal fade" id="modalQrCode" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="fab fa-whatsapp text-success me-2"></i> Escaneie o QR Code no WhatsApp
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="text-muted small mb-3">
                    Abra o WhatsApp no celular &rarr; <b>Aparelhos Conectados</b> &rarr; <b>Conectar um aparelho</b> e aponte para o código abaixo:
                </p>
                <div id="qr-container" class="p-3 bg-white d-inline-block rounded shadow-sm">
                    <div id="qr-spinner" class="py-5 text-dark">
                        <div class="spinner-border text-success mb-2" role="status"></div>
                        <p class="mb-0 small text-muted">Aguardando geração pelo Agente Local...</p>
                    </div>
                    <img id="qr-image" src="" alt="QR Code WhatsApp" style="display: none; max-width: 256px; height: auto;" />
                </div>
                <div class="mt-3">
                    <span id="qr-status-badge" class="badge bg-warning text-dark">Aguardando leitura</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Instruções de Instalação do Agente -->
<div class="modal fade" id="modalInstalacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-terminal text-primary me-2"></i> Como Executar o Pulse Agent no Computador da Loja
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <h6 class="text-white fw-bold mb-2">1. Baixe o Binário do Agente:</h6>
                <div class="d-flex gap-2 mb-4">
                    <a href="/downloads/bridge/pulse-agent.exe" class="btn btn-outline-primary" download>
                        <i class="fab fa-windows me-1"></i> Baixar para Windows (.exe)
                    </a>
                    <a href="/downloads/bridge/pulse-agent-linux" class="btn btn-outline-secondary" download>
                        <i class="fab fa-linux me-1"></i> Baixar para Linux
                    </a>
                </div>

                <h6 class="text-white fw-bold mb-2">2. Token Exclusivo da sua Loja:</h6>
                <div class="input-group mb-4">
                    <input type="text" id="token-copy" class="form-control bg-black text-warning border-secondary font-monospace" value="<?= Html::encode($token) ?>" readonly>
                    <button class="btn btn-secondary" onclick="navigator.clipboard.writeText('<?= Html::encode($token) ?>'); alert('Token copiado!');">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>

                <h6 class="text-white fw-bold mb-2">3. Executando no Terminal / Prompt de Comando:</h6>
                <div class="bg-black p-3 rounded border border-secondary mb-3 font-monospace small text-success">
                    # No Windows (CMD / PowerShell):<br/>
                    .\pulse-agent.exe --token="<?= Html::encode($token) ?>" --server="<?= Html::encode($serverUrl) ?>"<br/><br/>
                    # No Linux:<br/>
                    chmod +x pulse-agent-linux<br/>
                    ./pulse-agent-linux --token="<?= Html::encode($token) ?>" --server="<?= Html::encode($serverUrl) ?>"
                </div>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle me-1"></i> O agente se conectará automaticamente à VPS. Todas as mensagens serão enviadas através da conexão e IP do computador da sua loja.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
let modalQrInstance = null;
let pollTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    modalQrInstance = new bootstrap.Modal(document.getElementById('modalQrCode'));
    iniciarPolling();
});

function iniciarPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(atualizarStatus, 3000);
}

function atualizarStatus() {
    fetch('<?= Url::to(['/vendas/bridge-whatsapp/status-json']) ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            // Agente status
            const badgeAgente = document.getElementById('badge-agente');
            const badgeAgenteTexto = document.getElementById('badge-agente-texto');
            const infoAgenteStatus = document.getElementById('info-agente-status');
            const infoAgenteIp = document.getElementById('info-agente-ip');
            const infoAgenteHeartbeat = document.getElementById('info-agente-heartbeat');

            if (data.agente_online) {
                badgeAgente.className = 'badge p-2 px-3 fs-6 rounded-pill bg-success';
                badgeAgenteTexto.innerText = 'Agente Online';
                infoAgenteStatus.className = 'fw-bold text-success';
                infoAgenteStatus.innerText = '🟢 Conectado à VPS';
            } else {
                badgeAgente.className = 'badge p-2 px-3 fs-6 rounded-pill bg-danger';
                badgeAgenteTexto.innerText = 'Agente Offline';
                infoAgenteStatus.className = 'fw-bold text-danger';
                infoAgenteStatus.innerText = '🔴 Desconectado';
            }

            if (data.ip_agente) infoAgenteIp.innerText = data.ip_agente;
            if (data.ultimo_heartbeat) infoAgenteHeartbeat.innerText = data.ultimo_heartbeat;

            // WhatsApp status
            const infoWaStatus = document.getElementById('info-wa-status');
            const infoWaPhone = document.getElementById('info-wa-phone');
            const infoWaName = document.getElementById('info-wa-name');

            if (data.whatsapp_conectado) {
                infoWaStatus.className = 'fw-bold text-success';
                infoWaStatus.innerText = '🟢 Conectado';
                infoWaPhone.innerText = data.telefone || 'Nenhum';
                infoWaName.innerText = data.push_name || '-';
                // Fecha modal se estava aberto
                if (modalQrInstance && document.getElementById('modalQrCode').classList.contains('show')) {
                    modalQrInstance.hide();
                }
            } else {
                infoWaStatus.className = 'fw-bold text-warning';
                infoWaStatus.innerText = '⚪ ' + (data.status || 'Desconectado');
            }

            // QR Code se modal estiver aberto
            if (data.qr_code && document.getElementById('modalQrCode').classList.contains('show')) {
                const img = document.getElementById('qr-image');
                const spinner = document.getElementById('qr-spinner');
                img.src = data.qr_code.startsWith('data:') ? data.qr_code : 'data:image/png;base64,' + data.qr_code;
                img.style.display = 'block';
                spinner.style.display = 'none';
            }
        })
        .catch(err => console.error('Erro polling:', err));
}

function conectarWhatsapp() {
    modalQrInstance.show();
    document.getElementById('qr-spinner').style.display = 'block';
    document.getElementById('qr-image').style.display = 'none';

    fetch('<?= Url::to(['/vendas/bridge-whatsapp/conectar']) ?>', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            console.log('Comando conectar:', d);
        });
}

function desconectarWhatsapp() {
    if (!confirm('Deseja realmente desconectar a sessão do WhatsApp?')) return;
    fetch('<?= Url::to(['/vendas/bridge-whatsapp/desconectar']) ?>', { method: 'POST' })
        .then(r => r.json())
        .then(d => {
            alert(d.message);
            atualizarStatus();
        });
}

function enviarTeste(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-enviar-teste');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enfileirando...';

    const numero = document.getElementById('teste-numero').value;
    const texto = document.getElementById('teste-texto').value;

    const fd = new FormData();
    fd.append('numero', numero);
    fd.append('texto', texto);

    fetch('<?= Url::to(['/vendas/bridge-whatsapp/enviar-teste']) ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar Mensagem de Teste';
        if (d.success) {
            alert('Mensagem colocada na fila com sucesso! O Agente Local fará o envio através do chip da loja.');
            location.reload();
        } else {
            alert('Erro: ' + d.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar Mensagem de Teste';
        alert('Falha na comunicação com o servidor.');
    });
}
</script>
