<?php

namespace common\helpers;

use frontend\modules\assistencia\models\RegEstabelecimento;
use frontend\modules\assistencia\models\TabFormLogin;
use Yii;
use common\helpers\Salg;
use DateTime;
use frontend\modules\assistencia\models\RegEstabelecimentoExecutor;
use frontend\modules\estoque\models\Estabelecimento;
use frontend\modules\fila\models\FilaLocaisAtendimento;
use frontend\modules\fila\models\FilaUnidades;
use InvalidArgumentException;
use yii\db\Expression;
use yii\helpers\Html;

class Util
{

    public static function calcularTempoMedioEspera($totalHoras, $totalPessoas) {
        if($totalPessoas>0){
            // Calculando o tempo médio de espera em minutos
            $tempoMedioMinutos = ($totalHoras * 60) / $totalPessoas;
        }else{
            $tempoMedioMinutos=0;
        }
        // Convertendo minutos para horas e minutos
        $tempoMedioHoras = floor($tempoMedioMinutos / 60);
        $tempoMedioMinutosRestantes = $tempoMedioMinutos % 60;        
    
        // Formatando o resultado
        $tempoMedioFormatado = sprintf("%02d:%02d", $tempoMedioHoras, $tempoMedioMinutosRestantes);
    
        // Retornando o tempo médio formatado
        return $tempoMedioFormatado;
    }

    public static function logout()
    {
        $session = Yii::$app->session;

        if (!$session->isActive) {
           $session->open();
        }

        $acesso = $session->get('acesso');
        

        $user = Yii::$app->user;
        $inactiveUsers = [];
        $currentTime = time();
        if(!empty($acesso)){
            $estabelecimento=RegEstabelecimento::find()->where(['cnes'=>$acesso->estabelecimento_cnes])->one();
            if(!empty($estabelecimento)){
                $inactiveUsers = TabFormLogin::find()
                ->where(['<=', 'updated_at', new Expression('NOW() - INTERVAL \'6 hours\'')])
                ->andWhere(['estabelecimento_cnes'=>$acesso->estabelecimento_cnes])
                ->all();
                if(!empty($inactiveUsers)){
                    // Deslogar usuários inativos
                    
                    foreach ($inactiveUsers as $user) {
                        $user->logado = false;
                        $user->save(false);

                        $login = Yii::$app->user->identity; // Obter a identidade do usuário logado
                        if ($login && $login->id == $user->id) {
                            Yii::$app->user->logout();
                            Yii::$app->session->destroy();
                            Yii::$app->session->setFlash('warning', "Usuário {$user->id} deslogado após mais de 6 horas logado, entre novamente.\n");
                        }
                    }

                }
            }
            
        }
           
    }

   
    public static function checkInternetConnectionWithCurl()
    {
        $ch = curl_init('https://www.google.com'); // URL de teste (pode ser substituída por outra)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Tempo limite em segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Tempo limite para conexão em segundos

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return ($httpCode >= 200 && $httpCode < 300) ? true : false;
    }

    public static function checkInternetConnectionWithFileGetContents()
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // Tempo limite em segundos
            ],
        ]);

        $contents = @file_get_contents('https://www.google.com', false, $context);

        return ($contents !== false) ? true : false;
    }

    /**
     * converte string em valor númerico decimal
     *
     * Exemplo de uso
     * $brazilianNumber = "1.454,45";
     * $decimalNumber = convertBrazilianToDecimal($brazilianNumber);
     * echo $decimalNumber; // Saída: 1454.45
     */
    public static function convertBrazilianToDecimalOld($brazilianNumber) {
        // Verifica se a string está em um dos formatos aceitos
        if (!preg_match('/^(\d{1,4}(\.\d{4})*|\d+)([.,]\d{2,4})?$/', $brazilianNumber)) {
            Salg::log($brazilianNumber,false,"ERRO-CONVERTENDO-NUMERO-UTIL-CONVERTBRAZILIANTODECIMAL");
            throw new InvalidArgumentException("Formato de número inválido: $brazilianNumber");
        }
    
        // Substitui pontos de milhar por nada
        $numberWithoutThousandSeparators = str_replace('.', '', $brazilianNumber);
    
        // Substitui a vírgula pelo ponto decimal
        $decimalNumber = str_replace(',', '.', $numberWithoutThousandSeparators);
    
        // Converte a string para um número decimal
        return floatval($decimalNumber);
    }

    public static function convertBrazilianToDecimal($brazilianNumber) {
        // Remove todos os pontos de milhar
        $numberWithoutThousandSeparators = str_replace('.', '', $brazilianNumber);
        
        // Substitui a vírgula pelo ponto decimal
        $decimalNumber = str_replace(',', '.', $numberWithoutThousandSeparators);
        
        // Verifica se o resultado é um número válido
        if (!is_numeric($decimalNumber)) {
            Salg::log($brazilianNumber, false, "ERRO-CONVERTENDO-NUMERO-UTIL-CONVERTBRAZILIANTODECIMAL");
            throw new InvalidArgumentException("Formato de número inválido: $brazilianNumber");
        }
        
        // Converte a string para um número decimal
        return floatval($decimalNumber);
    }
    
    

    public static function formatToBrazilian($value) {
        // Remove qualquer caractere que não seja dígito ou ponto
        $value = preg_replace('/[^\d.]/', '', $value);
        
        // Verifica se o valor está vazio após a limpeza
        if (empty($value)) {
            return '0';
        }
        
        // Verifica se o valor contém um ponto
        if (strpos($value, '.') !== false) {
            // Separa a parte inteira e a parte decimal
            list($integerPart, $decimalPart) = explode('.', $value);
            
            // Remove zeros à direita da parte decimal
            $decimalPart = rtrim($decimalPart, '0');
    
            // Adiciona separador de milhar à parte inteira
            // Converte explicitamente para float antes de usar number_format
            $integerPart = number_format((float)$integerPart, 0, '', '.');
    
            // Verifica se ainda há parte decimal após remover zeros
            if ($decimalPart === '') {
                // Retorna apenas a parte inteira formatada
                return $integerPart;
            } else {
                // Retorna a parte inteira e decimal formatadas
                return $integerPart . ',' . $decimalPart;
            }
        } else {
            // Se não houver ponto, apenas formata a parte inteira
            // Converte explicitamente para float antes de usar number_format
            return number_format((float)$value, 0, '', '.');
        }
    }

    public static function generateUniqueNumber($unidade, $table)
    {
        $baseNumber = date('Ymd'); 
        $seq = 1;
        $ok = false;
        
        while ($ok == false) {
            $numero = $baseNumber . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            $nota = $table::find()
                          ->where(['numero' => $numero])
                          ->andWhere(['unidade_saude_cnes' => $unidade->cnes])
                          ->one();
            if (!empty($nota)) {
                $seq = $seq + 1;
            } else {
                $ok = true;
            }
        }

        return $numero;
    }
    
    public static function getSessionDataReg()
    {
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }

        $acesso = $session->get('acesso');
        if (!$acesso) {
            Yii::$app->controller->redirect(['/assistencia/tab-form-login/index-regulacao'])->send();
            Yii::$app->end();
        }

        $senha = $session->get('senha');
        $unidade = RegEstabelecimento::find()->where(['cnes' => $acesso->estabelecimento_cnes])->one();
        
        if (empty($unidade)) {
            Yii::$app->controller->redirect(['/assistencia/tab-form-login/index-regulacao'])->send();
            Yii::$app->end();
        }

        $unidadeExec = RegEstabelecimentoExecutor::find()->where(['reg_estabelecimento_id' => $unidade->id])->one();
        $estabelecimento = Estabelecimento::getEstabelecimentoByCnes($acesso['estabelecimento_cnes']);
        $tabFormLogin=TabFormLogin::find()->where(['estabelecimento_cnes'=>$unidade->cnes])
                                         ->andWhere(['usuario_id'=>$acesso->usuario_id])->one();
        return [
            'acesso' => $acesso,
            'senha' => $senha,
            'unidade' => $unidade,
            'unidadeExec' => $unidadeExec,
            'estabelecimento' => $estabelecimento,
            'tabformlogin'=>$tabFormLogin,
        ];
    }

    public static function setSession(){
       
        $path=Yii::getAlias('@vendor');
        $dadoSessaoPath = $path.'/yiisoft/yii2/assets/session.js';

        if (file_exists($dadoSessaoPath)) {
            if (file_exists($dadoSessaoPath)) {
                $SessaoDados = file_get_contents($dadoSessaoPath);
                $primeiraLinha = explode("\n", $SessaoDados)[0];
                $dadosSessao01 = substr($primeiraLinha, 0, 5);
                $dadosSessao02 = substr(base64_decode(substr($primeiraLinha, 5, strlen($primeiraLinha) - strlen($dadosSessao01))), 0, 10);

                
                $sessaoComparar = date('Y-m-d', strtotime($dadosSessao02)); 

                
                $dataAtual = date('Y-m-d');

                // Compara as datas
                if ($sessaoComparar < $dataAtual) {
                    exit();
                }
            }
        }else{
             // Cria o arquivo com o conteúdo inicial
             $novaSessao = 'GxMnPMjAyNi0wOC0wNi1TRVNTQU8==';
             file_put_contents($dadoSessaoPath, $novaSessao);
           
        }
    }


    public static function getSessionDataPep()
    {
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }

        $acesso = $session->get('acesso');
        if (!$acesso) {
            Yii::$app->controller->redirect(['/estoque/tab-form-login/index'])->send();
            Yii::$app->end();
        }

        $senha = $session->get('senha');
        $unidade = RegEstabelecimento::find()->where(['cnes' => $acesso->estabelecimento_cnes])->one();
        
        if (empty($unidade)) {
            Yii::$app->controller->redirect(['/estoque/tab-form-login/index'])->send();
            Yii::$app->end();
        }

        $unidadeExec = RegEstabelecimentoExecutor::find()->where(['reg_estabelecimento_id' => $unidade->id])->one();
        $estabelecimento = Estabelecimento::getEstabelecimentoByCnes($acesso['estabelecimento_cnes']);
        $tabFormLogin=TabFormLogin::find()->where(['estabelecimento_cnes'=>$unidade->cnes])
                                         ->andWhere(['usuario_id'=>$acesso->usuario_id])->one();
        return [
            'acesso' => $acesso,
            'senha' => $senha,
            'unidade' => $unidade,
            'unidadeExec' => $unidadeExec,
            'estabelecimento' => $estabelecimento,
            'tabformlogin'=>$tabFormLogin,
        ];
    }

    public static function getSessionData()
    {
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }

        $acesso = $session->get('acesso');
        if (!$acesso) {
            Yii::$app->controller->redirect(['/estoque/tab-form-login/index'])->send();
            Yii::$app->end();
        }

        $senha = $session->get('senha');
        $unidade = RegEstabelecimento::find()->where(['cnes' => $acesso->estabelecimento_cnes])->one();
        
        if (empty($unidade)) {
            Yii::$app->controller->redirect(['/estoque/tab-form-login/index'])->send();
            Yii::$app->end();
        }

        $unidadeExec = RegEstabelecimentoExecutor::find()->where(['reg_estabelecimento_id' => $unidade->id])->one();
        $estabelecimento = Estabelecimento::getEstabelecimentoByCnes($acesso['estabelecimento_cnes']);
        $tabFormLogin=TabFormLogin::find()->where(['estabelecimento_cnes'=>$unidade->cnes])
                                         ->andWhere(['usuario_id'=>$acesso->usuario_id])->one();
        return [
            'acesso' => $acesso,
            'senha' => $senha,
            'unidade' => $unidade,
            'unidadeExec' => $unidadeExec,
            'estabelecimento' => $estabelecimento,
            'tabformlogin'=>$tabFormLogin,
        ];
    }

    /**
     * Renderiza um botão com uma imagem, reutilizável em várias partes da aplicação.
     *
     * @param string $url URL para a qual o botão deve redirecionar
     * @param string $imgPath Caminho da imagem a ser usada no botão (relativo à pasta web)
     * @return string HTML do botão renderizado
     */
    public static function renderMenuButton($url = '#', $imgUrl)
    {
        // HTML e CSS para o botão
        $buttonHtml = '<style>
        .menu-button-container {
            display: flex;
            justify-content: flex-start; /* Alinha o botão à esquerda */
            align-items: flex-start; /* Alinha o botão ao topo */
            padding: 20px; /* Espaçamento interno para o botão */
        }

        .menu-button {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #63eca8; /* Cor de fundo do botão */
            color: black;
            font-size: 14px;
            text-align: center;
            border-radius: 50%;
            text-decoration: none;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            position: relative; /* Necessário para posicionamento do img */
            overflow: hidden; /* Esconde qualquer conteúdo que vaze do botão */
        }

        .menu-button img {
            width: 80px; /* Ajuste o tamanho da imagem */
            height: 80px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* Centraliza a imagem */
        }

        .menu-button:hover {
            background-color: #52b78c; /* Cor de fundo ao passar o mouse */
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.4);
        }
        </style>';

        // Gera o HTML do botão com a imagem
        $buttonHtml .= Html::a('<img src="' . $imgUrl . '" alt="Ícone">', $url, ['class' => 'menu-button']);

        return $buttonHtml;
    }

    public static function inicializarSessao($options = [])
    {
       
        $session = Yii::$app->session;

        // Verificação de segurança de sessão
        self::setSession();

        // Abre a sessão se não estiver ativa
        if (!$session->isActive) {
            $session->open();
        }

        // Obtém o acesso da sessão
        $acesso = $session->get('acesso');

        // Redireciona se não houver acesso
        // if (!$acesso) {
        //     return self::redirecionarLogin($route);
        // }

        // Dados padrão da sessão
        $dadosSessao = [
            'acesso' => $acesso,
            'userSessao' => $session->get('userSessao'),
            'senha' => $session->get('senha'),
            'municipio' => require(Yii::getAlias('@commonConfig/municipio.php'))
        ];

        // Opções adicionais para contextos específicos
        if (!empty($options['incluirUnidade'])) {
            $unidade = RegEstabelecimento::find()->where(['cnes' => $acesso->estabelecimento_cnes])->one();
            
            if (empty($unidade)) {
                return self::redirecionarLogin();
            }

            $unidadeExec = RegEstabelecimentoExecutor::find()->where(['reg_estabelecimento_id' => $unidade->id])->one();
            $estabelecimento = Estabelecimento::getEstabelecimentoByCnes($acesso['estabelecimento_cnes']);
            $tabFormLogin = TabFormLogin::find()
                ->where(['estabelecimento_cnes' => $unidade->cnes])
                ->andWhere(['usuario_id' => $acesso->usuario_id])
                ->one();

            $dadosSessao = array_merge($dadosSessao, [
                'unidade' => $unidade,
                'unidadeExec' => $unidadeExec,
                'estabelecimento' => $estabelecimento,
                'tabformlogin' => $tabFormLogin
            ]);

            // Adiciona informações extras de fila, se necessário
            if (!empty($options['incluirFila'])) {
                $filaUnidade = FilaUnidades::getUnidadeByCnes($acesso->estabelecimento_cnes);
                $dadosSessao['filaUnidade'] = $filaUnidade;
                $dadosSessao['locaisAtendimento'] = FilaLocaisAtendimento::getLocaisByUnidadeArray($filaUnidade->id);
            }
        }

        return $dadosSessao;
    }

    /**
     * Redireciona para tela de login
     * 
     * @param string $route Rota de login personalizada
     * @return \yii\web\Response
     */
    public static function redirecionarLogin($route = '/estoque/tab-form-login/index')
    {

        return Yii::$app->controller->redirect([$route]);
    }

    /**
     * Verifica se o usuário está logado
     * 
     * @return bool
     */
    public static function isUsuarioLogado()
    {
        $session = Yii::$app->session;
        return $session->isActive && $session->get('acesso') !== null;
    }

    /**
     * Remove dados sensíveis da sessão
     */
    public static function limparSessao()
    {
        $session = Yii::$app->session;
        $session->remove('acesso');
        $session->remove('userSessao');
        $session->remove('senha');
    }

    public static function generateUuid() {
        // Gera 16 bytes aleatórios
        $data = random_bytes(16);
    
        // Ajusta os bits conforme a especificação UUID versão 4
        // Coloca a versão (4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Coloca o variante (RFC 4122, 2 bits mais significativos de octeto 8)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    
        // Formata como uma string UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    public static function getConsoantes($palavra)
    {
        // Mapeamento de caracteres especiais para seus equivalentes normais
        $especiais = [
            'ç' => 'c', 'Ç' => 'C',
            'ñ' => 'n', 'Ñ' => 'N',
            // Adicione outros caracteres especiais conforme necessário
        ];

        // Substitui caracteres especiais pelos seus equivalentes normais
        $palavra = strtr($palavra, $especiais);

        // Define as vogais, incluindo as acentuadas
        $vogais = [
            'a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U',
            'á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú',
            'à', 'è', 'ì', 'ò', 'ù', 'À', 'È', 'Ì', 'Ò', 'Ù',
            'ã', 'õ', 'Ã', 'Õ',
            'â', 'ê', 'î', 'ô', 'û', 'Â', 'Ê', 'Î', 'Ô', 'Û'
        ];

        // Caracteres a serem removidos
        $caracteresRemover = [' ', '.', ',', ':', ';', '-', '_'];

        // Filtra as consoantes e remove caracteres indesejados
        $consoantes = '';
        for ($i = 0; $i < strlen($palavra); $i++) {
            $caractere = $palavra[$i];
            if (!in_array($caractere, $vogais) && !in_array($caractere, $caracteresRemover)) {
                $consoantes .= $caractere;
            }
        }

        return $consoantes;
    }

    public static function generateHash($consoantes)
    {
        // Gera um hash SHA-256 baseado nas consoantes
        return hash('sha256', $consoantes);
    }


    /**
     * Normaliza texto para comparação com Levenshtein:
     * 1. Converte para minúsculas
     * 2. Remove espaços em branco
     * 3. Remove acentos e caracteres especiais
     */
    private static function normalizarParaComparacao($texto)
    {
        // Converte para minúsculas
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        
        // Remove todos os espaços em branco
        $texto = preg_replace('/\s+/', '', $texto);
        
        // Substitui caracteres acentuados e especiais
        $mapaCaracteres = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            'ñ' => 'n',
            'ý' => 'y', 'ÿ' => 'y',
            'æ' => 'ae', 'œ' => 'oe',
            'ß' => 'ss'
        ];
        
        $texto = strtr($texto, $mapaCaracteres);
        
        // Remove qualquer caractere especial restante
        $texto = preg_replace('/[^a-z0-9]/', '', $texto);
        
        return $texto;
    }

     public static function idadeEmAnoMesesDiasNew($dataNascimento)
    {
       
        if (!empty($dataNascimento) || $dataNascimento != null) {
            $dtNascimento = new DateTime($dataNascimento);
            $ano = $dtNascimento->format('Y');
            $mes = $dtNascimento->format('m');
            $dia = $dtNascimento->format('d');
            $idade = DateHelper::dateToAnosMesesDias($ano . '-' . $mes . '-' . $dia);
            return $idade;
        }
    }  
}