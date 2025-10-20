<?php
/**
 * SignupForm - Formulário de Cadastro de Prestanista
 * 
 * Localização: app/modules/vendas/models/SignupForm.php
 */

namespace app\modules\vendas\models;

use Yii;
use yii\base\Model;
use app\models\Usuario;

/**
 * SignupForm - Cadastro de novo prestanista no sistema
 */
class SignupForm extends Model
{
    public $nome;
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
            // Campos obrigatórios
            [['nome', 'email', 'senha', 'senha_confirmacao'], 'required', 'message' => 'Este campo é obrigatório.'],
            
            // Nome
            [['nome'], 'string', 'min' => 3, 'max' => 100, 'message' => 'O nome deve ter entre 3 e 100 caracteres.'],
            [['nome'], 'match', 'pattern' => '/^[a-zA-ZÀ-ÿ\s]+$/', 'message' => 'O nome deve conter apenas letras.'],
            
            // Email
            [['email'], 'trim'],
            [['email'], 'email', 'message' => 'Formato de e-mail inválido.'],
            [['email'], 'string', 'max' => 100],
            [['email'], 'unique', 
                'targetClass' => Usuario::class, 
                'targetAttribute' => 'email',
                'message' => 'Este e-mail já está cadastrado no sistema.'
            ],
            
            // Senha
            [['senha'], 'string', 'min' => 6, 'message' => 'A senha deve ter no mínimo 6 caracteres.'],
            [['senha'], 'match', 
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'message' => 'A senha deve conter ao menos: uma letra maiúscula, uma minúscula e um número.'
            ],
            
            // Confirmação de senha
            [['senha_confirmacao'], 'compare', 
                'compareAttribute' => 'senha', 
                'message' => 'As senhas não conferem.'
            ],
            
            // Termos de uso
            [['termos_aceitos'], 'required', 'requiredValue' => 1, 
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
            'email' => 'E-mail',
            'senha' => 'Senha',
            'senha_confirmacao' => 'Confirmar Senha',
            'termos_aceitos' => 'Aceito os Termos de Uso',
        ];
    }

    /**
     * Cadastra um novo prestanista no sistema
     * 
     * @return Usuario|null O usuário cadastrado ou null em caso de erro
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
            $usuario->scenario = 'create'; // Define scenario para exigir senha
            $usuario->nome = $this->nome;
            $usuario->email = $this->email;
            $usuario->senha = $this->senha;
            $usuario->senha_confirmacao = $this->senha_confirmacao;

            if (!$usuario->save()) {
                Yii::error('Erro ao salvar usuário: ' . json_encode($usuario->errors), __METHOD__);
                throw new \Exception('Erro ao cadastrar usuário. Tente novamente.');
            }

            // Criar configuração padrão do prestanista
            $configuracao = new Configuracao();
            $configuracao->usuario_id = $usuario->id;
            $configuracao->cor_primaria = '#3B82F6';
            $configuracao->cor_secundaria = '#10B981';
            $configuracao->catalogo_publico = false;
            $configuracao->aceita_orcamentos = true;
            
            if (!$configuracao->save()) {
                Yii::error('Erro ao criar configuração: ' . json_encode($configuracao->errors), __METHOD__);
                throw new \Exception('Erro ao configurar conta. Tente novamente.');
            }

            $transaction->commit();
            
            // Log de sucesso
            Yii::info("Novo prestanista cadastrado: {$usuario->email} (ID: {$usuario->id})", __METHOD__);
            
            return $usuario;
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Erro no cadastro: {$e->getMessage()}", __METHOD__);
            $this->addError('email', $e->getMessage());
            return null;
        }
    }

    /**
     * Envia email de boas-vindas ao novo prestanista
     * 
     * @param Usuario $usuario
     * @return bool
     */
    public function sendWelcomeEmail($usuario)
    {
        try {
            return Yii::$app->mailer->compose(
                ['html' => '@app/modules/vendas/mail/welcome-html'],
                ['usuario' => $usuario]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setTo($usuario->email)
            ->setSubject('Bem-vindo ao ' . Yii::$app->name)
            ->send();
        } catch (\Exception $e) {
            Yii::error("Erro ao enviar email de boas-vindas: {$e->getMessage()}", __METHOD__);
            return false;
        }
    }
}