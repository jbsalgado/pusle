<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\vendas\models\TaxaEntrega;
use app\modules\vendas\models\LojaConfiguracao;
use app\components\MelhorEnvioService;
use yii\web\Response;

class FreteController extends BaseController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator']['optional'] = ['calcular', 'cotar'];
        return $behaviors;
    }

    /**
     * Endpoint legado e direto para calcular a taxa de entrega mais aplicável
     * GET /api/frete/calcular?usuario_id=...&cep=...&bairro=...&cidade=...&estado=...&subtotal=...&porte=...
     */
    public function actionCalcular()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $usuarioId = Yii::$app->request->get('usuario_id');
        $cep = Yii::$app->request->get('cep');
        $bairro = Yii::$app->request->get('bairro');
        $cidade = Yii::$app->request->get('cidade');
        $estado = Yii::$app->request->get('estado');
        $subtotal = (float)Yii::$app->request->get('subtotal', 0);
        $porte = Yii::$app->request->get('porte', 'P'); // Padrão Pequeno

        if (!$usuarioId) {
            return [
                'success' => false,
                'message' => 'usuario_id não informado'
            ];
        }

        // Tenta buscar cotações completas via actionCotar internamente
        $opcoes = $this->obterOpcoesCalculadas($usuarioId, $cep, $bairro, $cidade, $estado, $subtotal, $porte);

        if (!empty($opcoes)) {
            // Pega a opção mais vantajosa (excluindo retirada, a menos que seja a única)
            $opcoesEntrega = array_filter($opcoes, fn($o) => $o['tipo'] !== 'RETIRADA');
            $melhorOpcao = !empty($opcoesEntrega) ? reset($opcoesEntrega) : reset($opcoes);

            return [
                'success' => true,
                'valor' => (float)$melhorOpcao['valor'],
                'valor_minimo_frete_gratis' => $melhorOpcao['valor_minimo_frete_gratis'] ?? null,
                'regra_id' => $melhorOpcao['id'] ?? null,
                'servico' => $melhorOpcao['servico'] ?? 'Entrega',
                'prazo_descricao' => $melhorOpcao['prazo_descricao'] ?? null,
                'tipo' => $melhorOpcao['tipo'] ?? 'INTERNO',
                'opcoes' => $opcoes,
                'params' => [
                    'cep' => $cep,
                    'bairro' => $bairro,
                    'cidade' => $cidade,
                    'estado' => $estado,
                ]
            ];
        }

        // Fallback básico para a regra direta
        $regra = TaxaEntrega::findRegra($usuarioId, $cidade, $bairro, $cep, $porte, $estado, $subtotal);

        return [
            'success' => true,
            'valor' => $regra ? (float)$regra->valor : 0.00,
            'valor_minimo_frete_gratis' => $regra ? ($regra->valor_minimo_frete_gratis ? (float)$regra->valor_minimo_frete_gratis : null) : null,
            'regra_id' => $regra ? $regra->id : null,
            'params' => [
                'cep' => $cep,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
            ]
        ];
    }

    /**
     * Endpoint completo para cotação de múltiplas opções de frete (Melhor Envio + Solução Interna por Estado)
     * GET /api/frete/cotar?usuario_id=...&cep=...&bairro=...&cidade=...&estado=...&subtotal=...&porte=...
     */
    public function actionCotar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usuarioId = Yii::$app->request->get('usuario_id');
        $cep = Yii::$app->request->get('cep');
        $bairro = Yii::$app->request->get('bairro');
        $cidade = Yii::$app->request->get('cidade');
        $estado = Yii::$app->request->get('estado');
        $subtotal = (float)Yii::$app->request->get('subtotal', 0);
        $porte = Yii::$app->request->get('porte', 'P');

        if (!$usuarioId) {
            return [
                'success' => false,
                'message' => 'usuario_id não informado'
            ];
        }

        $opcoes = $this->obterOpcoesCalculadas($usuarioId, $cep, $bairro, $cidade, $estado, $subtotal, $porte);

        return [
            'success' => true,
            'opcoes' => $opcoes,
            'total_opcoes' => count($opcoes),
            'origem' => !empty($opcoes) && $opcoes[0]['tipo'] === 'MELHOR_ENVIO' ? 'MELHOR_ENVIO' : 'INTERNO',
            'params' => [
                'cep' => $cep,
                'cidade' => $cidade,
                'estado' => $estado,
                'subtotal' => $subtotal,
            ]
        ];
    }

    /**
     * Motor central de cálculo: Combina Melhor Envio com Solução Interna garantindo fail-safe
     */
    private function obterOpcoesCalculadas($usuarioId, $cep, $bairro, $cidade, $estado, $subtotal, $porte): array
    {
        $opcoes = [];

        // 1. Tentar cotação com Melhor Envio se a loja tiver ativo
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);
        if ($lojaConfig && $lojaConfig->melhor_envio_ativo && !empty($lojaConfig->melhor_envio_token) && !empty($cep)) {
            try {
                $opcoesMe = MelhorEnvioService::cotarFrete($lojaConfig, $cep, $subtotal, $porte);
                if (!empty($opcoesMe)) {
                    $opcoes = array_merge($opcoes, $opcoesMe);
                }
            } catch (\Exception $e) {
                Yii::error("[FreteController] Erro no Melhor Envio: " . $e->getMessage(), __METHOD__);
            }
        }

        // 2. Buscar opções da Solução Interna da Loja (Tabela por Estado, Cidade, Bairro, CEP e Faixa de Preço)
        $opcoesInternas = TaxaEntrega::findOpcoesEntrega($usuarioId, $cidade, $bairro, $cep, $porte, $estado, $subtotal);
        if (!empty($opcoesInternas)) {
            // Se já tiver opções do Melhor Envio, adiciona apenas retirada ou entrega local personalizada
            if (!empty($opcoes)) {
                foreach ($opcoesInternas as $opt) {
                    if ($opt['tipo'] === 'RETIRADA' || !empty($opt['bairro']) || !empty($opt['cidade'])) {
                        $opcoes[] = $opt;
                    }
                }
            } else {
                // Se o Melhor Envio não retornou opções ou está inativo, usa todas as opções internas!
                $opcoes = $opcoesInternas;
            }
        }

        // 3. Aplica regra de Frete Grátis da Loja se atingido ticket mínimo
        $regraEstadual = TaxaEntrega::findRegra($usuarioId, $cidade, $bairro, $cep, $porte, $estado, $subtotal);
        if ($regraEstadual && $regraEstadual->valor_minimo_frete_gratis && $subtotal >= (float)$regraEstadual->valor_minimo_frete_gratis) {
            foreach ($opcoes as &$opcao) {
                if ($opcao['tipo'] !== 'RETIRADA') {
                    $opcao['valor'] = 0.00;
                    $opcao['gratis'] = true;
                    $opcao['aviso_promocional'] = 'Frete Grátis pelo valor do pedido!';
                }
            }
        }

        // 4. Ordenar opções por valor crescente (as mais econômicas primeiro)
        usort($opcoes, function($a, $b) {
            // Retirada na loja sempre no final ou no início conforme preferência (vamos deixar retirada no topo se for grátis)
            return $a['valor'] <=> $b['valor'];
        });

        return $opcoes;
    }
}
