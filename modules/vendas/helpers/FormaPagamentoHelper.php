<?php
namespace app\modules\vendas\helpers;

use Yii;
use app\modules\vendas\models\FormaPagamento;
use yii\helpers\ArrayHelper;

/**
 * Helper com funções auxiliares para trabalhar com Formas de Pagamento
 */
class FormaPagamentoHelper
{
    /**
     * Retorna array de tipos disponíveis
     * @return array
     */
    public static function getTipos()
    {
        return [
            FormaPagamento::TIPO_DINHEIRO => 'Dinheiro',
            FormaPagamento::TIPO_PIX => 'PIX',
            FormaPagamento::TIPO_CARTAO => 'Cartão',
            FormaPagamento::TIPO_BOLETO => 'Boleto',
        ];
    }

    /**
     * Retorna o label de um tipo
     * @param string $tipo
     * @return string
     */
    public static function getTipoLabel($tipo)
    {
        $tipos = self::getTipos();
        return $tipos[$tipo] ?? $tipo;
    }

    /**
     * Retorna ícone HTML para o tipo
     * @param string $tipo
     * @param string $class
     * @return string
     */
    public static function getIconHtml($tipo, $class = 'w-5 h-5')
    {
        $icons = [
            FormaPagamento::TIPO_DINHEIRO => self::getMoneyIcon($class),
            FormaPagamento::TIPO_PIX => self::getPixIcon($class),
            FormaPagamento::TIPO_CARTAO => self::getCardIcon($class),
            FormaPagamento::TIPO_BOLETO => self::getBoletoIcon($class),
        ];

        return $icons[$tipo] ?? '';
    }

    /**
     * Retorna classe CSS de badge para o tipo
     * @param string $tipo
     * @return string
     */
    public static function getBadgeClass($tipo)
    {
        $classes = [
            FormaPagamento::TIPO_DINHEIRO => 'bg-green-100 text-green-800 border-green-200',
            FormaPagamento::TIPO_PIX => 'bg-blue-100 text-blue-800 border-blue-200',
            FormaPagamento::TIPO_CARTAO => 'bg-purple-100 text-purple-800 border-purple-200',
            FormaPagamento::TIPO_BOLETO => 'bg-orange-100 text-orange-800 border-orange-200',
        ];

        return $classes[$tipo] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }

    /**
     * Retorna cor principal do tipo
     * @param string $tipo
     * @return string
     */
    public static function getColor($tipo)
    {
        $colors = [
            FormaPagamento::TIPO_DINHEIRO => '#10b981', // green
            FormaPagamento::TIPO_PIX => '#3b82f6',      // blue
            FormaPagamento::TIPO_CARTAO => '#8b5cf6',   // purple
            FormaPagamento::TIPO_BOLETO => '#f59e0b',   // orange
        ];

        return $colors[$tipo] ?? '#6b7280'; // gray
    }

    /**
     * Retorna lista de formas ativas para dropdown
     * @param string|null $usuarioId
     * @param string|null $tipo Filtrar por tipo
     * @param bool|null $aceitaParcelamento Filtrar por parcelamento
     * @return array
     */
    public static function getDropdownList($usuarioId = null, $tipo = null, $aceitaParcelamento = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;
        
        $query = FormaPagamento::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if ($tipo !== null) {
            $query->andWhere(['tipo' => $tipo]);
        }

        if ($aceitaParcelamento !== null) {
            $query->andWhere(['aceita_parcelamento' => $aceitaParcelamento]);
        }

        return $query
            ->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['nome' => SORT_ASC])
            ->column();
    }

    /**
     * Retorna estatísticas de uso das formas de pagamento
     * @param string|null $usuarioId
     * @return array
     */
    public static function getStats($usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        $total = FormaPagamento::find()->where(['usuario_id' => $usuarioId])->count();
        $ativas = FormaPagamento::find()->where(['usuario_id' => $usuarioId, 'ativo' => 1])->count();

        $porTipo = FormaPagamento::find()
            ->select(['tipo', 'COUNT(*) as total'])
            ->where(['usuario_id' => $usuarioId])
            ->groupBy('tipo')
            ->asArray()
            ->all();

        return [
            'total' => $total,
            'ativas' => $ativas,
            'inativas' => $total - $ativas,
            'percentual_ativas' => $total > 0 ? round(($ativas / $total) * 100, 1) : 0,
            'por_tipo' => ArrayHelper::map($porTipo, 'tipo', 'total'),
            'com_parcelamento' => FormaPagamento::find()
                ->where(['usuario_id' => $usuarioId, 'aceita_parcelamento' => 1])
                ->count(),
        ];
    }

    /**
     * Valida se uma forma de pagamento pode ser usada
     * @param string $formaId
     * @param string|null $usuarioId
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateUso($formaId, $usuarioId = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        $forma = FormaPagamento::findOne([
            'id' => $formaId,
            'usuario_id' => $usuarioId
        ]);

        if (!$forma) {
            return [
                'valid' => false,
                'message' => 'Forma de pagamento não encontrada ou não pertence ao usuário.'
            ];
        }

        if (!$forma->ativo) {
            return [
                'valid' => false,
                'message' => 'Esta forma de pagamento está inativa.'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Forma de pagamento válida.',
            'forma' => $forma
        ];
    }

    /**
     * Retorna a forma de pagamento padrão do usuário
     * @param string|null $usuarioId
     * @param string|null $tipo
     * @return FormaPagamento|null
     */
    public static function getDefault($usuarioId = null, $tipo = null)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        $query = FormaPagamento::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if ($tipo) {
            $query->andWhere(['tipo' => $tipo]);
        }

        return $query->orderBy(['nome' => SORT_ASC])->one();
    }

    /**
     * Formata uma forma de pagamento para exibição
     * @param FormaPagamento $forma
     * @param bool $showIcon
     * @return string
     */
    public static function format($forma, $showIcon = true)
    {
        if (!$forma) {
            return '-';
        }

        $html = '';
        
        if ($showIcon) {
            $html .= self::getIconHtml($forma->tipo, 'w-4 h-4 inline-block mr-1');
        }

        $html .= \yii\helpers\Html::encode($forma->nome);

        if (!$forma->ativo) {
            $html .= ' <span class="text-xs text-gray-500">(Inativo)</span>';
        }

        return $html;
    }

    /**
     * Verifica se uma forma de pagamento pode ser deletada
     * @param string $formaId
     * @return array ['can_delete' => bool, 'message' => string, 'count' => int]
     */
    public static function canDelete($formaId)
    {
        $forma = FormaPagamento::findOne($formaId);
        
        if (!$forma) {
            return [
                'can_delete' => false,
                'message' => 'Forma de pagamento não encontrada.',
                'count' => 0
            ];
        }

        $parcelasCount = $forma->getParcelas()->count();

        if ($parcelasCount > 0) {
            return [
                'can_delete' => false,
                'message' => "Não é possível excluir. Existem {$parcelasCount} parcela(s) associada(s).",
                'count' => $parcelasCount
            ];
        }

        return [
            'can_delete' => true,
            'message' => 'Esta forma de pagamento pode ser excluída.',
            'count' => 0
        ];
    }

    /**
     * Cria uma forma de pagamento padrão para um usuário
     * @param string $usuarioId
     * @return bool
     */
    public static function createDefaults($usuarioId)
    {
        $defaults = [
            ['nome' => 'Dinheiro', 'tipo' => FormaPagamento::TIPO_DINHEIRO, 'aceita_parcelamento' => false],
            ['nome' => 'PIX', 'tipo' => FormaPagamento::TIPO_PIX, 'aceita_parcelamento' => false],
            ['nome' => 'Cartão de Crédito', 'tipo' => FormaPagamento::TIPO_CARTAO, 'aceita_parcelamento' => true],
            ['nome' => 'Cartão de Débito', 'tipo' => FormaPagamento::TIPO_CARTAO, 'aceita_parcelamento' => false],
        ];

        foreach ($defaults as $data) {
            $forma = new FormaPagamento();
            $forma->usuario_id = $usuarioId;
            $forma->nome = $data['nome'];
            $forma->tipo = $data['tipo'];
            $forma->ativo = true;
            $forma->aceita_parcelamento = $data['aceita_parcelamento'];
            
            if (!$forma->save()) {
                Yii::error('Erro ao criar forma padrão: ' . json_encode($forma->errors), __METHOD__);
                return false;
            }
        }

        return true;
    }

    // ========== Ícones SVG ==========

    protected static function getMoneyIcon($class)
    {
        return '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
    }

    protected static function getPixIcon($class)
    {
        return '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>';
    }

    protected static function getCardIcon($class)
    {
        return '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>';
    }

    protected static function getBoletoIcon($class)
    {
        return '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
    }
}