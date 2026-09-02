<?php

namespace app\modules\vendas\controllers;

use Yii;
use app\models\Usuario;
use app\modules\vendas\models\Colaborador;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\db\Expression;
use app\components\TenantHelper;

class UsuarioController extends Controller
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
                        'matchCallback' => function ($rule, $action) {
                            // Apenas administradores podem acessar
                            return $this->isAdministrador();
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'bloquear' => ['POST'],
                    'ativar' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Verifica se o usuário logado é administrador
     * Retorna true se:
     * - É dono da loja (eh_dono_loja = true), OU
     * - É colaborador com eh_administrador = true
     */
    protected function isAdministrador()
    {
        $usuario = Yii::$app->user->identity;
        if (!$usuario) {
            return false;
        }

        // Se é dono da loja, tem acesso completo
        if ($usuario->eh_dono_loja === true) {
            return true;
        }

        // Se não é dono, verifica se é colaborador administrador
        // Usa o método helper do modelo Colaborador que suporta ambos os cenários
        $colaborador = Colaborador::getColaboradorLogado();

        if (!$colaborador) {
            return false;
        }
        
        // Converte valor boolean do PostgreSQL para PHP boolean
        $ehAdmin = $colaborador->eh_administrador === true 
            || $colaborador->eh_administrador === 't' 
            || $colaborador->eh_administrador === '1' 
            || $colaborador->eh_administrador === 1
            || (is_string($colaborador->eh_administrador) && strtolower(trim($colaborador->eh_administrador)) === 't');

        return $ehAdmin;
    }

    /**
     * Lista os usuários da loja atual (multitenancy)
     */
    public function actionIndex()
    {
        $tenantId = TenantHelper::getId();
        
        $query = Usuario::find()
            ->orderBy(['nome' => SORT_ASC]);

        // Isolamento de Tenant: exibe APENAS os usuários da loja atual (o próprio Dono + seus colaboradores vinculados)
        $colaboradorUserIds = Colaborador::find()
            ->select('prest_usuario_login_id')
            ->where(['usuario_id' => $tenantId])
            ->andWhere(['is not', 'prest_usuario_login_id', null])
            ->column();

        $allowedUserIds = array_unique(array_merge([$tenantId], $colaboradorUserIds));
        $query->andWhere(['in', 'prest_usuarios.id', $allowedUserIds]);

        // Filtros
        $busca = Yii::$app->request->get('busca');
        if ($busca) {
            $query->andFilterWhere(['or',
                ['like', 'nome', $busca],
                ['like', 'email', $busca],
                ['like', 'username', $busca],
                ['like', 'cpf', $busca],
            ]);
        }
        
        $ehDono = Yii::$app->request->get('eh_dono_loja');
        if ($ehDono !== null && $ehDono !== '') {
            $query->andWhere(['eh_dono_loja' => (bool)$ehDono]);
        }
        
        $bloqueado = Yii::$app->request->get('bloqueado');
        if ($bloqueado === '1') {
            $query->andWhere(['IS NOT', 'blocked_at', null]);
        } elseif ($bloqueado === '0') {
            $query->andWhere(['blocked_at' => null]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['nome' => SORT_ASC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Visualiza um usuário específico
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $tenantId = TenantHelper::getId();
        
        // Busca colaborador associado ao login do usuário no contexto da loja
        $colaborador = Colaborador::find()
            ->where(['prest_usuario_login_id' => $model->id])
            ->andWhere(['usuario_id' => $tenantId])
            ->one();

        return $this->render('view', [
            'model' => $model,
            'colaborador' => $colaborador,
        ]);
    }

    /**
     * Cria um novo usuário
     */
    public function actionCreate()
    {
        $model = new Usuario();
        $model->id = (string) Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
        $model->generateAuthKey();
        // Por padrão, não é dono (será colaborador)
        $model->eh_dono_loja = false;
        // Confirma automaticamente
        $model->confirmed_at = date('Y-m-d H:i:s');

        if ($model->load(Yii::$app->request->post())) {
            // Define senha se fornecida
            $senha = Yii::$app->request->post('Usuario')['senha'] ?? null;
            if (!empty($senha)) {
                $model->setPassword($senha);
            } else {
                $model->addError('senha', 'A senha é obrigatória para novos usuários.');
            }

            // Sanitiza CPF e Telefone
            if (!empty($model->cpf)) {
                $model->cpf = preg_replace('/[^0-9]/', '', $model->cpf);
            }
            if (!empty($model->telefone)) {
                $model->telefone = preg_replace('/[^0-9]/', '', $model->telefone);
            }
            
            // Gera username se não fornecido
            if (empty($model->username)) {
                $model->username = !empty($model->email) ? $model->email : $model->cpf;
            }
            
            // Apenas super admins do sistema podem definir eh_dono_loja = true
            if (TenantHelper::isAdmin()) {
                $ehDono = Yii::$app->request->post('Usuario')['eh_dono_loja'] ?? false;
                $model->eh_dono_loja = (bool)$ehDono;
            } else {
                $model->eh_dono_loja = false;
            }
            
            if (!$model->hasErrors()) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save()) {
                        $tenantId = TenantHelper::getId();
                        if (!empty($tenantId)) {
                            $colaborador = new Colaborador();
                            $colaborador->id = (string) Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                            $colaborador->usuario_id = $tenantId;
                            $colaborador->prest_usuario_login_id = $model->id;
                            $colaborador->nome_completo = $model->nome;
                            $colaborador->email = $model->email;
                            $colaborador->telefone = $model->telefone;
                            $colaborador->cpf = $model->cpf;
                            $colaborador->ativo = true;
                            $colaborador->eh_vendedor = true;
                            $colaborador->eh_cobrador = false;
                            $colaborador->eh_administrador = false;
                            $colaborador->save(false);
                        }
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Usuário criado com sucesso!');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'Erro ao salvar usuário: verifique os campos do formulário.');
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Erro ao salvar usuário: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Cria um novo usuário e já configura como colaborador (nova funcionalidade)
     */
    public function actionCreateCompleto()
    {
        $usuario = new Usuario();
        $colaborador = new Colaborador();
        
        // Gera UUID para usuário (string, não Expression, para não falhar em rules de string)
        $usuario->id = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
        $usuario->generateAuthKey();
        $usuario->eh_dono_loja = false; // Sempre será colaborador
        $usuario->confirmed_at = date('Y-m-d H:i:s');
        
        // Gera UUID para colaborador (string)
        $colaborador->id = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
        
        // Obtém o tenant ID da loja
        $tenantId = TenantHelper::getId();
        if (empty($tenantId)) {
            Yii::$app->session->setFlash('error', 'Você precisa estar logado em uma loja para criar colaboradores.');
            return $this->redirect(['index']);
        }
        
        // Define usuario_id do colaborador como o dono da loja (tenant_id)
        $colaborador->usuario_id = $tenantId;
        $colaborador->ativo = true; // Por padrão, ativo
        $colaborador->eh_vendedor = false; // Será definido no formulário
        $colaborador->eh_cobrador = false; // Será definido no formulário
        $colaborador->eh_administrador = false; // Por padrão, não é admin

        if ($usuario->load(Yii::$app->request->post()) && $colaborador->load(Yii::$app->request->post())) {
            // Valida confirmação de senha
            $senha = Yii::$app->request->post('Usuario')['senha'] ?? null;
            $senhaConfirmacao = Yii::$app->request->post('Usuario')['senha_confirmacao'] ?? null;
            
            if (empty($senha)) {
                $usuario->addError('senha', 'A senha é obrigatória para novos usuários.');
            } elseif ($senha !== $senhaConfirmacao) {
                $usuario->addError('senha', 'As senhas não coincidem.');
            } else {
                // Define senha ANTES de validar (para passar validação de hash_senha required)
                $usuario->setPassword($senha);
            }
            
            // Limpa CPF e telefone (remove formatação)
            if (!empty($usuario->cpf)) {
                $usuario->cpf = preg_replace('/[^0-9]/', '', $usuario->cpf);
            }
            if (!empty($usuario->telefone)) {
                $usuario->telefone = preg_replace('/[^0-9]/', '', $usuario->telefone);
            }
            
            // Gera username se não fornecido
            if (empty($usuario->username)) {
                $usuario->username = !empty($usuario->email) ? $usuario->email : $usuario->cpf;
            }
            
            // Sincroniza dados do colaborador com o usuário (sempre)
            $colaborador->nome_completo = !empty($usuario->nome) ? $usuario->nome : 'Colaborador';
            $colaborador->cpf = !empty($usuario->cpf) ? $usuario->cpf : null;
            $colaborador->telefone = !empty($usuario->telefone) ? $usuario->telefone : null;
            $colaborador->email = !empty($usuario->email) ? $usuario->email : null;
            
            // Valida se pelo menos um papel foi marcado
            if (!$colaborador->eh_vendedor && !$colaborador->eh_cobrador) {
                $colaborador->addError('eh_vendedor', 'O colaborador deve ser vendedor e/ou cobrador.');
            }
            
            // Valida usuário primeiro (hash_senha já foi definido se senha foi fornecida)
            $usuarioValido = $usuario->validate();
            
            if ($usuarioValido && !empty($senha) && $senha === $senhaConfirmacao) {
                // Inicia transação
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    // Salva usuário primeiro
                    if (!$usuario->save(false)) {
                        throw new \Exception('Erro ao salvar usuário: ' . json_encode($usuario->errors));
                    }
                    
                    // Define o ID do dono da loja no colaborador
                    $colaborador->usuario_id = $tenantId; // Loja do dono (identifica a loja)
                    $colaborador->prest_usuario_login_id = $usuario->id; // Login próprio do colaborador
                    
                    // Valida colaborador (agora que o usuário foi salvo, a FK existe)
                    $colaboradorValido = $colaborador->validate();
                    
                    if (!$colaboradorValido) {
                        throw new \Exception('Erro na validação do colaborador: ' . json_encode($colaborador->errors));
                    }
                    
                    // Salva colaborador
                    if (!$colaborador->save(false)) {
                        throw new \Exception('Erro ao salvar colaborador: ' . json_encode($colaborador->errors));
                    }
                    
                    $transaction->commit();
                    
                    Yii::$app->session->setFlash('success', 'Usuário e colaborador criados com sucesso!');
                    return $this->redirect(['view', 'id' => $usuario->id]);
                    
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'Erro ao criar usuário e colaborador: ' . $e->getMessage());
                    Yii::error('Erro ao criar usuário completo: ' . $e->getMessage(), __METHOD__);
                    Yii::error('Erros do usuário: ' . json_encode($usuario->errors), __METHOD__);
                    Yii::error('Erros do colaborador: ' . json_encode($colaborador->errors), __METHOD__);
                }
            } else {
                // Adiciona erros do colaborador ao usuário para exibir no formulário
                foreach ($colaborador->errors as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $usuario->addError('colaborador_' . $attribute, $error);
                    }
                }
                Yii::$app->session->setFlash('error', 'Há erros no formulário. Verifique os campos destacados.');
            }
            if (!$usuarioValido) {
                Yii::$app->session->setFlash('error', 'Há erros no formulário. Verifique os campos destacados.');
            }
        }

        return $this->render('create-completo', [
            'usuario' => $usuario,
            'colaborador' => $colaborador,
        ]);
    }

    /**
     * Atualiza um usuário existente
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $tenantId = TenantHelper::getId();
        $userLogado = Yii::$app->user->identity;

        // Proteção: colaborador admin não pode alterar os dados do Dono da Loja
        if ($model->id === $tenantId && $userLogado->id !== $tenantId && !TenantHelper::isAdmin()) {
            Yii::$app->session->setFlash('error', 'Apenas o próprio Dono da Loja ou um Administrador do Sistema pode alterar a conta do Dono.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        $senhaAnterior = $model->hash_senha;
        $ehDonoOriginal = $model->eh_dono_loja;

        if ($model->load(Yii::$app->request->post())) {
            // Apenas super admin pode alterar a flag de dono da loja
            if (!TenantHelper::isAdmin()) {
                $model->eh_dono_loja = $ehDonoOriginal;
            }

            // Se nova senha foi fornecida, atualiza
            $novaSenha = Yii::$app->request->post('Usuario')['nova_senha'] ?? null;
            if (!empty($novaSenha)) {
                $model->setPassword($novaSenha);
            } else {
                // Mantém senha anterior se não foi fornecida nova
                $model->hash_senha = $senhaAnterior;
            }
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Usuário atualizado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Bloqueia um usuário (usa blocked_at em prest_usuarios)
     */
    public function actionBloquear($id)
    {
        $model = $this->findModel($id);
        $tenantId = TenantHelper::getId();
        $userLogado = Yii::$app->user->identity;
        
        // Não permite bloquear a si mesmo
        if ($model->id === Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Você não pode bloquear seu próprio usuário.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Proteção: colaborador admin não pode bloquear a conta do Dono da Loja
        if ($model->id === $tenantId && $userLogado->id !== $tenantId && !TenantHelper::isAdmin()) {
            Yii::$app->session->setFlash('error', 'Apenas o Administrador do Sistema pode bloquear a conta do Dono da Loja.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Bloqueia o usuário
        if ($model->bloquear()) {
            Yii::$app->session->setFlash('success', 'Usuário bloqueado com sucesso!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao bloquear usuário.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Ativa um usuário (remove blocked_at)
     */
    public function actionAtivar($id)
    {
        $model = $this->findModel($id);

        // Desbloqueia o usuário
        if ($model->desbloquear()) {
            Yii::$app->session->setFlash('success', 'Usuário ativado com sucesso!');
        } else {
            Yii::$app->session->setFlash('error', 'Erro ao ativar usuário.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Altera a senha de um usuário
     */
    public function actionMudarSenha($id)
    {
        $model = $this->findModel($id);
        $tenantId = TenantHelper::getId();
        $userLogado = Yii::$app->user->identity;

        // Proteção: colaborador admin não pode mudar a senha do Dono da Loja
        if ($model->id === $tenantId && $userLogado->id !== $tenantId && !TenantHelper::isAdmin()) {
            Yii::$app->session->setFlash('error', 'Apenas o próprio Dono da Loja ou um Administrador do Sistema pode alterar a senha do Dono.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isPost) {
            $novaSenha = Yii::$app->request->post('nova_senha');
            $confirmarSenha = Yii::$app->request->post('confirmar_senha');

            if (empty($novaSenha)) {
                Yii::$app->session->setFlash('error', 'A nova senha é obrigatória.');
                return $this->redirect(['mudar-senha', 'id' => $model->id]);
            }

            if ($novaSenha !== $confirmarSenha) {
                Yii::$app->session->setFlash('error', 'As senhas não coincidem.');
                return $this->redirect(['mudar-senha', 'id' => $model->id]);
            }

            if (strlen($novaSenha) < 6) {
                Yii::$app->session->setFlash('error', 'A senha deve ter no mínimo 6 caracteres.');
                return $this->redirect(['mudar-senha', 'id' => $model->id]);
            }

            $model->setPassword($novaSenha);
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Senha alterada com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao alterar senha.');
            }
        }

        return $this->render('mudar-senha', [
            'model' => $model,
        ]);
    }

    /**
     * Encontra o modelo Usuario pelo ID dentro do escopo da loja (multitenancy)
     */
    protected function findModel($id)
    {
        $tenantId = TenantHelper::getId();
        $colaboradorUserIds = Colaborador::find()
            ->select('prest_usuario_login_id')
            ->where(['usuario_id' => $tenantId])
            ->andWhere(['is not', 'prest_usuario_login_id', null])
            ->column();

        $allowedUserIds = array_unique(array_merge([$tenantId], $colaboradorUserIds));

        if (!in_array($id, $allowedUserIds, true)) {
            throw new NotFoundHttpException('O usuário solicitado não existe ou não pertence à sua loja.');
        }

        if (($model = Usuario::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('O usuário solicitado não existe.');
    }
}

