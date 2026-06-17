<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\db\Expression;

/**
 * LojaController implements the CRUD actions for creating new stores/filiais.
 */
class LojaController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all lojas/filiais that the current user has access to.
     * @return mixed
     */
    public function actionIndex()
    {
        $usuarioLogado = Yii::$app->user->identity;
        
        if (!$usuarioLogado) {
            return $this->redirect(['/auth/login']);
        }
        
        // Busca todas as lojas onde o usuário é dono OU colaborador
        $lojasComoDono = Usuario::find()
            ->where(['eh_dono_loja' => true])
            ->andWhere(['id' => $usuarioLogado->id])
            ->all();
        
        // Busca lojas onde o usuário é colaborador
        $colaboracoes = Colaborador::find()
            ->where(['prest_usuario_login_id' => $usuarioLogado->id])
            ->orWhere(['usuario_id' => $usuarioLogado->id])
            ->andWhere(['ativo' => true])
            ->all();
        
        $lojasComoColaborador = [];
        foreach ($colaboracoes as $colab) {
            $loja = Usuario::findOne($colab->usuario_id);
            if ($loja && $loja->eh_dono_loja) {
                $lojasComoColaborador[] = $loja;
            }
        }
        
        return $this->render('index', [
            'lojasComoDono' => $lojasComoDono,
            'lojasComoColaborador' => $lojasComoColaborador,
        ]);
    }

    /**
     * Creates a new loja/filial.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $usuarioLogado = Yii::$app->user->identity;
        
        if (!$usuarioLogado) {
            return $this->redirect(['/auth/login']);
        }
        
        // Verifica se o usuário é dono de alguma loja
        $ehDonoLoja = $this->converterParaBoolean($usuarioLogado->eh_dono_loja);
        
        if (!$ehDonoLoja) {
            Yii::$app->session->setFlash('error', 'Apenas donos de loja podem criar novas filiais.');
            return $this->redirect(['index']);
        }
        
        $model = new Usuario();
        $model->eh_dono_loja = true;
        $model->api_de_pagamento = false;
        $model->mercadopago_sandbox = true;
        $model->asaas_sandbox = true;
        $model->gateway_pagamento = 'nenhum';
        $model->catalogo_path = 'catalogo';
        
        if ($model->load(Yii::$app->request->post())) {
            $transaction = Yii::$app->db->beginTransaction();
            
            try {
                // Gera UUID para a nova loja
                $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                if (!$uuid) {
                    // Fallback para PHP UUID
                    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
                
                $model->id = $uuid;
                $model->eh_dono_loja = true;
                
                // Gera auth_key
                $model->auth_key = Yii::$app->security->generateRandomString(32);
                
                // Hash da senha - pega do campo hash_senha do formulário
                $senha = Yii::$app->request->post('Usuario')['hash_senha'] ?? null;
                if (!empty($senha)) {
                    $model->setPassword($senha);
                }
                
                if (!$model->save()) {
                    throw new \Exception('Erro ao criar loja: ' . implode(', ', array_map(function($errors) {
                        return implode(', ', $errors);
                    }, $model->errors)));
                }
                
                // =============================================================
                // ✅ CRIAR COLABORADOR AUTOMATICAMENTE
                // =============================================================
                $colaborador = new Colaborador();
                
                // Gera UUID para o colaborador
                $uuidColab = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                if (!$uuidColab) {
                    $uuidColab = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
                
                $colaborador->id = $uuidColab;
                $colaborador->usuario_id = $model->id; // ID da NOVA loja
                $colaborador->prest_usuario_login_id = $usuarioLogado->id; // Login do dono original
                $colaborador->nome_completo = $usuarioLogado->nome;
                $colaborador->cpf = $usuarioLogado->cpf;
                $colaborador->telefone = $usuarioLogado->telefone;
                $colaborador->email = $usuarioLogado->email;
                $colaborador->eh_vendedor = true;
                $colaborador->eh_cobrador = true;
                $colaborador->eh_administrador = true;
                $colaborador->ativo = true;
                $colaborador->data_admissao = date('Y-m-d');
                $colaborador->percentual_comissao_venda = 0;
                $colaborador->percentual_comissao_cobranca = 0;
                
                if (!$colaborador->save()) {
                    throw new \Exception('Erro ao criar colaborador: ' . implode(', ', array_map(function($errors) {
                        return implode(', ', $errors);
                    }, $colaborador->errors)));
                }
                
                $transaction->commit();
                
                Yii::$app->session->setFlash('success', 'Loja/filial criada com sucesso! Você foi automaticamente adicionado como colaborador administrador.');
                return $this->redirect(['index']);
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error("❌ Erro ao criar loja: {$e->getMessage()}", __METHOD__);
                Yii::$app->session->setFlash('error', 'Erro ao criar loja: ' . $e->getMessage());
            }
        }
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Helper para converter valor boolean do PostgreSQL para PHP boolean
     */
    protected function converterParaBoolean($valor)
    {
        if ($valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true') {
            return true;
        }
        
        if (is_string($valor) && strtolower(trim($valor)) === 't') {
            return true;
        }
        
        return false;
    }
}

