<?php
/**
 * InicioController - Página inicial de gestão do prestanista
 * Localização: app/modules/vendas/controllers/InicioController.php
 */

namespace app\modules\vendas\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\vendas\models\Cliente;
use app\modules\vendas\models\Produto;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\Colaborador;
use app\modules\vendas\models\FormaPagamento;
use app\modules\vendas\models\Regiao;
use app\modules\vendas\models\Configuracao;
use app\behaviors\ModuloAccessBehavior;
use app\helpers\Salg;

/**
 * InicioController - Centro de controle e setup inicial
 */
class InicioController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            // Verificar se está logado
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            
            // ← ADICIONAR: Verificar se tem acesso ao módulo vendas
            'moduloAccess' => [
                'class' => ModuloAccessBehavior::class,
                'moduloCodigo' => 'vendas',
            ],
        ];
    }

    /**
     * Página inicial - Centro de Gestão
     */
    public function actionIndex()
    {
        $usuario = Yii::$app->user->identity;
        
        //Verifica status dos cadastros básicos
        $progressoSetup = [];
      
        // Estatísticas rápidas
        $stats = [
            'clientes' => Cliente::find()->where(['usuario_id' => $usuario->id])->count(),
            'produtos' => Produto::find()->where(['usuario_id' => $usuario->id])->count(),
            'categorias' => Categoria::find()->where(['usuario_id' => $usuario->id])->count(),
            'colaboradores' => Colaborador::find()->where(['usuario_id' => $usuario->id])->count(),
            'formas_pagamento' => FormaPagamento::find()->where(['usuario_id' => $usuario->id])->count(),
            'regioes' => Regiao::find()->where(['usuario_id' => $usuario->id])->count(),
        ];

       

        // Verifica se configuração está completa
        $configuracao = Configuracao::findOne(['usuario_id' => $usuario->id]);
        $configuracaoConcluida = $configuracao !== null;

        return $this->render('index', [
            'usuario' => $usuario,
            //'progressoSetup' => $progressoSetup,
            'stats' => $stats,
            'configuracaoConcluida' => $configuracaoConcluida,
        ]);
    }

    

    /**
     * Calcula progresso do setup inicial
     * 
     * @param string $usuarioId
     * @return array
     */
    protected function getProgressoSetup($usuarioId)
    {
        $etapas = [
            [
                'nome' => 'Configurações Básicas',
                'descricao' => 'Configure cores, informações e preferências',
                'concluida' => Configuracao::find()->where(['usuario_id' => $usuarioId])->exists(),
                'rota' => ['/vendas/configuracao/index'],
                'icone' => 'settings',
            ],
            [
                'nome' => 'Categorias de Produtos',
                'descricao' => 'Organize seus produtos em categorias',
                'concluida' => Categoria::find()->where(['usuario_id' => $usuarioId])->exists(),
                'rota' => ['/vendas/categoria/index'],
                'icone' => 'category',
            ],
            [
                'nome' => 'Produtos',
                'descricao' => 'Cadastre os produtos que você vende',
                'concluida' => Produto::find()->where(['usuario_id' => $usuarioId])->exists(),
                'rota' => ['/vendas/produto/index'],
                'icone' => 'product',
            ],
            [
                'nome' => 'Formas de Pagamento',
                'descricao' => 'Configure como você recebe pagamentos',
                'concluida' => FormaPagamento::find()->where(['usuario_id' => $usuarioId])->exists(),
                'rota' => ['/vendas/forma-pagamento/index'],
                'icone' => 'payment',
            ],
            [
                'nome' => 'Clientes',
                'descricao' => 'Cadastre seus clientes',
                'concluida' => Cliente::find()->where(['usuario_id' => $usuarioId])->exists(),
                'rota' => ['/vendas/cliente/index'],
                'icone' => 'people',
            ],
        ];

        $total = count($etapas);
        $concluidas = 0;
        
        foreach ($etapas as $etapa) {
            if ($etapa['concluida']) {
                $concluidas++;
            }
        }

        $percentual = $total > 0 ? round(($concluidas / $total) * 100) : 0;

        return [
            'etapas' => $etapas,
            'total' => $total,
            'concluidas' => $concluidas,
            'percentual' => $percentual,
        ];
    }
}