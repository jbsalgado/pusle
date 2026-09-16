<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\modules\vendas\models\TaxaEntrega;
use app\modules\vendas\models\LojaConfiguracao;
use app\components\TenantHelper;
use app\components\MelhorEnvioService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;

/**
 * Controller para gestão de fretes por Estado, Faixa de Preço, Cidade, Bairro e CEP,
 * além de integração com APIs externas (Melhor Envio).
 */
class TaxaEntregaController extends Controller
{
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
                    'popular-estados-padrao' => ['POST'],
                    'testar-melhor-envio' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Listagem das regras de frete com suporte a busca e filtros por Estado/Localidade
     */
    public function actionIndex()
    {
        $tenantId = TenantHelper::getId();
        $query = TaxaEntrega::find()
            ->where(['usuario_id' => $tenantId, 'ativo' => true]);

        // Filtro de busca
        $busca = Yii::$app->request->get('busca');
        if ($busca && trim($busca) !== '') {
            $termo = '%' . trim($busca) . '%';
            $query->andWhere([
                'OR',
                ['ilike', 'estado', trim($busca)],
                ['ilike', 'nome_servico', $termo],
                ['ilike', new \yii\db\Expression('unaccent(cidade)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                ['ilike', new \yii\db\Expression('unaccent(bairro)'), new \yii\db\Expression('unaccent(:p)', [':p' => $termo])],
                ['ilike', 'cep', $termo],
            ]);
        }

        $tipo = Yii::$app->request->get('tipo');
        if ($tipo === 'ESTADO') {
            $query->andWhere(['IS NOT', 'estado', null]);
        } elseif ($tipo === 'LOCAL') {
            $query->andWhere(['estado' => null]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'estado' => SORT_ASC,
                    'cidade' => SORT_ASC,
                    'bairro' => SORT_ASC,
                ]
            ],
        ]);

        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $tenantId]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'busca' => $busca,
            'tipo' => $tipo,
            'lojaConfig' => $lojaConfig,
        ]);
    }

    /**
     * Cadastro de nova regra de frete
     */
    public function actionCreate()
    {
        $tenantId = TenantHelper::getId();
        $model = new TaxaEntrega();
        $model->usuario_id = $tenantId;
        $model->ativo = true;
        $model->prazo_dias_min = 1;
        $model->prazo_dias_max = 5;
        $model->nome_servico = 'Entrega Padrão';
        $model->faixa_preco_min = 0.00;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Regra de frete cadastrada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Alteração de regra existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Regra de frete atualizada com sucesso!');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Exclusão lógica da regra
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->ativo = false;
        $model->save(false);

        Yii::$app->session->setFlash('success', 'Regra removida!');
        return $this->redirect(['index']);
    }

    /**
     * Tela dedicada para configuração da integração com o Melhor Envio (APIs externas)
     */
    public function actionIntegracao()
    {
        $tenantId = TenantHelper::getId();
        $lojaConfig = LojaConfiguracao::findOne(['usuario_id' => $tenantId]);

        if (!$lojaConfig) {
            $lojaConfig = new LojaConfiguracao();
            $lojaConfig->usuario_id = $tenantId;
            $lojaConfig->nome_loja = Yii::$app->user->identity->nome ?? 'Minha Loja';
            $lojaConfig->cpf_cnpj = '00.000.000/0000-00';
            $lojaConfig->melhor_envio_ativo = false;
            $lojaConfig->melhor_envio_ambiente = 'production';
            $lojaConfig->save(false);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post('LojaConfiguracao', []);
            
            $lojaConfig->melhor_envio_ativo = !empty($post['melhor_envio_ativo']);
            $lojaConfig->melhor_envio_ambiente = in_array($post['melhor_envio_ambiente'] ?? '', ['sandbox', 'production']) ? $post['melhor_envio_ambiente'] : 'production';
            $lojaConfig->melhor_envio_token = trim($post['melhor_envio_token'] ?? '');
            $lojaConfig->melhor_envio_cep_origem = preg_replace('/\D/', '', $post['melhor_envio_cep_origem'] ?? '');
            $lojaConfig->melhor_envio_acrescimo_dias = (int)($post['melhor_envio_acrescimo_dias'] ?? 0);
            $lojaConfig->melhor_envio_acrescimo_valor = (float)str_replace(',', '.', $post['melhor_envio_acrescimo_valor'] ?? '0');

            if (isset($post['melhor_envio_servicos']) && is_array($post['melhor_envio_servicos'])) {
                $lojaConfig->melhor_envio_servicos = implode(',', $post['melhor_envio_servicos']);
            } elseif (isset($post['melhor_envio_servicos'])) {
                $lojaConfig->melhor_envio_servicos = $post['melhor_envio_servicos'];
            }

            if ($lojaConfig->save(false)) {
                Yii::$app->session->setFlash('success', 'Configurações de integração com Melhor Envio salvas com sucesso!');
                return $this->redirect(['integracao']);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao salvar configurações.');
            }
        }

        return $this->render('integracao', [
            'lojaConfig' => $lojaConfig,
        ]);
    }

    /**
     * Endpoint AJAX para testar a conexão com o Melhor Envio
     */
    public function actionTestarMelhorEnvio()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = trim(Yii::$app->request->post('token', ''));
        $ambiente = Yii::$app->request->post('ambiente', 'production');

        if (empty($token)) {
            return ['success' => false, 'message' => 'Informe o token para testar a conexão.'];
        }

        return MelhorEnvioService::testarConexao($token, $ambiente);
    }

    /**
     * Auto-preenche os 27 estados brasileiros com valores médios estimados de mercado
     */
    public function actionPopularEstadosPadrao()
    {
        $tenantId = TenantHelper::getId();
        $tabela = TaxaEntrega::getTabelaPadraoEstados();
        $inseridos = 0;
        $atualizados = 0;

        foreach ($tabela as $uf => $dados) {
            $regra = TaxaEntrega::findOne([
                'usuario_id' => $tenantId,
                'estado' => $uf,
                'ativo' => true,
                'cidade' => null,
                'bairro' => null,
                'cep' => null
            ]);

            if (!$regra) {
                $regra = new TaxaEntrega();
                $regra->usuario_id = $tenantId;
                $regra->estado = $uf;
                $regra->ativo = true;
                $regra->tipo_regra = 'ESTADO';
                $regra->nome_servico = "Frete Padrão {$uf}";
                $regra->porte = 'P';
                $regra->faixa_preco_min = 0.00;
                $inseridos++;
            } else {
                $atualizados++;
            }

            $regra->valor = $dados['valor'];
            $regra->prazo_dias_min = $dados['min'];
            $regra->prazo_dias_max = $dados['max'];
            $regra->valor_minimo_frete_gratis = $dados['gratis'];
            $regra->save(false);
        }

        Yii::$app->session->setFlash('success', "Tabela nacional configurada com sucesso! ({$inseridos} estados cadastrados, {$atualizados} atualizados). Você pode ajustar qualquer estado individualmente quando desejar.");
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        $tenantId = TenantHelper::getId();
        if (($model = TaxaEntrega::findOne(['id' => $id, 'usuario_id' => $tenantId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('A regra solicitada não existe.');
    }
}
