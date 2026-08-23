<?php

namespace app\modules\vendas\controllers;

use app\modules\vendas\models\Clientes;
use Yii;
use app\modules\vendas\models\PrestClientes;
use app\modules\vendas\models\Regioes;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\Html;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * PrestClientesController implementa as ações CRUD para o model PrestClientes.
 */
class ClientesController extends Controller
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
     * Lista todos os Clientes com filtros e paginação.
     * @return string
     */
    public function actionIndex()
    {
        $query = Clientes::find()
            ->where(['usuario_id' => \app\components\TenantHelper::getId()])
            ->with(['regiao'])
            ->orderBy(['nome_completo' => SORT_ASC]);

        // Aplicar filtros
        $busca = Yii::$app->request->get('busca');
        $buscaLimpa = preg_replace('/[^0-9]/', '', $busca ?? '');

        if ($busca) {
            $orConditions = ['or',
                ['like', 'nome_completo', $busca],
                ['like', 'razao_social', $busca],
                ['like', 'telefone', $busca],
                ['like', 'email', $busca],
            ];

            if ($buscaLimpa !== '') {
                $orConditions[] = ['like', 'cpf', $buscaLimpa];
                $orConditions[] = ['like', 'cnpj', $buscaLimpa];
            }

            $query->andFilterWhere($orConditions);
        }

        $regiaoId = Yii::$app->request->get('regiao_id');
        if ($regiaoId) {
            $query->andWhere(['regiao_id' => $regiaoId]);
        }

        $cidade = Yii::$app->request->get('cidade');
        if ($cidade) {
            $query->andFilterWhere(['like', 'endereco_cidade', $cidade]);
        }

        $ativo = Yii::$app->request->get('ativo');
        if ($ativo !== null && $ativo !== '') {
            $query->andWhere(['ativo' => (int)$ativo]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'nome_completo' => SORT_ASC,
                ]
            ],
        ]);

        // Buscar regiões para o filtro
        $regioes = Regioes::find()
            ->where(['usuario_id' => \app\components\TenantHelper::getId()])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Exibe um único modelo Cliente.
     * @param string $id
     * @return string
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria um novo modelo Cliente.
     * Se a criação for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Clientes();
        $model->usuario_id = \app\components\TenantHelper::getId();
        $model->ativo = true;

        // Buscar regiões para o dropdown
        $regioes = Regioes::find()
            ->where(['usuario_id' => \app\components\TenantHelper::getId()])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            
            // Limpar CPF antes de salvar (remover pontos e traços)
            if ($model->cpf) {
                $model->cpf = preg_replace('/[^0-9]/', '', $model->cpf);
            }

            // Limpar CEP antes de salvar
            if ($model->endereco_cep) {
                $model->endereco_cep = preg_replace('/[^0-9]/', '', $model->endereco_cep);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Cliente cadastrado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao salvar cliente. Verifique os dados.');
            }
        }

        return $this->render('create', [
            'model' => $model,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Atualiza um modelo Cliente existente.
     * Se a atualização for bem-sucedida, o navegador será redirecionado para a página 'view'.
     * @param string $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Buscar regiões para o dropdown
        $regioes = Regioes::find()
            ->where(['usuario_id' => \app\components\TenantHelper::getId()])
            ->orderBy(['nome' => SORT_ASC])
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            
            // Limpar CPF antes de salvar (remover pontos e traços)
            if ($model->cpf) {
                $model->cpf = preg_replace('/[^0-9]/', '', $model->cpf);
            }

            // Limpar CEP antes de salvar
            if ($model->endereco_cep) {
                $model->endereco_cep = preg_replace('/[^0-9]/', '', $model->endereco_cep);
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Cliente atualizado com sucesso!');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao atualizar cliente. Verifique os dados.');
            }
        }

        return $this->render('update', [
            'model' => $model,
            'regioes' => $regioes,
        ]);
    }

    /**
     * Deleta um modelo Cliente existente.
     * Se a exclusão for bem-sucedida, o navegador será redirecionado para a página 'index'.
     * @param string $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        try {
            // Exclusão lógica ao invés de física
            $model->ativo = false;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Cliente desativado com sucesso!');
            } else {
                Yii::$app->session->setFlash('error', 'Erro ao desativar cliente.');
            }
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Erro ao desativar cliente: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Busca CEP via API ViaCEP (método AJAX)
     * @return \yii\web\Response
     */
    public function actionBuscarCep()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $cep = Yii::$app->request->post('cep');
        $cep = preg_replace('/[^0-9]/', '', $cep);
        
        if (strlen($cep) !== 8) {
            return ['success' => false, 'message' => 'CEP inválido'];
        }
        
        try {
            $url = "https://viacep.com.br/ws/{$cep}/json/";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if (isset($data['erro'])) {
                return ['success' => false, 'message' => 'CEP não encontrado'];
            }
            
            return [
                'success' => true,
                'data' => [
                    'logradouro' => $data['logradouro'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'cidade' => $data['localidade'] ?? '',
                    'estado' => $data['uf'] ?? '',
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao buscar CEP'];
        }
    }

    /**
     * Importação de clientes via arquivo CSV.
     * Checa se o CPF já está cadastrado para este tenant. Se sim, ignora a linha e passa para a próxima.
     * @return string|\yii\web\Response
     */
    public function actionImportarCsv()
    {
        $tenantId = \app\components\TenantHelper::getId();

        if (Yii::$app->request->isPost) {
            $file = \yii\web\UploadedFile::getInstanceByName('csv_file');
            
            if (!$file) {
                // Tenta carregar via DynamicModel se enviado por ActiveForm
                $uploadModel = new \yii\base\DynamicModel(['file']);
                $uploadModel->addRule('file', 'file', ['extensions' => ['csv', 'txt'], 'checkExtensionByMimeType' => false]);
                $file = \yii\web\UploadedFile::getInstance($uploadModel, 'file');
            }

            if (!$file) {
                Yii::$app->session->setFlash('error', 'Por favor, selecione um arquivo CSV para importar.');
                return $this->redirect(['importar-csv']);
            }

            if ($file->extension && !in_array(strtolower($file->extension), ['csv', 'txt'])) {
                Yii::$app->session->setFlash('error', 'Formato de arquivo inválido. Envie um arquivo .csv ou .txt');
                return $this->redirect(['importar-csv']);
            }

            $handle = @fopen($file->tempName, 'r');
            if ($handle === false) {
                Yii::$app->session->setFlash('error', 'Não foi possível ler o arquivo CSV enviado.');
                return $this->redirect(['importar-csv']);
            }

            // Detectar delimitador (vírgula ou ponto e vírgula)
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                Yii::$app->session->setFlash('error', 'O arquivo CSV enviado está vazio.');
                return $this->redirect(['importar-csv']);
            }

            $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
            
            // Reposicionar ponteiro e ignorar UTF-8 BOM se houver
            rewind($handle);
            $checkBom = fread($handle, 3);
            if ($checkBom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Ler cabeçalho
            $rawHeader = fgetcsv($handle, 0, $delimiter);
            if (!$rawHeader) {
                fclose($handle);
                Yii::$app->session->setFlash('error', 'Não foi possível ler o cabeçalho do arquivo CSV.');
                return $this->redirect(['importar-csv']);
            }

            // Mapear colunas do cabeçalho
            $headerMap = [];
            $accentMap = [
                'á'=>'a', 'à'=>'a', 'ã'=>'a', 'â'=>'a', 'ä'=>'a',
                'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
                'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
                'ó'=>'o', 'ò'=>'o', 'õ'=>'o', 'ô'=>'o', 'ö'=>'o',
                'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
                'ç'=>'c', 'ñ'=>'n',
                'Á'=>'a', 'À'=>'a', 'Ã'=>'a', 'Â'=>'a', 'Ä'=>'a',
                'É'=>'e', 'È'=>'e', 'Ê'=>'e', 'Ë'=>'e',
                'Í'=>'i', 'Ì'=>'i', 'Î'=>'i', 'Ï'=>'i',
                'Ó'=>'o', 'Ò'=>'o', 'Õ'=>'o', 'Ô'=>'o', 'Ö'=>'o',
                'Ú'=>'u', 'Ù'=>'u', 'Û'=>'u', 'Ü'=>'u',
                'Ç'=>'c', 'Ñ'=>'n'
            ];
            foreach ($rawHeader as $index => $colName) {
                // Remove acentos e caracteres especiais para comparação flexível
                $colSemAcento = strtr($colName, $accentMap);
                $cleanCol = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-', '/'], '_', $colSemAcento))));
                $headerMap[$cleanCol] = $index;
            }

            // Buscar regiões do tenant para vínculo por nome (opcional)
            $regioesExistentes = Regioes::find()
                ->where(['usuario_id' => $tenantId])
                ->all();
            $regioesMap = [];
            foreach ($regioesExistentes as $r) {
                $regioesMap[mb_strtolower(trim($r->nome))] = $r->id;
            }

            $totalLinhas = 0;
            $cadastrados = 0;
            $ignoradosCpf = 0;
            $errosCount = 0;
            $detalhesErros = [];

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Ignorar linhas em branco
                if (empty(array_filter($row, function($v) { return trim($v) !== ''; }))) {
                    continue;
                }

                $totalLinhas++;
                $linhaNum = $totalLinhas + 1; // +1 por causa do cabeçalho

                // Função de busca de colunas por apelidos
                $getCol = function($aliases) use ($headerMap, $row) {
                    foreach ((array)$aliases as $alias) {
                        if (isset($headerMap[$alias]) && isset($row[$headerMap[$alias]])) {
                            $val = trim($row[$headerMap[$alias]]);
                            if ($val !== '') return $val;
                        }
                    }
                    return null;
                };

                $nome = $getCol(['nome_completo', 'nome', 'cliente', 'razao_social', 'nome_fantasia']);
                $cpf = $getCol(['cpf', 'cpf_cnpj', 'documento']);
                $cnpj = $getCol(['cnpj']);
                $telefone = $getCol(['telefone', 'celular', 'fone', 'tel']);
                $email = $getCol(['email', 'e_mail']);
                $logradouro = $getCol(['endereco_logradouro', 'logradouro', 'rua', 'endereco']);
                $numero = $getCol(['endereco_numero', 'numero', 'num']);
                $complemento = $getCol(['endereco_complemento', 'complemento']);
                $bairro = $getCol(['endereco_bairro', 'bairro']);
                $cidade = $getCol(['endereco_cidade', 'cidade']);
                $estado = strtoupper($getCol(['endereco_estado', 'uf', 'estado']) ?? '');
                $cep = $getCol(['endereco_cep', 'cep']);
                $pontoReferencia = $getCol(['ponto_referencia', 'referencia']);
                $observacoes = $getCol(['observacoes', 'obs']);
                $regiaoNome = $getCol(['regiao', 'regiao_nome']);

                // Sanitizar CPF (apenas números)
                $cpfLimpo = $cpf ? preg_replace('/[^0-9]/', '', $cpf) : null;
                $cnpjLimpo = $cnpj ? preg_replace('/[^0-9]/', '', $cnpj) : null;

                // REQUISITO CHAVE: Se tiver CPF limpo, verificar se já existe no banco para o tenant
                if (!empty($cpfLimpo)) {
                    $existeCpf = Clientes::find()
                        ->where(['usuario_id' => $tenantId, 'cpf' => $cpfLimpo])
                        ->exists();

                    if ($existeCpf) {
                        $ignoradosCpf++;
                        continue; // IGNORA E PASSA PARA A PRÓXIMA IMPORTAÇÃO
                    }
                }

                // Também verificar por CNPJ se preenchido
                if (!empty($cnpjLimpo)) {
                    $existeCnpj = Clientes::find()
                        ->where(['usuario_id' => $tenantId, 'cnpj' => $cnpjLimpo])
                        ->exists();

                    if ($existeCnpj) {
                        $ignoradosCpf++;
                        continue; // IGNORA E PASSA PARA A PRÓXIMA IMPORTAÇÃO
                    }
                }

                // Validação mínima: nome completo
                if (empty($nome)) {
                    $errosCount++;
                    $detalhesErros[] = "Linha {$linhaNum}: O nome do cliente é obrigatório.";
                    continue;
                }

                // Criar novo cliente
                $cliente = new Clientes();
                $cliente->usuario_id = $tenantId;
                $cliente->nome_completo = mb_substr($nome, 0, 150);
                $cliente->cpf = $cpfLimpo;
                $cliente->cnpj = $cnpjLimpo;
                
                // Tipo de Pessoa: F se CPF ou default, J se CNPJ
                if (!empty($cnpjLimpo) || strlen($cpfLimpo ?? '') > 11) {
                    $cliente->tipo_pessoa = 'J';
                    $cliente->razao_social = mb_substr($nome, 0, 150);
                } else {
                    $cliente->tipo_pessoa = 'F';
                }

                $cliente->telefone = $telefone ? mb_substr($telefone, 0, 20) : null;
                $cliente->email = $email ? mb_substr($email, 0, 100) : null;
                $cliente->endereco_logradouro = $logradouro ? mb_substr($logradouro, 0, 255) : null;
                $cliente->endereco_numero = $numero ? mb_substr($numero, 0, 20) : null;
                $cliente->endereco_complemento = $complemento ? mb_substr($complemento, 0, 100) : null;
                $cliente->endereco_bairro = $bairro ? mb_substr($bairro, 0, 100) : null;
                $cliente->endereco_cidade = $cidade ? mb_substr($cidade, 0, 100) : null;
                $cliente->endereco_estado = strlen($estado) === 2 ? $estado : null;
                $cliente->endereco_cep = $cep ? preg_replace('/[^0-9]/', '', $cep) : null;
                $cliente->ponto_referencia = $pontoReferencia;
                $cliente->observacoes = $observacoes;
                $cliente->ativo = true;

                // Tentar vincular região por nome
                if ($regiaoNome && isset($regioesMap[mb_strtolower(trim($regiaoNome))])) {
                    $cliente->regiao_id = $regioesMap[mb_strtolower(trim($regiaoNome))];
                }

                if ($cliente->save()) {
                    $cadastrados++;
                } else {
                    $errosCount++;
                    $msgs = [];
                    foreach ($cliente->errors as $field => $errList) {
                        $msgs[] = implode(', ', $errList);
                    }
                    $detalhesErros[] = "Linha {$linhaNum} (" . Html::encode($nome) . "): " . implode(' | ', $msgs);
                }
            }

            fclose($handle);

            // Montar mensagem de resumo Flash
            $summaryMsg = "<strong>Importação concluída!</strong><br>" .
                "• Total de linhas lidas: <strong>{$totalLinhas}</strong><br>" .
                "• Registros cadastrados com sucesso: <strong style='color:#16a34a;'>{$cadastrados}</strong><br>" .
                "• Ignorados (CPF/CNPJ já cadastrado): <strong style='color:#d97706;'>{$ignoradosCpf}</strong>";

            if ($errosCount > 0) {
                $summaryMsg .= "<br>• Falhas de validação: <strong style='color:#dc2626;'>{$errosCount}</strong>";
                if (!empty($detalhesErros)) {
                    $summaryMsg .= "<br><small>" . implode('<br>', array_slice($detalhesErros, 0, 5)) . (count($detalhesErros) > 5 ? '<br>...' : '') . "</small>";
                }
            }

            Yii::$app->session->setFlash($cadastrados > 0 ? 'success' : ($ignoradosCpf > 0 ? 'warning' : 'info'), $summaryMsg);
            return $this->redirect(['index']);
        }

        return $this->render('importar');
    }

    /**
     * Baixa um modelo CSV de exemplo para importação de clientes.
     */
    public function actionBaixarModeloCsv()
    {
        $filename = 'modelo_importacao_clientes.csv';
        $content = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content .= "nome_completo;cpf;telefone;email;logradouro;numero;complemento;bairro;cidade;estado;cep;observacoes\n";
        $content .= "João da Silva;12345678901;(11) 99999-8888;joao@exemplo.com;Rua das Flores;100;Apto 12;Centro;São Paulo;SP;01001000;Cliente VIP\n";
        $content .= "Maria Oliveira;98765432100;(21) 98888-7777;maria@exemplo.com;Avenida Brasil;2500;;Jardins;Rio de Janeiro;RJ;20000000;\n";

        return Yii::$app->response->sendContentAsFile($content, $filename, [
            'mimeType' => 'text/csv; charset=UTF-8',
            'inline' => false
        ]);
    }

    /**
     * Encontra o modelo Cliente baseado em seu valor de chave primária.
     * Se o modelo não for encontrado, uma exceção HTTP 404 será lançada.
     * @param string $id
     * @return PrestClientes o modelo carregado
     * @throws NotFoundHttpException se o modelo não puder ser encontrado
     */
    protected function findModel($id)
    {
        if (($model = Clientes::findOne(['id' => $id, 'usuario_id' => Yii::$app->user->id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('O cliente solicitado não existe.');
    }
}