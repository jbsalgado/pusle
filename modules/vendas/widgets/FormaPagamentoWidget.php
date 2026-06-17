<?php
namespace app\modules\vendas\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\vendas\models\FormaPagamento;

/**
 * Widget para exibir e selecionar formas de pagamento
 * 
 * Uso:
 * <?= FormaPagamentoWidget::widget([
 *     'mode' => 'select', // select, cards, list, badges
 *     'name' => 'forma_pagamento_id',
 *     'value' => $model->forma_pagamento_id,
 *     'onlyActive' => true,
 *     'showIcons' => true,
 *     'allowParcelamento' => false,
 * ]) ?>
 */
class FormaPagamentoWidget extends Widget
{
    /**
     * @var string Modo de exibição: select, cards, list, badges, dropdown
     */
    public $mode = 'select';

    /**
     * @var string Nome do input
     */
    public $name = 'forma_pagamento_id';

    /**
     * @var mixed Valor selecionado
     */
    public $value = null;

    /**
     * @var string ID do usuário (se não informado, usa o usuário logado)
     */
    public $usuarioId = null;

    /**
     * @var bool Mostrar apenas formas ativas
     */
    public $onlyActive = true;

    /**
     * @var bool Filtrar apenas formas que aceitam parcelamento
     */
    public $allowParcelamento = null;

    /**
     * @var string Tipo específico (DINHEIRO, PIX, CARTAO, BOLETO)
     */
    public $tipo = null;

    /**
     * @var bool Mostrar ícones
     */
    public $showIcons = true;

    /**
     * @var array Opções HTML adicionais
     */
    public $options = [];

    /**
     * @var bool Permitir criar nova forma (mostra link)
     */
    public $allowCreate = false;

    /**
     * @var string Classe CSS customizada
     */
    public $cssClass = '';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        if ($this->usuarioId === null) {
            $this->usuarioId = Yii::$app->user->id;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $formas = $this->getFormasPagamento();

        switch ($this->mode) {
            case 'cards':
                return $this->renderCards($formas);
            case 'list':
                return $this->renderList($formas);
            case 'badges':
                return $this->renderBadges($formas);
            case 'dropdown':
                return $this->renderDropdown($formas);
            case 'select':
            default:
                return $this->renderSelect($formas);
        }
    }

    /**
     * Busca as formas de pagamento
     */
    protected function getFormasPagamento()
    {
        $query = FormaPagamento::find()
            ->where(['usuario_id' => $this->usuarioId]);

        if ($this->onlyActive) {
            $query->andWhere(['ativo' => true]);
        }

        if ($this->allowParcelamento !== null) {
            $query->andWhere(['aceita_parcelamento' => $this->allowParcelamento]);
        }

        if ($this->tipo !== null) {
            $query->andWhere(['tipo' => $this->tipo]);
        }

        return $query->orderBy(['nome' => SORT_ASC])->all();
    }

    /**
     * Renderiza como select dropdown padrão
     */
    protected function renderSelect($formas)
    {
        $items = [];
        foreach ($formas as $forma) {
            $label = $forma->nome;
            if ($this->showIcons) {
                $label = $this->getIcon($forma->tipo) . ' ' . $label;
            }
            $items[$forma->id] = $label;
        }

        $options = array_merge([
            'class' => 'form-control ' . $this->cssClass,
            'prompt' => 'Selecione uma forma de pagamento',
        ], $this->options);

        $html = Html::dropDownList($this->name, $this->value, $items, $options);

        if ($this->allowCreate) {
            $html .= ' ' . Html::a('+ Nova', Url::to(['/vendas/forma-pagamento/create']), [
                'class' => 'btn btn-sm btn-outline-primary',
                'target' => '_blank'
            ]);
        }

        return $html;
    }

    /**
     * Renderiza como cards selecionáveis
     */
    protected function renderCards($formas)
    {
        $html = '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">';
        
        foreach ($formas as $forma) {
            $isSelected = $this->value == $forma->id;
            $selectedClass = $isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-white';
            
            $html .= '<label class="cursor-pointer">';
            $html .= Html::radio($this->name, $isSelected, [
                'value' => $forma->id,
                'class' => 'sr-only peer'
            ]);
            $html .= '<div class="' . $selectedClass . ' peer-checked:border-blue-500 peer-checked:bg-blue-50 border-2 rounded-lg p-4 hover:border-blue-300 transition-all">';
            
            if ($this->showIcons) {
                $html .= '<div class="text-center mb-2">' . $this->getIcon($forma->tipo, 'w-8 h-8 mx-auto') . '</div>';
            }
            
            $html .= '<div class="text-center font-medium text-sm">' . Html::encode($forma->nome) . '</div>';
            $html .= '<div class="text-center text-xs text-gray-500 mt-1">' . $forma->tipo . '</div>';
            $html .= '</div>';
            $html .= '</label>';
        }
        
        $html .= '</div>';

        if ($this->allowCreate) {
            $html .= '<div class="mt-3 text-center">';
            $html .= Html::a('+ Criar Nova Forma de Pagamento', Url::to(['/vendas/forma-pagamento/create']), [
                'class' => 'text-blue-600 hover:text-blue-800 text-sm font-medium',
                'target' => '_blank'
            ]);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Renderiza como lista simples
     */
    protected function renderList($formas)
    {
        $html = '<div class="space-y-2">';
        
        foreach ($formas as $forma) {
            $html .= '<div class="flex items-center p-3 border rounded-lg hover:bg-gray-50">';
            
            if ($this->showIcons) {
                $html .= '<div class="mr-3">' . $this->getIcon($forma->tipo, 'w-6 h-6') . '</div>';
            }
            
            $html .= '<div class="flex-1">';
            $html .= '<div class="font-medium">' . Html::encode($forma->nome) . '</div>';
            $html .= '<div class="text-sm text-gray-500">' . $forma->tipo;
            
            if ($forma->aceita_parcelamento) {
                $html .= ' • Aceita Parcelamento';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderiza como badges
     */
    protected function renderBadges($formas)
    {
        $html = '<div class="flex flex-wrap gap-2">';
        
        foreach ($formas as $forma) {
            $badgeClass = $this->getBadgeClass($forma->tipo);
            
            $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ' . $badgeClass . '">';
            
            if ($this->showIcons) {
                $html .= $this->getIcon($forma->tipo, 'w-4 h-4 mr-1');
            }
            
            $html .= Html::encode($forma->nome);
            $html .= '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Renderiza como dropdown interativo (requer JS)
     */
    protected function renderDropdown($formas)
    {
        $dropdownId = 'forma-dropdown-' . uniqid();
        
        $html = '<div class="relative" x-data="{ open: false }">';
        $html .= '<button @click="open = !open" type="button" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2 text-left flex items-center justify-between">';
        $html .= '<span>Selecionar forma de pagamento</span>';
        $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
        $html .= '</button>';
        
        $html .= '<div x-show="open" @click.away="open = false" class="absolute z-10 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-auto">';
        
        foreach ($formas as $forma) {
            $html .= '<button type="button" class="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center" onclick="selectForma(\'' . $forma->id . '\', \'' . Html::encode($forma->nome) . '\')">';
            
            if ($this->showIcons) {
                $html .= $this->getIcon($forma->tipo, 'w-5 h-5 mr-3');
            }
            
            $html .= '<span>' . Html::encode($forma->nome) . '</span>';
            $html .= '</button>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= Html::hiddenInput($this->name, $this->value);
        
        return $html;
    }

    /**
     * Retorna o ícone SVG para o tipo
     */
    protected function getIcon($tipo, $class = 'w-5 h-5')
    {
        $icons = [
            'DINHEIRO' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
            'PIX' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>',
            'CARTAO' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>',
            'BOLETO' => '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
        ];

        return $icons[$tipo] ?? '';
    }

    /**
     * Retorna classe CSS para badge
     */
    protected function getBadgeClass($tipo)
    {
        $classes = [
            'DINHEIRO' => 'bg-green-100 text-green-800',
            'PIX' => 'bg-blue-100 text-blue-800',
            'CARTAO' => 'bg-purple-100 text-purple-800',
            'BOLETO' => 'bg-orange-100 text-orange-800',
        ];

        return $classes[$tipo] ?? 'bg-gray-100 text-gray-800';
    }
}