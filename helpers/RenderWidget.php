<?php

namespace app\helpers;

use kartik\select2\Select2;
use kartik\widgets\DepDrop;
use yii\web\JsExpression;
use kartik\detail\DetailView;
use common\models\Cgm;
use yii\helpers\Html;
use yii\bootstrap\NavBar;
use yii\bootstrap\Nav;
use Yii;
use dmstr\widgets\Menu;
use kartik\grid\GridView;
use yii\data\ArrayDataProvider;
use yii\helpers\Url;
use common\helpers\ConfigValues;

//use kartik\widgets\Select2;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RenderView
 *
 * @author Júnior Pires
 */
class RenderWidget
{
    public static function select2($form, $params)
    {
        if (!isset($params['options'])) {
            $params['options'] = [];
        }

        if (!isset($params['pluginOptions'])) {
            $params['pluginOptions'] = [];
        }

        if (!isset($params['placeholder'])) {
            $params['placeholder'] = 'selecione uma opção';
        }



        $params['options']['placeholder'] = $params['placeholder'];




        if (isset($params['label'])) {
            if ($params['label'] == false) {
                return $form->field($params['model'], $params['attribute'])->label(false)->widget(Select2::classname(), [
                    'data' => $params['data'], 'options' => $params['options']
                ]);
            }
        }

        return $form->field($params['model'], $params['attribute'])->widget(Select2::classname(), [
            'data' => $params['data'], 'options' => $params['options'],
            'pluginOptions' => $params['pluginOptions'],
        ]);
    }

    public static function select2Ajax($form, $params)
    {
        if (!isset($params['placeholder'])) {
            $params['placeholder'] = 'selecione uma opção';
        }
        if (!isset($params['params'])) {
            $params['params'] = '';
        } else {
            $params['params'] = ',' . $params['params'];
        }

        return $form->field($params['model'], $params['attribute'])->label(false)->widget(Select2::classname(), [
            'options' => ['placeholder' => $params['placeholder']],
            'pluginOptions' => [
                'allowClear' => true,
                //'minimumInputLength' => 3,
                'language' => [
                    'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                ],
                'ajax' => [
                    'url' => $params['url'],
                    'dataType' => 'json',
                    'data' => new JsExpression('function(params) { return {q:params.term' . $params['params'] . '}; }')
                ],
                'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                'templateResult' => new JsExpression('function(city) { return city.text; }'),
                'templateSelection' => new JsExpression('function (city) { return city.text; }'),
            ],
        ]);
    }

    public static function select2Child($form, $params)
    {
        if (!isset($params['data'])) {
            $params['data'] = array();
        }

        if (!isset($params['placeholder'])) {
            $params['placeholder'] = 'selecione uma opção';
        }

        if (!isset($params['label'])) {
            $params['label'] = null;
        }

        return $form->field($params['model'], $params['attribute'])->label($params['label'])->widget(DepDrop::classname(), [
            'data' => $params['data'],
            'options' => ['placeholder' => $params['placeholder']],
            'type' => DepDrop::TYPE_SELECT2,
            'select2Options' => ['pluginOptions' => ['allowClear' => true]],
            'pluginOptions' => [
                'placeholder' => $params['placeholder'],
                'depends' => $params['dependents'],
                'url' => $params['url'],
                'loadingText' => 'Buscando...',
            ],
        ]);
    }

    public static function select2ChildMultSelect($form, $params)
    {
        if (!isset($params['data'])) {
            $params['data'] = array();
        }

        if (!isset($params['placeholder'])) {
            $params['placeholder'] = 'selecione uma opção';
        }

        if (!isset($params['label'])) {
            $params['label'] = null;
        }

        if (!isset($params['multiple'])) {
            $params['multiple'] = false;
        }

        return $form->field($params['model'], $params['attribute'])->label($params['label'])->widget(DepDrop::classname(), [
            'data' => $params['data'],
            'options' => [
                'placeholder' => $params['placeholder'],
                'multiple' => $params['multiple']
            ],
            'type' => DepDrop::TYPE_SELECT2,
            'select2Options' => ['pluginOptions' => ['allowClear' => true]],
            'pluginOptions' => [
                'placeholder' => $params['placeholder'],
                'depends' => $params['dependents'],
                'url' => $params['url'],
                'loadingText' => 'Buscando...',
            ],
        ]);
    }

    public static function depDropChild($form, $params)
    {
        if (!isset($params['placeholder'])) {
            $params['placeholder'] = 'selecione uma opção';
        }

        return $form->field($params['model'], $params['attribute'])->widget(DepDrop::classname(), [
            //'options' => ['placeholder' => 'Selecione uma opção'],
            'pluginOptions' => [
                'placeholder' => $params['placeholder'],
                'depends' => $params['dependents'],
                'url' => $params['url'],
                'loadingText' => 'Buscando...',
            ]
        ]);
    }

    /**
     *
     * @param string|array $attributes
     * @param string $message
     * @return string
     */
    public static function badgeError($model, $attributes = null, $message = null)
    {
        $error = '<div class="badge badge-important"> ' . (is_string($message) ? $message : 'Erros!') . '</div>';

        if (!is_array($model)) {
            //vai gerar de acordo com os atributos
            if ($attributes != null) {
                if (is_string($attributes)) {
                    return $model->hasErrors($attributes) ? $error : '';
                } elseif (is_array($attributes)) {
                    foreach ($attributes as $atr) {
                        if ($model->hasErrors($atr)) {
                            return $error;
                        }
                    }
                }
            } else {
                return $model->hasErrors() ? $error : '';
            }
        } else {
            if (!empty($model)) {
                foreach ($model as $m) {
                    return $m->hasErrors() ? $error : '';
                }
            }
            return;
        }
    }

    public static function EstudanteCursosView($list)
    {
        $attributes = [];


        foreach ($list as $c) {
            $cAtributtes = [
                [
                    'group' => true,
                    'label' => $c->instituicaoCurso->curso->nome,
                    'rowOptions' => ['class' => 'info']
                ],
                [
                    'label' => 'Instiuição',
                    'value' => $c->cgm->pj_nome_fantasia,
                ],
                [
                    'label' => 'Matrícula',
                    'value' => $c->matricula,
                ],
                [
                    'label' => 'Período atual',
                    'value' => $c->periodo_atual,
                ],
                [
                    'label' => 'Status',
                    'value' => Html::a($c->getStatus($c->status), ['estudante-curso/status', 'id' => $c->id,], ['role' => 'modal-remote']),
                    'format' => 'raw'
                ],
            ];

            $attributes = array_merge($attributes, $cAtributtes);
        }

        return DetailView::widget([
            'id' => 'crud-datatable',
            'model' => new \frontend\modules\nep\models\Estudante(),
            //'condensed'=>true,
            'hover' => true,
            'mode' => DetailView::MODE_VIEW,
            'enableEditMode' => false,
            'panel' => [
                'heading' => 'CURSOS',
                'type' => 'default',
            ],
            'attributes' => $attributes,
        ]);
    }

    public static function EstagiosView($list)
    {
        $attributes = [];

        foreach ($list as $c) {
            foreach ($c->estagios as $estagio) {
                $cAtributtes = [
                    [
                        'group' => true,
                        //'label' => $c->instituicaoCurso->curso->nome,
                        'label' => Html::a($c->instituicaoCurso->curso->nome, ['estagio/update', 'id' => $estagio->id], ['role' => 'modal-remote', 'title' => 'Atualizar Estágio']),
                        'rowOptions' => ['class' => 'info']
                    ],
                    [
                        //'group' => true,
                        //'label' => $c->instituicaoCurso->curso->nome,
                        'attribute' => 'id',
                        'label' => "Editar",
                        'value' => Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['estagio/update', 'id' => $estagio->id], ['role' => 'modal-remote', 'title' => 'Atualizar Estágio']),
                        'format' => 'raw',
                        'rowOptions' => ['class' => 'warning']
                    ],
                    [
                        'label' => 'Tipo de estágio',
                        'value' => $estagio->tipo->nome,
                    ],
                    [
                        'label' => 'Unidade',
                        'value' => $estagio->unidade->nome,
                    ],
                    [
                        'label' => 'Início',
                        'value' => Yii::$app->formatter->asDate($estagio->inicio, "php:d/m/Y"),
                        'format' => 'raw'
                    ],
                    [
                        'label' => 'Dias Alternados',
                        'value' => $estagio->getIntervaloDias(),
                        'format' => 'raw'
                    ],
                    [
                        'label' => 'Fim',
                        'value' => Yii::$app->formatter->asDate($estagio->fim, "php:d/m/Y"),
                        'format' => 'raw'
                    ],
                    [
                        'label' => 'Número da apólice de seguro',
                        'value' => $estagio->numero_apolice_seguro,
                    ],
                    [
                        'label' => 'Validade da apólice',
                        'value' => Yii::$app->formatter->asDate($estagio->validade_apolice_seguro, "php:d/m/Y"),
                        'format' => 'raw'
                    ],
                    [
                        'label' => 'Status',
                        'value' => $estagio->getStatusName(),
                    ],
                ];

                $attributes = array_merge($attributes, $cAtributtes);
            }
        }

        return DetailView::widget([
            'model' => new \frontend\modules\nep\models\Estagio(),
            'hover' => true,
            'mode' => DetailView::MODE_VIEW,
            'enableEditMode' => false,
            'panel' => [
                'heading' => 'Estágios',
                'type' => 'default',
            ],
            'attributes' => $attributes,
        ]);
    }

    public static function CgmView($model)
    {
        $pessoaFisica = $model->isPessoaFisica();
        $pessoaJuridica = $model->isPessoaJuridica();

        $attributes = [
            [
                'group' => true,
                'label' => 'DADOS GERAIS',
                'rowOptions' => ['class' => 'info sys-default']
            ],
            'id',
            [
                'label' => isset($model->tipo_documento_id) ? \common\models\TipoDocumento::getList()[$model->tipo_documento_id] : ' Documento',
                'value' => $model->documento,
                'visible' => $pessoaFisica,
            ],
            [
                'label' => 'CNPJ',
                'value' => Yii::$app->formatter->asCpfCnpj($model->documento),
                'visible' => $pessoaJuridica,
            ],
            [
                'attribute' => 'nome',
                'value' => $model->nome,
                'visible' => $pessoaFisica,
            ],
            [
                'attribute' => 'nome_mae',
                'value' => $model->nome_mae,
                'visible' => $pessoaFisica,
            ],
            [
                'attribute' => 'pj_razao_social',
                'value' => $model->pj_razao_social,
                'visible' => $pessoaJuridica,
            ],
            [
                'attribute' => 'pj_nome_fantasia',
                'value' => $model->pj_nome_fantasia,
                'visible' => $pessoaJuridica,
            ],
            [
                'columns' => [
                    [
                        'attribute' => 'data_nascimento',
                        'value' => Yii::$app->formatter->asDatetime($model->data_nascimento, "php:d/m/Y"),
                        'visible' => $pessoaFisica,
                    ],
                ]
            ],
        ];

        if ($model->endereco) {
            $endereco = [
                [
                    'group' => true,
                    'label' => 'ENDEREÇO',
                    'rowOptions' => ['class' => 'info']
                ], [
                    'columns' => [
                        [
                            'label' => 'Logradouro',
                            'value' => $model->endereco->logradouro,
                        ],
                        [
                            'label' => 'Número',
                            'value' => $model->endereco->numero,
                        ],
                    ]
                ],
                [
                    'columns' => [
                        [
                            'label' => 'CEP',
                            'value' => $model->endereco->cep != null ? Yii::$app->formatter->asCep($model->endereco->cep) : "",
                        ],
                        [
                            'label' => 'Complemento',
                            'value' => $model->endereco->complemento,
                        ],
                    ]
                ],
                [
                    'columns' => [
                        [
                            'label' => 'Bairro',
                            'value' => $model->endereco->bairro != null ? $model->endereco->bairro->nome : "",
                        ],
                        [
                            'label' => 'Cidade',
                            'value' => $model->endereco->bairro != null ? $model->endereco->bairro->cidade->nome : "",
                        ],
                        [
                            'label' => 'Estado',
                            'value' => $model->endereco->bairro != null ? $model->endereco->bairro->cidade->estado->nome : "",
                        ],
                    ]
                ]
            ];
            $attributes = array_merge($attributes, $endereco);
        }

        if (count($model->contatos) > 0) {
            $attributes[] = [
                'group' => true,
                'label' => 'CONTATOS',
                'rowOptions' => ['class' => 'info']
            ];

            foreach ($model->contatos as $c) {
                $attributes[] = [
                    'label' => $c->tipoContato->nome,
                    'value' => $c->nome,
                ];
            }
        }
        return DetailView::widget([
            'model' => $model,
            //'condensed'=>true,
            'hover' => true,
            'mode' => DetailView::MODE_VIEW,
            'enableEditMode' => false,
            'panel' => [
                'heading' => 'CGM',
                'type' => 'default',
            ],
            'attributes' => $attributes,
        ]);
    }

    public static function CursosList($list)
    {
        $dataProvider = new ArrayDataProvider([
            'allModels' => $list,
            //            'sort' => [
            //                 'attributes' => ['id'],
            //            ],
        ]);

        $columns = [
            [
                'attribute' => 'curso.nome',
                'label' => 'Cursos',
                'format' => 'raw',
            ],
            [
                'attribute' => 'coordenador.nome',
                'label' => 'Coordenador',
                'format' => 'raw',
            ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'dropdown' => false,
                'vAlign' => 'middle',
                'template' => '{view} {update} {delete}',
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::to(['curso-' . $action, 'id' => $model->id]);
                },
                'viewOptions' => ['role' => 'modal-remote', 'title' => 'Visualizar', 'data-toggle' => 'tooltip'],
                'updateOptions' => ['role' => 'modal-remote', 'title' => 'Atualizar', 'data-toggle' => 'tooltip'],
                'deleteOptions' => [
                    'role' => 'modal-remote', 'title' => 'Excluir',
                    'data-confirm' => false, 'data-method' => false, // for overide yii data api
                    'data-request-method' => 'post',
                    'data-toggle' => 'tooltip',
                    'data-confirm-title' => 'Tem certeza ?',
                    'data-confirm-message' => 'Você tem certeza que deseja excluir esse item ?'
                ],
            ],
        ];



        return GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => $columns,
            'summary' => '',
        ]);
    }

    public static function NotasEntradasViewRelatorio(array $listNotaEntrada, $consolidado)
    {
        for ($i = 0; $i < count($listNotaEntrada); $i++) {
            $attributes = [
                [
                    'label' => 'Fornecedor',
                    'value' => $listNotaEntrada[$i]->fornecedor->cgm->pj_razao_social,
                ],
                [
                    'label' => 'Nº da NF',
                    'value' => $listNotaEntrada[$i]->numero_nota,
                ],
                [
                    'label' => 'Nº da OF',
                    'value' => $listNotaEntrada[$i]->ordem_fornecimento,
                ],
                [
                    'label' => 'Data de Emissão',
                    'value' => Yii::$app->formatter->asDatetime($listNotaEntrada[$i]->data_emissao, "php:d/m/Y"),
                ],
                [
                    'attribute' => 'data_entrega',
                    'value' => Yii::$app->formatter->asDatetime($listNotaEntrada[$i]->data_entrega, "php:d/m/Y"),
                ],
                [
                    'attribute' => 'valor_total',
                    //'value' => Yii::$app->formatter->asMoedaReal($listNotaEntrada[$i]->valor_total),
                    'format' => ['decimal', 2],
                ],
            ];







            echo DetailView::widget([
                'model' => $listNotaEntrada[$i],
                //'condensed'=>true,
                'hover' => true,
                'mode' => DetailView::MODE_VIEW,
                'enableEditMode' => false,
                'panel' => [
                    'heading' => '',
                    'type' => 'default',
                ],
                'attributes' => $attributes,
            ]);


            if (!empty($listNotaEntrada[$i]->itens)) {
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $listNotaEntrada[$i]->itens,
                    'pagination' => false,
                    //            'sort' => [
                    //                 'attributes' => ['id'],
                    //            ],
                ]);

                $columns = [
                    [
                        'label' => 'Produto',
                        'attribute' => 'est_produto.nome',
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Quantidade',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asBrNumero($model->quantidade);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Val. Unitário',
                        'attribute' => 'valor_unitario',
                        //'value' => function($model) {
                        //    return Yii::$app->formatter->asMoedaReal($model->valor_unitario);
                        //},
                        //'format' => 'raw',
                        'format' => ['decimal', 2],
                    ],
                    [
                        'label' => 'Val. Total',
                        'attribute' => 'valor_total',
                        //                        'value' => function($model) {
                        //                            return Yii::$app->formatter->asMoedaReal($model->valor_total);
                        //                        },
                        //                        'format' => 'raw',
                        'format' => ['decimal', 2],
                    ],
                ];



                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => $columns,
                    'summary' => '',
                ]);
            }
            if ($i < (count($listNotaEntrada) - 1)) {
                echo DetailView::widget([
                    'model' => $listNotaEntrada[$i],
                    //'condensed'=>true,
                    'hover' => true,
                    'mode' => DetailView::MODE_VIEW,
                    'enableEditMode' => false,
                    'panel' => [
                        'heading' => '',
                        'type' => 'default',
                    ],
                    'attributes' => $attributes,
                ]);

                echo "<pagebreak>";
            }
        }
        echo "<pagebreak>";
        $columns = [
            [
                'label' => 'Fornecedor',
                'attribute' => 'origem',
                'format' => 'raw',
                'pageSummary' => '<b>Total</b>',
            ],
            [
                'label' => 'Valor Total',
                'value' => 'valor_total',
                'format' => 'raw',
                'pageSummary' => true,
                'pageSummaryFunc' => GridView::F_SUM,
            ],
        ];

        echo GridView::widget([
            'dataProvider' => $consolidado,
            'autoXlFormat' => true,
            'showPageSummary' => true,
            'summary' => false,
            'columns' => $columns,
            'summary' => '',
            'toolbar' => false,
            'panel' => [
                'type' => 'default',
                'heading' => "<b>CONSOLIDADO</b>",
            ]
        ]);
    }

    public static function NotasSaidasViewRelatorio(array $listNotaSaida, $consolidado)
    {
        for ($i = 0; $i < count($listNotaSaida); $i++) {
            $attributes = [
                [
                    'label' => 'Unidade de destino',
                    'value' => $listNotaSaida[$i]->estabelecimento->nome,
                ],
                [
                    'label' => 'Nº do pedido',
                    'value' => $listNotaSaida[$i]->est_pedido_establecimento_id,
                ],
                [
                    'label' => 'Data',
                    'value' => Yii::$app->formatter->asDatetime($listNotaSaida[$i]->updated_at, "php:d/m/Y"),
                ],
                [
                    'attribute' => 'valor_total',
                    //'value' => Yii::$app->formatter->asMoedaReal($listNotaSaida[$i]->valor_total),
                    'format' => ['decimal', 2],
                ],
            ];







            echo DetailView::widget([
                'model' => $listNotaSaida[$i],
                //'condensed'=>true,
                'hover' => true,
                'mode' => DetailView::MODE_VIEW,
                'enableEditMode' => false,
                'panel' => [
                    'heading' => '',
                    'type' => 'default',
                ],
                'attributes' => $attributes,
            ]);


            if (!empty($listNotaSaida[$i]->itens)) {
                $dataProvider = new ArrayDataProvider([
                    'allModels' => $listNotaSaida[$i]->itens,
                    'pagination' => false,
                    //            'sort' => [
                    //                 'attributes' => ['id'],
                    //            ],
                ]);

                $columns = [
                    [
                        'label' => 'Produto',
                        'attribute' => 'est_produto.nome',
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Quantidade Solic.',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asBrNumero($model->quantidade_solicitada);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Quantidade Atend.',
                        'value' => function ($model) {
                            return Yii::$app->formatter->asBrNumero($model->quantidade_atendida);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'label' => 'Val. Unitário',
                        'attribute' => 'valor_unitario',
                        //'value' => function($model) {
                        //    return Yii::$app->formatter->asMoedaReal($model->valor_unitario);
                        //},
                        //'format' => 'raw',
                        'format' => ['decimal', 2],
                    ],
                    [
                        'label' => 'Val. Total',
                        'attribute' => 'valor_total',
                        //'value' => function($model) {
                        //    return Yii::$app->formatter->asMoedaReal($model->valor_total);
                        //},
                        //'format' => 'raw',
                        'format' => ['decimal', 2],
                    ],
                    [
                        'label' => 'Observação',
                        'attribute' => 'observacao',
                        'format' => 'raw',
                    ],
                ];



                echo GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => $columns,
                    'summary' => '',
                ]);
            }
            if ($i < (count($listNotaSaida) - 1)) {
                echo "<pagebreak>";
            }
        }

        echo "<pagebreak>";
        echo GridView::widget([
            'dataProvider' => $consolidado,
            'autoXlFormat' => true,
            'showPageSummary' => true,
            'summary' => false,
            'columns' => [
                [
                    'label' => 'Fornecedor',
                    'attribute' => 'destino',
                    'format' => 'raw',
                    'pageSummary' => 'Total',
                ],
                [
                    'label' => 'Valor Total',
                    'value' => 'valor',
                    'format' => ['decimal', ConfigValues::CASAS_DECIMAIS],
                    'pageSummary' => true,
                    'pageSummaryFunc' => GridView::F_SUM,
                ],
            ],
            'summary' => '',
            'panel' => [
                'type' => 'default',
                'heading' => "<b>CONSOLIDADO</b>",
            ]
        ]);
    }

    public static function navModel($title, $menuItems)
    {
        NavBar::begin([
            'brandLabel' => $title,
            'brandUrl' => "",
            'options' => [
                'style' => ['background-color' => '#cfe2f3'],
                'class' => ' navbar navbar-default',
            ],
        ]);
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-left'],
            'items' => $menuItems
        ]);

        NavBar::end();
    }

    public static function createRolesMenu($items)
    {
        $items = self::getMenu($items);
        echo Menu::widget(
            [
                'options' => ['class' => 'sidebar-menu', 'data-widget' => 'tree'],
                'items' => $items
            ]
        );
    }

    private static function getMenu($m = null)
    {
        $path = Yii::$app->basePath . '/themes/lte/layouts/menu';
        $menu = [];
        $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        foreach ($roles as $key => $value) {
            if (file_exists($path . '/' . $key . '.php')) {
                $menu[] = require($path . '/' . $key . '.php');
            }
        }
        if ($m != null) {
            $menu = array_merge($m, $menu);
        }
        return $menu;
    }
}
