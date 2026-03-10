import { API_ENDPOINTS, CONFIG } from './config.js';
import { getToken } from './storage.js';

/**
 * Inicializa o gerenciamento de maquinetas
 */
export function inicializarGerenciamentoMaquinetas() {
    const btnAbrir = document.getElementById('btn-gerenciar-maquinetas');
    const btnSalvar = document.getElementById('btn-salvar-maquineta');

    if (btnAbrir) {
        btnAbrir.onclick = abrirModalMaquinetas;
    }

    if (btnSalvar) {
        btnSalvar.onclick = salvarNovaMaquineta;
    }
}

/**
 * Abre o modal e carrega a lista
 */
export async function abrirModalMaquinetas() {
    const modal = document.getElementById('modal-gerenciar-maquinetas');
    if (modal) {
        modal.classList.remove('hidden');
        await listarMaquinetas();
    }
}

/**
 * Lista as maquinetas do lojista
 */
export async function listarMaquinetas() {
    const container = document.getElementById('lista-maquinetas');
    if (!container) return;

    try {
        container.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">Buscando dispositivos...</p>';
        
        const token = await getToken();
        const headers = { 'Content-Type': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const response = await fetch(`${API_ENDPOINTS.MERCADOPAGO_LISTAR_DISPOSITIVOS}?tenant_id=${CONFIG.ID_USUARIO_LOJA}`, {
            headers: headers
        });

        if (!response.ok) throw new Error('Falha ao listar dispositivos');

        const data = await response.json();
        const dispositivos = data.dispositivos || [];

        if (dispositivos.length === 0) {
            container.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">Nenhuma maquineta vinculada.</p>';
            return;
        }

        container.innerHTML = dispositivos.map(d => `
            <div class="flex justify-between items-center p-3 border rounded-lg bg-white shadow-sm">
                <div>
                    <h4 class="font-bold text-gray-800 text-sm">${d.nome}</h4>
                    <p class="text-xs text-gray-500">S/N: ${d.device_id}</p>
                </div>
                <button onclick="window.excluirMaquineta('${d.id}')" class="text-red-500 hover:text-red-700 p-2" title="Excluir">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `).join('');

    } catch (error) {
        console.error('[Devices] ❌ Erro ao listar:', error);
        container.innerHTML = '<p class="text-center text-red-500 text-sm py-4">Erro ao carregar lista.</p>';
    }
}

/**
 * Salva um novo vínculo
 */
async function salvarNovaMaquineta() {
    const inputSerial = document.getElementById('point-serial');
    const inputNome = document.getElementById('point-nome');
    const btn = document.getElementById('btn-salvar-maquineta');

    const serial = inputSerial.value.trim();
    const nome = inputNome.value.trim();

    if (!serial || !nome) {
        alert('Por favor, informe o Serial Number e um Nome para a máquina.');
        return;
    }

    try {
        btn.disabled = true;
        btn.innerHTML = 'Vinculando...';

        const token = await getToken();
        const headers = { 'Content-Type': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const response = await fetch(API_ENDPOINTS.MERCADOPAGO_CRIAR_PAGAMENTO_POINT.replace('criar-pagamento-point', 'registrar-dispositivo'), {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                tenant_id: CONFIG.ID_USUARIO_LOJA,
                device_id: serial,
                nome: nome
            })
        });

        if (!response.ok) {
            const erro = await response.json();
            throw new Error(erro.erro || 'Falha ao vincular dispositivo');
        }

        alert('Maquineta vinculada com sucesso!');
        inputSerial.value = '';
        inputNome.value = '';
        await listarMaquinetas();

    } catch (error) {
        console.error('[Devices] ❌ Erro ao salvar:', error);
        alert('Erro: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Vincular Dispositivo';
    }
}

/**
 * Exclui um vínculo (Inativa)
 */
window.excluirMaquineta = async function(id) {
    if (!confirm('Tem certeza que deseja desvincular esta maquineta?')) return;

    try {
        const token = await getToken();
        const headers = { 'Content-Type': 'application/json' };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        // Usamos o endpoint de registro enviando um delete ou inativando? 
        // Como o backend ainda não tem ação Delete específica, vamos deixar o placeholder da URL
        // Por enquanto, o backend tem Registrar. Vou assumir que o usuário quer apenas desvincular do Pulse.
        
        const response = await fetch(`${CONFIG.URL_API}/api/mercado-pago/remover-dispositivo?id=${id}`, {
            method: 'DELETE',
            headers: headers
        });

        if (!response.ok) throw new Error('Falha ao remover dispositivo');

        await listarMaquinetas();
    } catch (error) {
        console.error('[Devices] ❌ Erro ao excluir:', error);
        alert('Erro ao excluir: ' + error.message);
    }
}
