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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // Campos obrigatórios (conforme tabela prest_usuarios)
            [['nome', 'cpf', 'telefone','senha', 'senha_confirmacao', 'termos_aceitos'], 'required', 'message' => 'Este campo é obrigatório.'],
            
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
            [['email'], 'email', 'message' => 'Formato de e-mail inválido.'],
            [['email'], 'string', 'max' => 100],
            // [['email'], 'unique', 
            //     'targetClass' => Usuario::class, 
            //     'targetAttribute' => 'email',
            //     'message' => 'Este e-mail já está cadastrado no sistema.'
            // ],
            
            // Senha
            [['senha'], 'string', 'min' => 6, 'message' => 'A senha deve ter no mínimo 6 caracteres.'],
            
            // Confirmação de senha
            [['senha_confirmacao'], 'compare', 
                'compareAttribute' => 'senha', 
                'message' => 'As senhas não conferem.'
            ],
            
            // Termos de uso
            [['termos_aceitos'], 'boolean'],
            [['termos_aceitos'], 'compare', 'compareValue' => 1, 
                'message' => 'Você deve aceitar os termos de uso para continuar.'
            ],
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
        ];
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
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Criar novo usuário
            $usuario = new Usuario();

            // ======================= DEBUG PARTE 1 =======================
        // Vamos verificar o estado do objeto logo após a sua criação
        \Yii::debug('isNewRecord APÓS new Usuario(): ' . ($usuario->isNewRecord ? 'true' : 'false'));
        // =============================================================

            $usuario->scenario = 'create';
            $usuario->nome = $this->nome;
            $usuario->cpf = preg_replace('/[^0-9]/', '', $this->cpf);
            $usuario->telefone = preg_replace('/[^0-9]/', '', $this->telefone);
            $usuario->email = trim(strtolower($this->email));
            $usuario->senha = $this->senha;
            $usuario->senha_confirmacao = $this->senha_confirmacao;
            Salg::log($usuario,false,"USUARIO-PEGO");
             // ======================= DEBUG PARTE 2 =======================
            // Agora vamos verificar o estado ANTES de salvar
            \Yii::debug('isNewRecord ANTES de save(): ' . ($usuario->isNewRecord ? 'true' : 'false'));
            // =============================================================

                if (!$usuario->save()) {
                    Yii::error('Erro ao salvar usuário: ' . json_encode($usuario->errors), __METHOD__);
                    
                    // Adiciona erros do model ao form
                    foreach ($usuario->errors as $field => $errors) {
                        foreach ($errors as $error) {
                            $this->addError($field, $error);
                        }
                    }
                    
                    throw new \Exception('Erro ao cadastrar usuário. Verifique os dados e tente novamente.');
                }

                $transaction->commit();
                
                Yii::info("Novo usuário cadastrado: {$usuario->email} (ID: {$usuario->id})", __METHOD__);
                
                // Envia email de boas-vindas (opcional)
                //$this->sendWelcomeEmail($usuario);
                
                return $usuario;
                
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error("Erro no cadastro: {$e->getMessage()}", __METHOD__);
                
                if (!$this->hasErrors()) {
                    $this->addError('email', 'Erro ao cadastrar usuário. Tente novamente.');
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