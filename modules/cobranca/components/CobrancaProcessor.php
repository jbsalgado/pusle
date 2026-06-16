<?php

namespace app\modules\cobranca\components;

use Yii;
use yii\base\Component;
use app\modules\vendas\models\Parcela;
use app\modules\cobranca\models\CobrancaConfiguracao;
use app\modules\cobranca\models\CobrancaTemplate;
use app\modules\cobranca\models\CobrancaHistorico;

/**
 * CobrancaProcessor
 * 
 * Processador de cobranças automáticas
 * Responsável por buscar parcelas e enviar mensagens
 */
class CobrancaProcessor extends Component
{
    /**
     * Processa todas as cobranças do dia
     * 
     * @return array Estatísticas de envio
     */
    public function processarCobrancasDoDia()
    {
        $stats = [
            'total' => 0,
            'enviadas' => 0,
            'falhas' => 0,
            'por_tipo' => [
                'ANTES' => 0,
                'DIA' => 0,
                'APOS' => 0,
            ],
        ];

        // Buscar todas as configurações ativas
        $configuracoes = CobrancaConfiguracao::find()
            ->where(['ativo' => true])
            ->all();

        foreach ($configuracoes as $config) {
            if (!$config->isReady()) {
                Yii::warning("Configuração do usuário {$config->usuario_id} não está pronta", __METHOD__);
                continue;
            }

            // Processar cada tipo de cobrança
            if ($config->dias_antes_vencimento > 0) {
                $resultado = $this->processarTipo($config, CobrancaTemplate::TIPO_ANTES, $config->dias_antes_vencimento);
                $stats['total'] += $resultado['total'];
                $stats['enviadas'] += $resultado['enviadas'];
                $stats['falhas'] += $resultado['falhas'];
                $stats['por_tipo']['ANTES'] += $resultado['enviadas'];
            }

            if ($config->enviar_dia_vencimento) {
                $resultado = $this->processarTipo($config, CobrancaTemplate::TIPO_DIA, 0);
                $stats['total'] += $resultado['total'];
                $stats['enviadas'] += $resultado['enviadas'];
                $stats['falhas'] += $resultado['falhas'];
                $stats['por_tipo']['DIA'] += $resultado['enviadas'];
            }

            if ($config->dias_apos_vencimento > 0) {
                $resultado = $this->processarTipo($config, CobrancaTemplate::TIPO_APOS, -$config->dias_apos_vencimento);
                $stats['total'] += $resultado['total'];
                $stats['enviadas'] += $resultado['enviadas'];
                $stats['falhas'] += $resultado['falhas'];
                $stats['por_tipo']['APOS'] += $resultado['enviadas'];
            }
        }

        Yii::info("Processamento concluído: {$stats['enviadas']} enviadas, {$stats['falhas']} falhas", __METHOD__);

        return $stats;
    }

    /**
     * Processa cobranças de um tipo específico
     * 
     * @param CobrancaConfiguracao $config
     * @param string $tipo
     * @param int $diasDiferenca Positivo para futuro, negativo para passado
     * @return array
     */
    protected function processarTipo($config, $tipo, $diasDiferenca)
    {
        $stats = ['total' => 0, 'enviadas' => 0, 'falhas' => 0];

        // Calcular data alvo
        $dataAlvo = date('Y-m-d', strtotime(($diasDiferenca >= 0 ? '+' : '') . $diasDiferenca . ' days'));

        // Buscar parcelas
        $parcelas = $this->buscarParcelas($config->usuario_id, $dataAlvo);

        foreach ($parcelas as $parcela) {
            $stats['total']++;

            // Verificar se já foi enviada
            if (CobrancaHistorico::jaEnviado($parcela->id, $tipo)) {
                Yii::info("Cobrança tipo {$tipo} já enviada para parcela {$parcela->id}", __METHOD__);
                continue;
            }

            // Enviar cobrança
            if ($this->enviarCobranca($parcela, $tipo, $config)) {
                $stats['enviadas']++;
            } else {
                $stats['falhas']++;
            }
        }

        return $stats;
    }

    /**
     * Busca parcelas para cobrança
     * 
     * @param string $usuarioId
     * @param string $dataVencimento
     * @return Parcela[]
     */
    protected function buscarParcelas($usuarioId, $dataVencimento)
    {
        return Parcela::find()
            ->joinWith(['venda', 'venda.cliente'])
            ->where(['prest_vendas.usuario_id' => $usuarioId])
            ->andWhere(['prest_parcelas.status_parcela_codigo' => 'PENDENTE'])
            ->andWhere(['prest_parcelas.data_vencimento' => $dataVencimento])
            ->andWhere(['IS NOT', 'prest_clientes.telefone', null])
            ->andWhere(['<>', 'prest_clientes.telefone', ''])
            ->all();
    }

    /**
     * Envia cobrança para uma parcela
     * 
     * @param Parcela $parcela
     * @param string $tipo
     * @param CobrancaConfiguracao $config
     * @return bool
     */
    protected function enviarCobranca($parcela, $tipo, $config)
    {
        $cliente = $parcela->venda->cliente;

        // Buscar template
        $template = CobrancaTemplate::findOne([
            'usuario_id' => $config->usuario_id,
            'tipo' => $tipo,
            'ativo' => true,
        ]);

        if (!$template) {
            Yii::warning("Template tipo {$tipo} não encontrado para usuário {$config->usuario_id}", __METHOD__);
            return false;
        }

        // Substituir variáveis
        $mensagem = $template->substituirVariaveis($parcela, $cliente);

        // Criar registro de histórico
        $historico = new CobrancaHistorico();
        $historico->usuario_id = $config->usuario_id;
        $historico->parcela_id = $parcela->id;
        $historico->tipo = $tipo;
        $historico->telefone = $cliente->telefone;
        $historico->mensagem = $mensagem;
        $historico->status = CobrancaHistorico::STATUS_PENDENTE;
        $historico->save();

        // Enviar via WhatsApp
        $whatsapp = new WhatsAppService($config);
        $resultado = $whatsapp->enviarMensagem($cliente->telefone, $mensagem);

        // Atualizar histórico
        $historico->registrarTentativa($resultado['success'], $resultado);

        if ($resultado['success']) {
            Yii::info("Cobrança enviada: Parcela {$parcela->id}, Cliente {$cliente->nome}, Tipo {$tipo}", __METHOD__);
        } else {
            Yii::error("Falha ao enviar cobrança: {$resultado['message']}", __METHOD__);
        }

        return $resultado['success'];
    }

    /**
     * Reenvia uma cobrança do histórico
     * 
     * @param CobrancaHistorico $historico
     * @return bool
     */
    public function reenviarCobranca($historico)
    {
        $config = CobrancaConfiguracao::findOne(['usuario_id' => $historico->usuario_id]);

        if (!$config || !$config->isReady()) {
            return false;
        }

        $whatsapp = new WhatsAppService($config);
        $resultado = $whatsapp->enviarMensagem($historico->telefone, $historico->mensagem);

        $historico->registrarTentativa($resultado['success'], $resultado);

        return $resultado['success'];
    }
}
