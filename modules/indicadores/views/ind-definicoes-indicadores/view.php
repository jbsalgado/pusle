<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\modules\indicadores\models\IndDefinicoesIndicadores */
?>
<div class="ind-definicoes-indicadores-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_indicador',
            'cod_indicador',
            'nome_indicador',
            'descricao_completa:ntext',
            'conceito:ntext',
            'justificativa:ntext',
            'metodo_calculo:ntext',
            'interpretacao:ntext',
            'limitacoes:ntext',
            'observacoes_gerais:ntext',
            'id_dimensao',
            'id_unidade_medida',
            'id_periodicidade_ideal_medicao',
            'id_periodicidade_ideal_divulgacao',
            'id_fonte_padrao',
            'tipo_especifico',
            'polaridade',
            'data_inicio_validade',
            'data_fim_validade',
            'responsavel_tecnico',
            'nota_tecnica_url:url',
            'palavras_chave:ntext',
            'versao',
            'ativo:boolean',
            'data_criacao',
            'data_atualizacao',
        ],
    ]) ?>

</div>
