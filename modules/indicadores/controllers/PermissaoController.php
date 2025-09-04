<?php

namespace app\modules\indicadores\controllers;

use Yii;
use app\modules\indicadores\models\ManySysModulosHasManyUser;
use app\modules\indicadores\models\User;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * PermissaoController implementa o CRUD para as associações de Usuários e Módulos.
 */
class PermissaoController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista todos os usuários e seus módulos associados.
     * @return string
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'username' => SORT_ASC,
                ]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Cria associações de permissões para um novo usuário.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ManySysModulosHasManyUser();

        if ($this->request->isPost && $model->load($this->request->post())) {
            // Valida se um usuário foi selecionado
            if (empty($model->id_user)) {
                Yii::$app->session->setFlash('error', 'Por favor, selecione um usuário.');
            } elseif ($model->saveAssociations()) {
                $user = User::findOne($model->id_user);
                Yii::$app->session->setFlash('success', 'Permissões criadas com sucesso para ' . $user->username . '!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Ocorreu um erro ao salvar as permissões.');
            }
        }

        $allUsers = $model->getUsersForSelect();
        $allModules = $model->getModulosForSelect();

        return $this->render('create', [
            'model' => $model,
            'user'=>Yii::$app->user->identity,
            'allUsers' => $allUsers,
            'allModules' => $allModules,
        ]);
    }

    /**
     * Atualiza as permissões de um usuário existente.
     * @param int $id ID do Usuário
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $user = $this->findUserModel($id);
        $model = new ManySysModulosHasManyUser();
        $model->loadUserModules($user->id); // Carrega módulos já associados

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($model->saveAssociations()) {
                Yii::$app->session->setFlash('success', 'Permissões atualizadas com sucesso!');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Ocorreu um erro ao salvar as permissões.');
            }
        }

        $allUsers = $model->getUsersForSelect();
        $allModules = $model->getModulosForSelect();

        return $this->render('update', [
            'model' => $model,
            'user' => $user,
            'allUsers' => $allUsers,
            'allModules' => $allModules,
        ]);
    }

    /**
     * Visualiza as permissões de um usuário específico.
     * @param int $id ID do Usuário
     * @return mixed
     */
    public function actionView($id)
    {
        $user = $this->findUserModel($id);
        
        $modulos = ManySysModulosHasManyUser::find()
            ->joinWith('sysModulos')
            ->where(['id_user' => $user->id])
            ->all();

        return $this->render('view', [
            'user' => $user,
            'modulos' => $modulos,
        ]);
    }

    /**
     * Deleta todas as associações de um usuário.
     * @param int $id ID do Usuário
     * @return mixed
     */
    public function actionDelete($id)
    {
        $user = $this->findUserModel($id);
        $deletedCount = ManySysModulosHasManyUser::deleteAll(['id_user' => $user->id]);
        
        if ($deletedCount > 0) {
            Yii::$app->session->setFlash('success', 
                "Todas as {$deletedCount} permissão(ões) do usuário {$user->username} foram removidas.");
        } else {
            Yii::$app->session->setFlash('info', 
                "O usuário {$user->username} não possuía permissões para remover.");
        }

        return $this->redirect(['index']);
    }

    /**
     * Remove uma associação específica via AJAX.
     * @return mixed
     */
    public function actionRemoveModule()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        if (!$this->request->isPost) {
            return ['success' => false, 'message' => 'Método não permitido'];
        }

        $userId = $this->request->post('user_id');
        $moduleId = $this->request->post('module_id');

        if (!$userId || !$moduleId) {
            return ['success' => false, 'message' => 'Parâmetros obrigatórios não informados'];
        }

        $deleted = ManySysModulosHasManyUser::deleteAll([
            'id_user' => $userId,
            'id_sys_modulos' => $moduleId
        ]);

        if ($deleted > 0) {
            return ['success' => true, 'message' => 'Módulo removido com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Nenhuma associação encontrada'];
        }
    }

    /**
     * Busca usuários via AJAX para select2.
     * @return mixed
     */
    public function actionSearchUsers()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $query = $this->request->get('q', '');
        $page = $this->request->get('page', 1);
        $pageSize = 20;

        $usersQuery = User::find()
            ->andFilterWhere(['like', 'username', $query])
            ->orFilterWhere(['like', 'email', $query])
            ->limit($pageSize)
            ->offset(($page - 1) * $pageSize);

        $users = $usersQuery->all();
        $totalCount = User::find()
            ->andFilterWhere(['like', 'username', $query])
            ->orFilterWhere(['like', 'email', $query])
            ->count();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id,
                'text' => $user->username . ' (' . $user->email . ')'
            ];
        }

        return [
            'results' => $results,
            'pagination' => [
                'more' => ($page * $pageSize) < $totalCount
            ]
        ];
    }

    /**
     * Duplica as permissões de um usuário para outro.
     * @param int $id ID do usuário de origem
     * @return mixed
     */
    public function actionDuplicate($id)
    {
        $sourceUser = $this->findUserModel($id);
        
        if ($this->request->isPost) {
            $targetUserId = $this->request->post('target_user_id');
            
            if (!$targetUserId) {
                Yii::$app->session->setFlash('error', 'Selecione um usuário de destino.');
                return $this->redirect(['duplicate', 'id' => $id]);
            }

            $targetUser = $this->findUserModel($targetUserId);
            
            // Remove permissões existentes do usuário de destino
            ManySysModulosHasManyUser::deleteAll(['id_user' => $targetUserId]);
            
            // Copia as permissões do usuário de origem
            $sourceModules = ManySysModulosHasManyUser::find()
                ->where(['id_user' => $id])
                ->all();
            
            $copiedCount = 0;
            foreach ($sourceModules as $sourceModule) {
                $newAssociation = new ManySysModulosHasManyUser();
                $newAssociation->id_user = $targetUserId;
                $newAssociation->id_sys_modulos = $sourceModule->id_sys_modulos;
                
                if ($newAssociation->save()) {
                    $copiedCount++;
                }
            }
            
            if ($copiedCount > 0) {
                Yii::$app->session->setFlash('success', 
                    "{$copiedCount} permissão(ões) copiada(s) de {$sourceUser->username} para {$targetUser->username}.");
            } else {
                Yii::$app->session->setFlash('warning', 
                    "Nenhuma permissão foi encontrada para copiar de {$sourceUser->username}.");
            }
            
            return $this->redirect(['index']);
        }

        // Busca usuários disponíveis (exceto o usuário de origem)
        $availableUsers = User::find()
            ->where(['!=', 'id', $id])
            ->orderBy('username ASC')
            ->all();

        return $this->render('duplicate', [
            'sourceUser' => $sourceUser,
            'availableUsers' => ArrayHelper::map($availableUsers, 'id', 'username'),
        ]);
    }

    /**
     * Encontra o modelo User com base em sua chave primária.
     * @param int $id
     * @return User o modelo carregado
     * @throws NotFoundHttpException se o modelo não for encontrado
     */
    protected function findUserModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O usuário solicitado não existe.');
    }
}