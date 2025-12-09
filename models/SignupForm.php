<?php
/**
 * SignupForm - Formulário de Cadastro Global
 * Localização: app/models/SignupForm.php
 * 
 * Este formulário é usado por TODOS os módulos
 */

namespace app\models;

use app\helpers\Salg;
use Yii;
use yii\base\Model;
use app\models\Usuario;
use yii\db\Expression;

/**
 * SignupForm - Cadastro global de usuários
 */
class SignupForm extends Model
{
    public $nome;
    public $cpf;
    public $telefone;
    public $email;
    public $senha;
    public $senha_confirmacao;
    public $termos_aceitos;
    // Campos de endereço
    public $endereco;
    public $bairro;
    public $cidade;
    public $estado;
    public $logo_path;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Campos obrigatórios (conforme tabela prest_usuarios)
            [['nome', 'cpf', 'telefone', 'email', 'senha', 'senha_confirmacao', 'termos_aceitos'], 'required', 'message' => 'Este campo é obrigatório.'],
            
            // Nome
            [['nome'], 'string', 'min' => 3, 'max' => 100],
            [['nome'], 'trim'],
            [['nome'], 'match', 'pattern' => '/^[a-zA-ZÀ-ÿ\s]+$/', 'message' => 'O nome deve conter apenas letras.'],

            // CPF
            [['cpf'], 'trim'],
            [['cpf'], 'match', 'pattern' => '/^\d{11}$/', 'message' => 'CPF deve conter exatamente 11 números.'],
            [['cpf'], 'unique', 
                'targetClass' => Usuario::class, 
                'targetAttribute' => 'cpf',
                'message' => 'Este CPF já está cadastrado no sistema.'
            ],
            [['cpf'], 'validateCPF'],

            // Telefone
            [['telefone'], 'trim'],
            [['telefone'], 'match', 'pattern' => '/^\d{10,11}$/', 'message' => 'Telefone inválido. Use apenas números (10 ou 11 dígitos).'],

            // Email (obrigatório conforme tabela)
            [['email'], 'trim'],
            [['email'], 'required', 'message' => 'O e-mail é obrigatório.'],
            [['email'], 'email', 'message' => 'Formato de e-mail inválido.'],
            [['email'], 'string', 'max' => 100],
            [['email'], 'unique', 
                'targetClass' => Usuario::class, 
                'targetAttribute' => 'email',
                'message' => 'Este e-mail já está cadastrado no sistema.',
                'skipOnEmpty' => false
            ],
            
            // Senha
            [['senha'], 'string', 'min' => 6, 'message' => 'A senha deve ter no mínimo 6 caracteres.'],
            
            // Confirmação de senha
            [['senha_confirmacao'], 'required', 'message' => 'Confirme sua senha.'],
            [['senha_confirmacao'], 'compare', 
                'compareAttribute' => 'senha', 
                'message' => 'As senhas não conferem.'
            ],
            
            // Termos de uso - validação melhorada para checkbox
            [['termos_aceitos'], 'boolean'],
            [['termos_aceitos'], 'required', 'message' => 'Você deve aceitar os termos de uso para continuar.'],
            [['termos_aceitos'], 'validateTermosAceitos'],
            
            // Campos de endereço (opcionais)
            [['endereco'], 'string', 'max' => 255],
            [['bairro', 'cidade'], 'string', 'max' => 100],
            [['estado'], 'string', 'max' => 2],
            [['estado'], 'match', 'pattern' => '/^[A-Z]{2}$/', 'message' => 'Estado deve ter 2 letras maiúsculas (ex: MG, SP).', 'skipOnEmpty' => true],
            [['logo_path'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nome' => 'Nome Completo',
            'cpf' => 'CPF',
            'telefone' => 'Telefone (WhatsApp)',
            'email' => 'E-mail',
            'senha' => 'Senha',
            'senha_confirmacao' => 'Confirmar Senha',
            'termos_aceitos' => 'Aceito os Termos de Uso',
            'endereco' => 'Endereço da Empresa',
            'bairro' => 'Bairro',
            'cidade' => 'Cidade',
            'estado' => 'Estado (UF)',
            'logo_path' => 'URL/Caminho da Logo da Empresa',
        ];
    }

    /**
     * Valida termos aceitos (checkbox)
     */
    public function validateTermosAceitos($attribute, $params)
    {
        // Checkbox pode vir como '1', 1, true, 'on', etc.
        $value = $this->$attribute;
        
        // Converte para boolean
        if ($value === '1' || $value === 1 || $value === true || $value === 'on') {
            $this->$attribute = 1;
            return;
        }
        
        // Se estiver vazio ou false, adiciona erro
        if (empty($value) || $value === false || $value === '0' || $value === 0) {
            $this->addError($attribute, 'Você deve aceitar os termos de uso para continuar.');
        }
    }

    /**
     * Valida CPF
     */
    public function validateCPF($attribute, $params)
    {
        $cpf = preg_replace('/[^0-9]/', '', $this->$attribute);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            $this->addError($attribute, 'CPF inválido.');
            return;
        }
        
        // Verifica se não é uma sequência de números iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $this->addError($attribute, 'CPF inválido.');
            return;
        }
        
        // Valida primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        
        if (intval($cpf[9]) != $digito1) {
            $this->addError($attribute, 'CPF inválido.');
            return;
        }
        
        // Valida segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        
        if (intval($cpf[10]) != $digito2) {
            $this->addError($attribute, 'CPF inválido.');
            return;
        }
    }

    /**
     * Cadastra um novo usuário no sistema
     * @return Usuario|null
     */
    // public function signup()
    // {
    //     if (!$this->validate()) {
    //         return null;
    //     }

    //     $transaction = Yii::$app->db->beginTransaction();
        
    //     try {
    //         // Criar novo usuário
    //         $usuario = new Usuario();

    //         // ======================= DEBUG PARTE 1 =======================
    //     // Vamos verificar o estado do objeto logo após a sua criação
    //     \Yii::debug('isNewRecord APÓS new Usuario(): ' . ($usuario->isNewRecord ? 'true' : 'false'));
    //     // =============================================================

    //         $usuario->scenario = 'create';
    //         $usuario->nome = $this->nome;
    //         $usuario->cpf = preg_replace('/[^0-9]/', '', $this->cpf);
    //         $usuario->telefone = preg_replace('/[^0-9]/', '', $this->telefone);
    //         $usuario->email = trim(strtolower($this->email));
    //         $usuario->senha = $this->senha;
    //         $usuario->senha_confirmacao = $this->senha_confirmacao;
    //         Salg::log($usuario,false,"USUARIO-PEGO");
    //          // ======================= DEBUG PARTE 2 =======================
    //         // Agora vamos verificar o estado ANTES de salvar
    //         \Yii::debug('isNewRecord ANTES de save(): ' . ($usuario->isNewRecord ? 'true' : 'false'));
    //         // =============================================================

    //             if (!$usuario->save()) {
    //                 Yii::error('Erro ao salvar usuário: ' . json_encode($usuario->errors), __METHOD__);
                    
    //                 // Adiciona erros do model ao form
    //                 foreach ($usuario->errors as $field => $errors) {
    //                     foreach ($errors as $error) {
    //                         $this->addError($field, $error);
    //                     }
    //                 }
                    
    //                 throw new \Exception('Erro ao cadastrar usuário. Verifique os dados e tente novamente.');
    //             }

    //             $transaction->commit();
                
    //             Yii::info("Novo usuário cadastrado: {$usuario->email} (ID: {$usuario->id})", __METHOD__);
                
    //             // Envia email de boas-vindas (opcional)
    //             //$this->sendWelcomeEmail($usuario);
                
    //             return $usuario;
                
    //         } catch (\Exception $e) {
    //             $transaction->rollBack();
    //             Yii::error("Erro no cadastro: {$e->getMessage()}", __METHOD__);
                
    //             if (!$this->hasErrors()) {
    //                 $this->addError('email', 'Erro ao cadastrar usuário. Tente novamente.');
    //             }
                
    //             return null;
    //         }
    // }

    public function signup()
    {
        // 🔍 DEBUG: Log antes da validação
        Yii::info('🔍 Iniciando signup() - Dados: ' . json_encode($this->attributes), __METHOD__);
        
        if (!$this->validate()) {
            Yii::error('❌ Validação falhou no signup(): ' . json_encode($this->errors), __METHOD__);
            return null;
        }
        
        Yii::info('✅ Validação passou, iniciando criação do usuário', __METHOD__);

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            $usuario = new Usuario();

            // =============================================================
            // ✅ INÍCIO: AJUSTES CRÍTICOS NO CADASTRO
            // =============================================================

            // 1. Gera o ID (obrigatório pela regra do model Usuario )
            //    Gera UUID como string para passar na validação (não usa Expression)
            try {
                // Tenta usar gen_random_uuid() do PostgreSQL (nativo, não precisa de extensão)
                $usuario->id = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
            } catch (\Exception $e) {
                Yii::error("Erro ao gerar UUID com gen_random_uuid(): " . $e->getMessage(), __METHOD__);
                // Fallback: gera UUID no PHP usando função nativa ou helper
                if (function_exists('uuid_create')) {
                    $uuid = uuid_create(UUID_TYPE_RANDOM);
                    $usuario->id = $uuid; // uuid_create já retorna string
                } elseif (class_exists('\Ramsey\Uuid\Uuid')) {
                    $usuario->id = \Ramsey\Uuid\Uuid::uuid4()->toString();
                } else {
                    // Fallback final: gera UUID v4 manualmente
                    $usuario->id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
            }

            // 2. Define os dados
            $usuario->nome = $this->nome;
            $usuario->cpf = preg_replace('/[^0-9]/', '', $this->cpf);
            $usuario->telefone = preg_replace('/[^0-9]/', '', $this->telefone);
            
            // Email é obrigatório - valida antes de usar
            if (empty($this->email)) {
                throw new \Exception('E-mail é obrigatório.');
            }
            $usuario->email = trim(strtolower($this->email));
            
            // Gera username (usa email ou CPF)
            $usuario->username = !empty($this->email) ? trim(strtolower($this->email)) : preg_replace('/[^0-9]/', '', $this->cpf);
            
            // Define como dono da loja (cadastro via signup sempre é dono)
            $usuario->eh_dono_loja = true;
            
            // Confirma automaticamente (pode mudar se implementar confirmação de email)
            $usuario->confirmed_at = date('Y-m-d H:i:s');
            
            // Campos de endereço (opcionais)
            $usuario->endereco = !empty($this->endereco) ? trim($this->endereco) : null;
            $usuario->bairro = !empty($this->bairro) ? trim($this->bairro) : null;
            $usuario->cidade = !empty($this->cidade) ? trim($this->cidade) : null;
            // Estado: converte para maiúsculas se preenchido
            $usuario->estado = !empty($this->estado) ? strtoupper(trim($this->estado)) : null;
            $usuario->logo_path = !empty($this->logo_path) ? trim($this->logo_path) : null;
            
            // 🔍 DEBUG: Log do usuário antes de salvar
            Yii::info('🔍 Usuário preparado para salvar: ' . json_encode([
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'cpf' => $usuario->cpf,
                'username' => $usuario->username,
                'eh_dono_loja' => $usuario->eh_dono_loja,
            ]), __METHOD__);
            
            // 3. Gera o HASH da senha usando o helper (NÃO SALVE SENHA EM TEXTO PLANO)
            if (empty($this->senha)) {
                throw new \Exception('Senha é obrigatória.');
            }
            $usuario->setPassword($this->senha);
            
            // Verifica se hash_senha foi gerado
            if (empty($usuario->hash_senha)) {
                throw new \Exception('Erro ao gerar hash da senha.');
            }
            
            // 4. Gera o AuthKey (necessário para "lembrar-me" e validação)
            $usuario->generateAuthKey();
            
            // Verifica se auth_key foi gerado
            if (empty($usuario->auth_key)) {
                throw new \Exception('Erro ao gerar auth_key.');
            }
            
            // 🔍 DEBUG: Verifica campos obrigatórios antes de salvar
            $camposObrigatorios = ['id', 'nome', 'hash_senha', 'cpf', 'telefone', 'username'];
            $camposVazios = [];
            foreach ($camposObrigatorios as $campo) {
                if (empty($usuario->$campo)) {
                    $camposVazios[] = $campo;
                }
            }
            
            if (!empty($camposVazios)) {
                throw new \Exception('Campos obrigatórios não preenchidos: ' . implode(', ', $camposVazios));
            }
            
            // As linhas antigas abaixo estavam erradas:
            // $usuario->senha = $this->senha;
            // $usuario->senha_confirmacao = $this->senha_confirmacao;
            
            // =============================================================
            // ✅ FIM: AJUSTES CRÍTICOS NO CADASTRO
            // =============================================================


            Salg::log($usuario,false,"USUARIO-PREPARADO");

            if (!$usuario->save()) {
                Yii::error('❌ Erro ao salvar usuário: ' . json_encode($usuario->errors), __METHOD__);
                Yii::error('❌ Atributos do usuário: ' . json_encode($usuario->attributes), __METHOD__);
                Yii::error('❌ Campos obrigatórios do Usuario: id, nome, hash_senha, cpf, telefone, username', __METHOD__);
                Yii::error('❌ Valores definidos: ' . json_encode([
                    'id' => $usuario->id ? 'definido' : 'VAZIO',
                    'nome' => $usuario->nome ? 'definido' : 'VAZIO',
                    'hash_senha' => $usuario->hash_senha ? 'definido' : 'VAZIO',
                    'cpf' => $usuario->cpf ? 'definido' : 'VAZIO',
                    'telefone' => $usuario->telefone ? 'definido' : 'VAZIO',
                    'username' => $usuario->username ? 'definido' : 'VAZIO',
                ]), __METHOD__);
                
                // Adiciona erros do model ao form com mensagens específicas
                $mensagensErro = [];
                foreach ($usuario->errors as $field => $errors) {
                    foreach ($errors as $error) {
                        // Mapeia campos do Usuario para campos do SignupForm
                        $campoForm = $field;
                        if ($field === 'hash_senha') {
                            $campoForm = 'senha';
                        } elseif ($field === 'username') {
                            // username pode ser gerado do email ou CPF
                            // Se o erro for de unicidade, provavelmente é do email
                            if (stripos($error, 'unique') !== false || stripos($error, 'já') !== false) {
                                $campoForm = 'email';
                            } else {
                                $campoForm = 'email';
                            }
                        }
                        $this->addError($campoForm, $error);
                        // Tenta obter label do campo, senão usa o nome do campo
                        $label = $usuario->getAttributeLabel($field);
                        $mensagensErro[] = $label . ': ' . $error;
                    }
                }
                
                // Se não houver erros específicos, adiciona erro genérico
                if (empty($mensagensErro)) {
                    $this->addError('email', 'Erro desconhecido ao salvar usuário. Verifique os logs.');
                    $mensagensErro[] = 'Erro desconhecido ao salvar usuário.';
                }
                
                throw new \Exception('Erro ao cadastrar: ' . implode(' | ', $mensagensErro));
            }
            
            Yii::info('✅ Usuário salvo com sucesso! ID: ' . $usuario->id, __METHOD__);
            
            // =============================================================
            // ✅ CRIAR COLABORADOR AUTOMATICAMENTE
            // =============================================================
            // Quando criar uma conta (signup), automaticamente criar um registro
            // em prest_colaboradores com os mesmos dados de prest_usuarios
            try {
                $colaborador = new \app\modules\vendas\models\Colaborador();
                
                // Gera UUID para colaborador
                try {
                    $colaborador->id = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                } catch (\Exception $e) {
                    Yii::error("Erro ao gerar UUID para colaborador: " . $e->getMessage(), __METHOD__);
                    // Fallback: gera UUID manualmente
                    if (function_exists('uuid_create')) {
                        $colaborador->id = uuid_create(UUID_TYPE_RANDOM);
                    } else {
                        $colaborador->id = sprintf(
                            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
                
                // Define dados do colaborador baseado no usuário
                $colaborador->usuario_id = $usuario->id; // O próprio usuário é o dono da loja
                $colaborador->prest_usuario_login_id = null; // Não tem login próprio (usa o login do dono)
                $colaborador->nome_completo = $usuario->nome;
                $colaborador->cpf = $usuario->cpf;
                $colaborador->telefone = $usuario->telefone;
                $colaborador->email = $usuario->email;
                $colaborador->eh_vendedor = true; // Por padrão, o dono é vendedor
                $colaborador->eh_cobrador = false; // Pode ser ajustado depois
                $colaborador->eh_administrador = true; // O dono é sempre administrador
                $colaborador->ativo = true;
                $colaborador->data_admissao = date('Y-m-d');
                $colaborador->percentual_comissao_venda = 0;
                $colaborador->percentual_comissao_cobranca = 0;
                
                if (!$colaborador->save()) {
                    Yii::error('❌ Erro ao criar colaborador: ' . json_encode($colaborador->errors), __METHOD__);
                    throw new \Exception('Erro ao criar registro de colaborador: ' . implode(', ', array_map(function($errors) {
                        return implode(', ', $errors);
                    }, $colaborador->errors)));
                }
                
                Yii::info('✅ Colaborador criado com sucesso! ID: ' . $colaborador->id, __METHOD__);
            } catch (\Exception $e) {
                Yii::error("❌ Erro ao criar colaborador: {$e->getMessage()}", __METHOD__);
                // Não faz rollback aqui, pois o usuário já foi criado
                // Apenas loga o erro, mas permite que o cadastro continue
            }

            $transaction->commit();
            
            Yii::info("Novo usuário cadastrado: {$usuario->email} (ID: {$usuario->id})", __METHOD__);
            
            // Envia email de boas-vindas (opcional)
            //$this->sendWelcomeEmail($usuario);
            
            return $usuario;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("❌ Erro no cadastro: {$e->getMessage()}", __METHOD__);
            Yii::error("❌ Stack trace: " . $e->getTraceAsString(), __METHOD__);
            
            // Se não houver erros específicos, adiciona o erro genérico
            if (!$this->hasErrors()) {
                $this->addError('email', $e->getMessage());
            }
            
            return null;
        }
    }

    /**
     * Envia email de boas-vindas ao novo usuário
     * 
     * @param Usuario $usuario
     * @return bool
     */
    protected function sendWelcomeEmail($usuario)
    {
        try {
            return Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['supportEmail'] ?? 'noreply@thausz-pulse.com' => Yii::$app->name])
                ->setTo($usuario->email)
                ->setSubject('Bem-vindo ao ' . Yii::$app->name)
                ->setHtmlBody("
                    <h1>Olá, {$usuario->nome}!</h1>
                    <p>Bem-vindo ao <strong>" . Yii::$app->name . "</strong>!</p>
                    <p>Seu cadastro foi realizado com sucesso.</p>
                    <p><strong>CPF:</strong> {$usuario->getCpfFormatado()}</p>
                    <p>Você já pode fazer login no sistema.</p>
                    <br>
                    <p>Atenciosamente,<br>Equipe " . Yii::$app->name . "</p>
                ")
                ->send();
        } catch (\Exception $e) {
            Yii::error("Erro ao enviar email de boas-vindas: {$e->getMessage()}", __METHOD__);
            return false;
        }
    }
}