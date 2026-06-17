<?php
namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use app\modules\vendas\models\RegraParcelamento; // Use o namespace correto

/**
 * Controller para cálculos em tempo real da API
 */
class CalculoController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        // Permite acesso sem autenticação (é apenas um cálculo)
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'optional' => ['calcular-parcelas'], 
        ];
        return $behaviors;
    }

    protected function verbs()
    {
        return [
            'calcular-parcelas' => ['GET'],
        ];
    }

    /**
     * Ação para calcular o valor da parcela com base nas regras.
     * GET /api/calculo/calcular-parcelas
     * * @param float $valor_base O valor total dos itens no carrinho
     * @param int $numero_parcelas O número de parcelas desejado
     * @param string $usuario_id O ID do usuário (loja) para buscar as regras corretas
     */
    public function actionCalcularParcelas($valor_base = null, $numero_parcelas = null, $usuario_id = null)
    {
        if ($valor_base === null || $numero_parcelas === null || $usuario_id === null) {
            throw new BadRequestHttpException('Parâmetros valor_base, numero_parcelas e usuario_id são obrigatórios.');
        }

        $valorBase = (float)$valor_base;
        $numParcelas = (int)$numero_parcelas;

        if ($numParcelas <= 1) {
            // À vista, sem acréscimo
            return [
                'numero_parcelas' => 1,
                'valor_parcela' => $valorBase,
                'valor_total_prazo' => $valorBase,
                'acrescimo_percentual' => 0,
            ];
        }

        // Busca a regra de parcelamento aplicável
        $regra = RegraParcelamento::buscarRegraAplicavel($usuario_id, $numParcelas);

        $valorTotalAPrazo = $valorBase;
        $percentualAcrescimo = 0;

        if ($regra) {
            $percentualAcrescimo = (float)$regra->percentual_acrescimo;
            // Usa a função que já existe no modelo RegraParcelamento
            $valorTotalAPrazo = $regra->calcularValorComAcrescimo($valorBase);
        } else {
             // Se não houver regra, não aplica acréscimo
             Yii::warning("Nenhuma regra de parcelamento encontrada para Usuario ID {$usuario_id} e {$numParcelas} parcelas.", 'api');
        }

        $valorParcela = round($valorTotalAPrazo / $numParcelas, 2);

        // Retorna o resultado
        return [
            'numero_parcelas' => $numParcelas,
            'valor_parcela' => $valorParcela,
            'valor_total_prazo' => round($valorTotalAPrazo, 2),
            'acrescimo_percentual' => $percentualAcrescimo,
        ];
    }
}