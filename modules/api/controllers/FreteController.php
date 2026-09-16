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
     * Resolução instantânea e offline de Estado (UF) por faixa de CEP
     */
    public static function detectarEstadoPorCep($cep): ?string
    {
        $limpo = (int)substr(preg_replace('/\D/', '', (string)$cep), 0, 5);
        if ($limpo >= 1000 && $limpo <= 19999) return 'SP';
        if ($limpo >= 20000 && $limpo <= 28999) return 'RJ';
        if ($limpo >= 29000 && $limpo <= 29999) return 'ES';
        if ($limpo >= 30000 && $limpo <= 39999) return 'MG';
        if ($limpo >= 40000 && $limpo <= 48999) return 'BA';
        if ($limpo >= 49000 && $limpo <= 49999) return 'SE';
        if ($limpo >= 50000 && $limpo <= 56999) return 'PE';
        if ($limpo >= 57000 && $limpo <= 57999) return 'AL';
        if ($limpo >= 58000 && $limpo <= 58999) return 'PB';
        if ($limpo >= 59000 && $limpo <= 59999) return 'RN';
        if ($limpo >= 60000 && $limpo <= 63999) return 'CE';
        if ($limpo >= 64000 && $limpo <= 64999) return 'PI';
        if ($limpo >= 65000 && $limpo <= 65999) return 'MA';
        if ($limpo >= 66000 && $limpo <= 68899) return 'PA';
        if ($limpo >= 68900 && $limpo <= 68999) return 'AP';
        if ($limpo >= 69000 && $limpo <= 69299) return 'AM';
        if ($limpo >= 69300 && $limpo <= 69399) return 'RR';
        if ($limpo >= 69400 && $limpo <= 69899) return 'AM';
        if ($limpo >= 69900 && $limpo <= 69999) return 'AC';
        if ($limpo >= 70000 && $limpo <= 72799) return 'DF';
        if ($limpo >= 72800 && $limpo <= 72999) return 'GO';
        if ($limpo >= 73000 && $limpo <= 76799) return 'GO';
        if ($limpo >= 76800 && $limpo <= 76999) return 'RO';
        if ($limpo >= 77000 && $limpo <= 77999) return 'TO';
        if ($limpo >= 78000 && $limpo <= 78899) return 'MT';
        if ($limpo >= 79000 && $limpo <= 79999) return 'MS';
        if ($limpo >= 80000 && $limpo <= 87999) return 'PR';
        if ($limpo >= 88000 && $limpo <= 89999) return 'SC';
        if ($limpo >= 90000 && $limpo <= 99999) return 'RS';
        return null;
    }

    /**
     * Motor central de cálculo: Combina Melhor Envio com Solução Interna garantindo fail-safe
     */
    private function obterOpcoesCalculadas($usuarioId, $cep, $bairro, $cidade, $estado, $subtotal, $porte): array
    {
        $opcoes = [];

        // Auto-detecção de UF caso não tenha sido enviada ou ViaCEP tenha falhado
        if (empty($estado) && !empty($cep)) {
            $estado = self::detectarEstadoPorCep($cep);
        }

        // 1. Tentar cotação com Melhor Envio se houver token (próprio da loja ou global da plataforma)
        $tokenGlobal = trim(getenv('MELHOR_ENVIO_GLOBAL_TOKEN') ?: ($_ENV['MELHOR_ENVIO_GLOBAL_TOKEN'] ?? ''));
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $usuarioId]);
        $temToken = ($lojaConfig && !empty($lojaConfig->melhor_envio_token)) || !empty($tokenGlobal);
        $podeCotar = $lojaConfig && $temToken && !empty($cep) && ($lojaConfig->melhor_envio_ativo || !empty($tokenGlobal));

        if ($podeCotar) {
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
        if ($regraEstadual && $regraEstadual->valor_minimo_frete_gratis) {
            $minGratis = (float)$regraEstadual->valor_minimo_frete_gratis;
            $ganhouFreteGratis = $subtotal >= $minGratis;

            foreach ($opcoes as &$opcao) {
                if ($opcao['tipo'] !== 'RETIRADA') {
                    $opcao['valor_minimo_frete_gratis'] = $minGratis;
                    if ($ganhouFreteGratis) {
                        $valorOriginal = (float)($opcao['valor_original'] ?? $opcao['valor']);
                        if ($valorOriginal <= 0) {
                            $valorOriginal = (float)$regraEstadual->valor;
                        }
                        $opcao['valor_original'] = $valorOriginal;
                        $opcao['valor'] = 0.00;
                        $opcao['gratis'] = true;
                        $opcao['motivo_gratis'] = "Frete Grátis por valor de compra (pedidos acima de R$ " . number_format($minGratis, 2, ',', '.') . ")";
                        $opcao['aviso_promocional'] = "🎉 Frete Grátis por valor de compra • Economizou R$ " . number_format($valorOriginal, 2, ',', '.');
                        $opcao['economia'] = $valorOriginal;
                    } else {
                        $faltaParaGratis = $minGratis - $subtotal;
                        $opcao['falta_para_frete_gratis'] = $faltaParaGratis;
                        $opcao['aviso_promocional'] = "Faltam R$ " . number_format($faltaParaGratis, 2, ',', '.') . " para ganhar Frete Grátis!";
                    }
                }
            }
        }

        // 4. Ordenar opções por valor crescente (as mais econômicas primeiro)
        usort($opcoes, function($a, $b) {
            return $a['valor'] <=> $b['valor'];
        });

        return $opcoes;
    }
}
