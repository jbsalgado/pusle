<?php

namespace app\modules\marketplace\components;

use Yii;
use yii\base\Component;
use GuzzleHttp\Client;
use app\modules\marketplace\models\MarketplaceSyncLog;

/**
 * Classe abstrata base para serviços de marketplace
 * 
 * Todos os marketplaces (Mercado Livre, Shopee, etc) devem estender esta classe
 */
abstract class MarketplaceService extends Component
{
    /**
     * @var string Nome do marketplace (MERCADO_LIVRE, SHOPEE, etc)
     */
    protected $marketplaceName;

    /**
     * @var array Configuração do marketplace
     */
    protected $config;

    /**
     * @var Client Cliente HTTP Guzzle
     */
    protected $httpClient;

    /**
     * @var string ID do usuário
     */
    protected $usuarioId;

    /**
     * Inicializa o serviço
     */
    public function init()
    {
        parent::init();

        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => true,
        ]);
    }

    /**
     * Define a configuração do marketplace
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->usuarioId = $config['usuario_id'] ?? null;
    }
    
    // ============================================================================================================
    // MÉTODOS ABSTRATOS (devem ser implementados por cada marketplace)
    // ============================================================================================================

    /**
     * Autentica no marketplace
     * @return bool
     */
    abstract public function authenticate();

    /**
     * Atualiza o token de acesso
     * @return bool
     */
    abstract public function refreshToken();

    /**
     * Sincroniza produtos do sistema local para o marketplace
     * @param array $produtoIds IDs dos produtos a sincronizar (vazio = todos)
     * @return array Resultado da sincronização
     */
    abstract public function syncProdutos($produtoIds = []);

    /**
     * Atualiza estoque de um produto no marketplace
     * @param string $produtoId ID do produto local
     * @param int $quantidade Nova quantidade em estoque
     * @return bool
     */
    abstract public function syncEstoque($produtoId, $quantidade);

    /**
     * Importa pedidos do marketplace
     * @param string $dataInicio Data inicial (formato Y-m-d)
     * @param string $dataFim Data final (formato Y-m-d)
     * @return array Resultado da importação
     */
    abstract public function importPedidos($dataInicio = null, $dataFim = null);

    /**
     * Atualiza status de um pedido no marketplace
     * @param string $pedidoId ID do pedido no marketplace
     * @param string $status Novo status
     * @param array $dados Dados adicionais (código de rastreio, etc)
     * @return bool
     */
    abstract public function updatePedidoStatus($pedidoId, $status, $dados = []);

    /**
     * Processa webhook recebido do marketplace
     * @param array $payload Dados do webhook
     * @return bool
     */
    abstract public function processWebhook($payload);
    
    // ============================================================================================================
    // MÉTODOS AUXILIARES COMUNS
    // ============================================================================================================

    /**
     * Registra log de sincronização
     * @param string $tipo Tipo de sincronização (PRODUTOS, ESTOQUE, PEDIDOS, WEBHOOK)
     * @param string $status Status (SUCESSO, ERRO, PARCIAL)
     * @param string $mensagem Mensagem descritiva
     * @param array $detalhes Detalhes adicionais
     * @param int $tempoExecucaoMs Tempo de execução em milissegundos
     * @return MarketplaceSyncLog
     */
    protected function log($tipo, $status, $mensagem, $detalhes = [], $tempoExecucaoMs = null)
    {
        $log = new MarketplaceSyncLog();
        $log->usuario_id = $this->usuarioId;
        $log->marketplace = $this->marketplaceName;
        $log->tipo_sync = $tipo;
        $log->status = $status;
        $log->mensagem = $mensagem;
        $log->detalhes = $detalhes;
        $log->tempo_execucao_ms = $tempoExecucaoMs;
        $log->itens_processados = $detalhes['itens_processados'] ?? 0;
        $log->itens_sucesso = $detalhes['itens_sucesso'] ?? 0;
        $log->itens_erro = $detalhes['itens_erro'] ?? 0;
        $log->data_fim = new \yii\db\Expression('NOW()');
        $log->save();

        return $log;
    }

    /**
     * Trata erros de API
     * @param \Exception $exception
     * @param string $contexto Contexto do erro
     * @return array
     */
    protected function handleError($exception, $contexto = '')
    {
        $mensagem = sprintf(
            '[%s] Erro em %s: %s',
            $this->marketplaceName,
            $contexto,
            $exception->getMessage()
        );

        Yii::error($mensagem, __METHOD__);

        return [
            'success' => false,
            'error' => $exception->getMessage(),
            'contexto' => $contexto,
        ];
    }

    /**
     * Verifica se o token está expirado
     * @return bool
     */
    protected function isTokenExpired()
    {
        if (empty($this->config['token_expira_em'])) {
            return true;
        }

        $expiraEm = new \DateTime($this->config['token_expira_em']);
        $agora = new \DateTime();

        // Considera expirado se faltar menos de 5 minutos
        $expiraEm->modify('-5 minutes');

        return $agora >= $expiraEm;
    }

    /**
     * Faz requisição HTTP com tratamento de erros
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $url URL completa
     * @param array $options Opções do Guzzle
     * @return array Resposta decodificada
     * @throws \Exception
     */
    protected function request($method, $url, $options = [])
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = (string) $response->getBody();

            return json_decode($body, true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Erro 4xx (cliente)
            $response = $e->getResponse();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            throw new \Exception(sprintf(
                'Erro HTTP %d: %s',
                $response->getStatusCode(),
                $data['message'] ?? $body
            ));
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Erro 5xx (servidor)
            throw new \Exception(sprintf(
                'Erro no servidor do marketplace: %s',
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Valida se a configuração está completa
     * @return bool
     */
    protected function validateConfig()
    {
        if (empty($this->config)) {
            throw new \Exception('Configuração do marketplace não definida');
        }

        if (empty($this->usuarioId)) {
            throw new \Exception('ID do usuário não definido');
        }

        return true;
    }
}
