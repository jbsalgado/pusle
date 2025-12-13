<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\Expression;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\models\Usuario;
use app\modules\vendas\models\Categoria;
use app\modules\vendas\models\VendaItem;
use app\modules\vendas\models\ProdutoFoto;
use app\modules\vendas\models\DadosFinanceiros;
use app\modules\vendas\helpers\PricingHelper;

/**
 * ============================================================================================================
 * Model: Produto
 * ============================================================================================================
 * Tabela: prest_produtos
 *
 * @property string $id
 * @property string $usuario_id
 * @property string $categoria_id
 * @property string $nome
 * @property string $descricao
 * @property string $codigo_referencia
 * @property float $preco_custo
 * @property float $valor_frete
 * @property float $preco_venda_sugerido
 * @property float $margem_lucro_percentual
 * @property float $markup_percentual
 * @property integer $estoque_atual
 * @property integer $estoque_minimo
 * @property integer $ponto_corte
 * @property string $localizacao
 * @property boolean $ativo
 * @property string $data_criacao
 * @property string $data_atualizacao
 * @property boolean $permite_parcelamento
 * @property float $preco_promocional
 * @property string $data_inicio_promocao
 * @property string $data_fim_promocao
 *
 * @property Usuario $usuario
 * @property Categoria $categoria
 * @property ProdutoFoto[] $fotos
 * @property VendaItem[] $vendaItens
 * @property DadosFinanceiros|null $dadosFinanceiros
 */
class Produto extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prest_produtos';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'data_criacao',
                'updatedAtAttribute' => 'data_atualizacao',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['usuario_id', 'nome', 'preco_venda_sugerido'], 'required'],
            [['usuario_id', 'categoria_id'], 'string'],
            [['descricao'], 'string'],
            [['preco_custo', 'valor_frete', 'preco_venda_sugerido', 'preco_promocional'], 'number', 'min' => 0],
            [['margem_lucro_percentual'], 'number', 'min' => 0, 'max' => 99.99], // Margem: 0-99.99%
            [['markup_percentual'], 'number', 'min' => 0], // ✅ Markup: sem limite máximo (pode ser qualquer valor positivo)
            // Validação: impedir prejuízo (margem negativa)
            [['preco_venda_sugerido'], 'validatePrejuizo'],
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'integer', 'min' => 0, 'skipOnEmpty' => false],
            [['estoque_atual'], 'default', 'value' => 0],
            [['estoque_minimo'], 'default', 'value' => 10],
            [['ponto_corte'], 'default', 'value' => 5],
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'filter', 'filter' => function($value) {
                // ✅ Converte string vazia para 0, mantém números inteiros
                if ($value === '' || $value === null) {
                    return 0;
                }
                return (int) $value;
            }],
            // Validação: ponto_corte deve ser maior ou igual a estoque_minimo
            [['ponto_corte'], 'compare', 'compareAttribute' => 'estoque_minimo', 'operator' => '>=', 'skipOnEmpty' => false, 'message' => 'O ponto de corte deve ser maior ou igual ao estoque mínimo.'],
            [['valor_frete'], 'default', 'value' => 0],
            [['ativo', 'permite_parcelamento'], 'boolean'],
            [['ativo'], 'default', 'value' => true],
            [['permite_parcelamento'], 'default', 'value' => false],
            [['data_inicio_promocao', 'data_fim_promocao'], 'safe'],
            [['estoque_atual', 'estoque_minimo', 'ponto_corte'], 'safe'], // ✅ Garante que os campos podem ser carregados via load()
            [['nome'], 'string', 'max' => 150],
            [['codigo_referencia'], 'string', 'max' => 50],
            [['localizacao'], 'string', 'max' => 30],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
            // Código de referência único por usuário (validação customizada)
            [['codigo_referencia'], 'validateCodigoReferenciaUnico'],
            // Validação de promoção: se tem preço promocional, deve ter datas
            ['preco_promocional', 'validatePromocao'],
        ];
    }

    /**
     * Validação customizada: impedir prejuízo (margem negativa)
     */
    public function validatePrejuizo($attribute, $params)
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo ?? 0, $this->valor_frete ?? 0);
        
        if ($custoTotal > 0 && $this->preco_venda_sugerido > 0) {
            // Busca configuração financeira (específica do produto ou global)
            $dadosFinanceiros = DadosFinanceiros::getConfiguracaoParaProduto($this->id, $this->usuario_id);
            
            $provaReal = PricingHelper::calcularProvaReal(
                $this->preco_venda_sugerido,
                $custoTotal,
                $dadosFinanceiros->taxa_fixa_percentual,
                $dadosFinanceiros->taxa_variavel_percentual
            );
            
            if ($provaReal['lucro_real'] < 0) {
                $this->addError($attribute, "⚠️ ATENÇÃO: Este preço resultará em PREJUÍZO de R$ " . number_format(abs($provaReal['lucro_real']), 2, ',', '.') . ". Ajuste o preço de venda ou reduza as taxas.");
            }
        }
    }

    /**
     * Validação customizada para campos de promoção
     */
    public function validatePromocao($attribute, $params)
    {
        if (!empty($this->preco_promocional)) {
            if (empty($this->data_inicio_promocao) || empty($this->data_fim_promocao)) {
                $this->addError($attribute, 'Quando há preço promocional, as datas de início e fim são obrigatórias.');
            }
            
            if ($this->preco_promocional >= $this->preco_venda_sugerido) {
                $this->addError($attribute, 'O preço promocional deve ser menor que o preço de venda sugerido.');
            }
            
            // ✅ NOVO: Validação de prejuízo para preço promocional
            $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo ?? 0, $this->valor_frete ?? 0);
            
            if ($custoTotal > 0 && $this->preco_promocional > 0) {
                // Busca configuração financeira (específica do produto ou global)
                $dadosFinanceiros = DadosFinanceiros::getConfiguracaoParaProduto($this->id, $this->usuario_id);
                
                $provaReal = PricingHelper::calcularProvaReal(
                    $this->preco_promocional,
                    $custoTotal,
                    $dadosFinanceiros->taxa_fixa_percentual,
                    $dadosFinanceiros->taxa_variavel_percentual
                );
                
                if ($provaReal['lucro_real'] < 0) {
                    $this->addError($attribute, "⚠️ ATENÇÃO: Este preço promocional resultará em PREJUÍZO de R$ " . number_format(abs($provaReal['lucro_real']), 2, ',', '.') . ". Ajuste o preço promocional ou reduza as taxas.");
                }
            }
        }
    }
    
    /**
     * Validação customizada para garantir que código de referência seja único por usuário
     */
    public function validateCodigoReferenciaUnico($attribute, $params)
    {
        // Se o código está vazio, não valida (é opcional)
        if (empty($this->codigo_referencia)) {
            return;
        }
        
        $query = self::find()
            ->where(['usuario_id' => $this->usuario_id, 'codigo_referencia' => $this->codigo_referencia]);
        
        // Se estiver editando, exclui o próprio produto da verificação
        if (!$this->isNewRecord) {
            $query->andWhere(['!=', 'id', $this->id]);
        }
        
        if ($query->exists()) {
            $this->addError($attribute, 'Este código de referência já está em uso. Escolha outro código.');
        }
    }

    /**
     * Calcula e atualiza margem de lucro e markup automaticamente
     */
    public function calculateMargemMarkup($attribute, $params)
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        
        if ($custoTotal > 0 && $this->preco_venda_sugerido > 0) {
            $this->margem_lucro_percentual = PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
            $this->markup_percentual = PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
        } else {
            $this->margem_lucro_percentual = null;
            $this->markup_percentual = null;
        }
    }

    /**
     * Hook antes de salvar para calcular margem e markup e garantir nome na descrição
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Gera UUID se for um novo registro e não tiver ID definido
            if ($insert && empty($this->id)) {
                try {
                    // Tenta usar gen_random_uuid() do PostgreSQL (nativo, não precisa de extensão)
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    $this->id = $uuid;
                } catch (\Exception $e) {
                    // Fallback: gera UUID no PHP usando ramsey/uuid ou função nativa
                    if (function_exists('uuid_create')) {
                        $uuid = uuid_create(UUID_TYPE_RANDOM);
                        $this->id = $uuid;
                    } else {
                        // Gera UUID v4 manualmente
                        $this->id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                            mt_rand(0, 0xffff),
                            mt_rand(0, 0x0fff) | 0x4000,
                            mt_rand(0, 0x3fff) | 0x8000,
                            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                        );
                    }
                }
            }
            // 🔍 DEBUG: Log do estoque antes de salvar
            Yii::info('Estoque antes de salvar: ' . $this->estoque_atual, __METHOD__);
            
            // Converte todos os campos de texto para MAIÚSCULO
            if (!empty($this->nome)) {
                $this->nome = mb_strtoupper(trim($this->nome), 'UTF-8');
            }
            
            if (!empty($this->codigo_referencia)) {
                $this->codigo_referencia = mb_strtoupper(trim($this->codigo_referencia), 'UTF-8');
            }
            
            if (!empty($this->localizacao)) {
                $this->localizacao = mb_strtoupper(trim($this->localizacao), 'UTF-8');
            }
            
            // Garante que a descrição sempre inclua o nome do produto
            if (!empty($this->nome)) {
                $nome = $this->nome; // Já está em maiúsculo e trimado
                $descricao = !empty($this->descricao) ? trim($this->descricao) : '';
                $prefixo = $nome . ' - ';
                
                // Se a descrição não começa com o nome, adiciona
                if (empty($descricao)) {
                    $this->descricao = $nome;
                } elseif (strpos($descricao, $nome) !== 0) {
                    // Descrição não começa com o nome, adiciona
                    $this->descricao = $prefixo . mb_strtoupper($descricao, 'UTF-8');
                } elseif (strpos($descricao, $prefixo) === 0) {
                    // Já está no formato correto (nome - descrição), converte para maiúsculo
                    $parteUsuario = mb_strtoupper(substr($descricao, strlen($prefixo)), 'UTF-8');
                    $this->descricao = $prefixo . $parteUsuario;
                } elseif ($descricao === $nome) {
                    // Se a descrição é apenas o nome, mantém
                    $this->descricao = $nome;
                } else {
                    // Converte para maiúsculo
                    $this->descricao = mb_strtoupper($descricao, 'UTF-8');
                }
            } elseif (!empty($this->descricao)) {
                // Se não tem nome mas tem descrição, converte para maiúsculo
                $this->descricao = mb_strtoupper(trim($this->descricao), 'UTF-8');
            }
            
            // ✅ Calcula margem e markup, mas limita margem a 99.99% para não falhar validação
            // Markup pode ser qualquer valor positivo (sem limite)
            $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
            
            if ($custoTotal > 0 && $this->preco_venda_sugerido > 0) {
                $this->margem_lucro_percentual = PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
                $this->markup_percentual = PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
                
                // ✅ Limita margem a 99.99% para não falhar validação
                if ($this->margem_lucro_percentual > 99.99) {
                    $this->margem_lucro_percentual = 99.99;
                }
            } else {
                $this->margem_lucro_percentual = null;
                $this->markup_percentual = null;
            }
            
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'usuario_id' => 'Usuário',
            'categoria_id' => 'Categoria',
            'nome' => 'Nome',
            'descricao' => 'Descrição',
            'codigo_referencia' => 'Código de Referência',
            'preco_custo' => 'Preço de Custo',
            'valor_frete' => 'Valor do Frete',
            'preco_venda_sugerido' => 'Preço de Venda',
            'margem_lucro_percentual' => 'Margem de Lucro (%)',
            'markup_percentual' => 'Markup (%)',
            'estoque_atual' => 'Estoque Atual',
            'estoque_minimo' => 'Estoque Mínimo',
            'ponto_corte' => 'Ponto de Corte',
            'localizacao' => 'Localização',
            'ativo' => 'Ativo',
            'data_criacao' => 'Data de Cadastro',
            'data_atualizacao' => 'Última Atualização',
            'permite_parcelamento' => 'Permite Parcelamento',
            'preco_promocional' => 'Preço Promocional',
            'data_inicio_promocao' => 'Início da Promoção',
            'data_fim_promocao' => 'Fim da Promoção',
        ];
    }

    /**
     * ✅ MÉTODO fields() MODIFICADO/ADICIONADO
     * Controla quais campos são retornados por padrão na API.
     */
    public function fields()
    {
        $fields = parent::fields(); // Pega os campos padrão (colunas da tabela)

        // Adiciona a relação 'fotos' aos campos padrão
        // Isso garante que a relação seja incluída no JSON se carregada com ->with('fotos')
        $fields['fotos'] = 'fotos';

        // Adiciona campos calculados
        $fields['em_promocao'] = 'emPromocao';
        $fields['preco_final'] = 'precoFinal';

        // Descomente a linha abaixo se quiser incluir a categoria por padrão também
        // $fields['categoria'] = 'categoria';

        return $fields;
    }


    /**
     * Define quais campos e relações extras podem ser incluídos na resposta da API
     * usando o parâmetro ?expand=... na URL.
     * Como 'fotos' agora está em fields(), só precisamos de 'categoria' aqui se quisermos
     * que ela seja opcional (carregada apenas com ?expand=categoria).
     * Se 'categoria' também foi movida para fields(), este método pode ser removido
     * ou retornar um array vazio.
     */
    public function extraFields()
    {
        // 'fotos' foi movido para fields(), então só deixamos 'categoria' aqui
        return ['categoria'];
    }


    /**
     * Retorna margem de lucro em porcentagem (CORRIGIDO)
     * Margem = (Preço de Venda - Custo) / Preço de Venda * 100
     * 
     * @return float Margem de lucro em percentual
     */
    public function getMargemLucro()
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        return PricingHelper::calcularMargemLucro($custoTotal, $this->preco_venda_sugerido);
    }

    /**
     * Retorna o markup em porcentagem
     * Markup = (Preço de Venda - Custo) / Custo * 100
     * 
     * @return float Markup em percentual
     */
    public function getMarkup()
    {
        $custoTotal = PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
        return PricingHelper::calcularMarkup($custoTotal, $this->preco_venda_sugerido);
    }

    /**
     * Retorna o custo total (custo + frete)
     * 
     * @return float Custo total
     */
    public function getCustoTotal()
    {
        return PricingHelper::calcularCustoTotal($this->preco_custo, $this->valor_frete);
    }

    /**
     * ✅ NOVO: Verifica se o produto está em promoção ativa
     */
    public function getEmPromocao()
    {
        if (empty($this->preco_promocional)) {
            return false;
        }
        
        $agora = new \DateTime();
        $inicio = $this->data_inicio_promocao ? new \DateTime($this->data_inicio_promocao) : null;
        $fim = $this->data_fim_promocao ? new \DateTime($this->data_fim_promocao) : null;
        
        if ($inicio && $fim) {
            return $agora >= $inicio && $agora <= $fim;
        }
        
        return false;
    }

    /**
     * ✅ NOVO: Retorna o preço final (promocional se estiver em promoção, ou normal)
     */
    public function getPrecoFinal()
    {
        return $this->emPromocao ? $this->preco_promocional : $this->preco_venda_sugerido;
    }

    /**
     * ✅ NOVO: Retorna desconto em porcentagem
     */
    public function getDescontoPromocional()
    {
        if (!$this->emPromocao || $this->preco_venda_sugerido == 0) {
            return 0;
        }
        
        return (($this->preco_venda_sugerido - $this->preco_promocional) / $this->preco_venda_sugerido) * 100;
    }

    /**
     * Retorna foto principal do produto
     * Se não houver foto marcada como principal, retorna a primeira foto
     */
    public function getFotoPrincipal()
    {
        // Primeiro tenta buscar foto marcada como principal
        $fotoPrincipal = $this->getFotos()
            ->where(['eh_principal' => true])
            ->one();
        
        // Se não encontrou principal, retorna a primeira foto disponível
        if (!$fotoPrincipal) {
            $fotoPrincipal = $this->getFotos()
                ->orderBy(['ordem' => SORT_ASC])
                ->limit(1)
                ->one();
        }
        
        return $fotoPrincipal;
    }

    /**
     * Verifica se produto tem estoque disponível
     */
    public function temEstoque($quantidade = 1)
    {
        return $this->estoque_atual >= $quantidade;
    }

    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }

    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getFotos()
    {
        // Certifique-se de que a relação está correta e ordenada
        return $this->hasMany(ProdutoFoto::class, ['produto_id' => 'id'])
            ->orderBy(['eh_principal' => SORT_DESC, 'ordem' => SORT_ASC]);
    }

    public function getVendaItens()
    {
        return $this->hasMany(VendaItem::class, ['produto_id' => 'id']);
    }

    /**
     * Retorna a relação com DadosFinanceiros (configuração específica do produto)
     * Se não houver específica, retorna null (use getDadosFinanceirosOuGlobal() para buscar global)
     */
    public function getDadosFinanceiros()
    {
        return $this->hasOne(DadosFinanceiros::class, ['produto_id' => 'id']);
    }

    /**
     * Retorna a configuração financeira para este produto
     * Busca primeiro configuração específica, depois global
     * 
     * @return DadosFinanceiros
     */
    public function getDadosFinanceirosOuGlobal()
    {
        return DadosFinanceiros::getConfiguracaoParaProduto($this->id, $this->usuario_id);
    }

    /**
     * Retorna produtos ativos para dropdown
     */
    public static function getListaDropdown($usuarioId = null, $apenasComEstoque = false)
    {
        $usuarioId = $usuarioId ?: Yii::$app->user->id;

        $query = self::find()
            ->where(['usuario_id' => $usuarioId, 'ativo' => true]);

        if ($apenasComEstoque) {
            $query->andWhere(['>', 'estoque_atual', 0]);
        }

        return $query->select(['nome', 'id'])
            ->indexBy('id')
            ->orderBy(['nome' => SORT_ASC])
            ->column();
    }
    
    /**
     * Gera código de referência único baseado na categoria
     * Formato: SIGLA_CATEGORIA-0000 (ex: ELET-0000, ROUP-0001, até 9999)
     * 
     * @param string $categoriaId ID da categoria
     * @param string $usuarioId ID do usuário
     * @return string Código de referência gerado
     */
    public static function gerarCodigoReferencia($categoriaId, $usuarioId)
    {
        // Busca a categoria
        $categoria = Categoria::findOne($categoriaId);
        
        if (!$categoria || $categoria->usuario_id !== $usuarioId) {
            return '';
        }
        
        // Gera sigla da categoria (primeiras letras, maiúsculas, sem espaços)
        $nome = $categoria->nome;
        $sigla = self::gerarSiglaCategoria($nome);
        
        // Busca o último código da categoria para gerar o próximo sequencial
        $ultimoCodigo = self::find()
            ->where(['usuario_id' => $usuarioId, 'categoria_id' => $categoriaId])
            ->andWhere(['like', 'codigo_referencia', $sigla . '-%', false])
            ->orderBy(['codigo_referencia' => SORT_DESC])
            ->select('codigo_referencia')
            ->scalar();
        
        $sequencial = 0;
        
        if ($ultimoCodigo) {
            // Extrai o número do último código (ex: ELET-0000 -> 0000)
            if (preg_match('/' . preg_quote($sigla, '/') . '-(\d+)$/', $ultimoCodigo, $matches)) {
                $sequencial = (int)$matches[1] + 1;
            }
        }
        
        // Verifica se excedeu o limite de 9999
        if ($sequencial > 9999) {
            // Se excedeu, tenta encontrar um número disponível ou retorna vazio
            $sequencial = 0;
            for ($i = 0; $i <= 9999; $i++) {
                $codigoTeste = $sigla . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
                if (!self::find()
                    ->where(['usuario_id' => $usuarioId, 'codigo_referencia' => $codigoTeste])
                    ->exists()) {
                    $sequencial = $i;
                    break;
                }
            }
            
            // Se não encontrou nenhum disponível, retorna vazio
            if ($sequencial > 9999) {
                return '';
            }
        }
        
        // Formata o código: SIGLA-0000 (4 dígitos, de 0000 a 9999)
        $codigo = $sigla . '-' . str_pad($sequencial, 4, '0', STR_PAD_LEFT);
        
        // Verifica se o código já existe (garantia de unicidade)
        $tentativas = 0;
        $maxTentativas = 10000;
        
        while (self::find()
            ->where(['usuario_id' => $usuarioId, 'codigo_referencia' => $codigo])
            ->exists() && $tentativas < $maxTentativas && $sequencial <= 9999) {
            $sequencial++;
            if ($sequencial > 9999) {
                // Se excedeu, tenta encontrar um número disponível
                $encontrado = false;
                for ($i = 0; $i <= 9999; $i++) {
                    $codigoTeste = $sigla . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
                    if (!self::find()
                        ->where(['usuario_id' => $usuarioId, 'codigo_referencia' => $codigoTeste])
                        ->exists()) {
                        $sequencial = $i;
                        $encontrado = true;
                        break;
                    }
                }
                if (!$encontrado) {
                    return ''; // Não há mais códigos disponíveis
                }
            }
            $codigo = $sigla . '-' . str_pad($sequencial, 4, '0', STR_PAD_LEFT);
            $tentativas++;
        }
        
        return $codigo;
    }
    
    /**
     * Gera sigla a partir do nome da categoria
     * Ex: "Eletrônicos" -> "ELET", "Roupas e Acessórios" -> "ROUP"
     * 
     * @param string $nome Nome da categoria
     * @return string Sigla gerada (máximo 4 caracteres)
     */
    protected static function gerarSiglaCategoria($nome)
    {
        // Remove acentos e caracteres especiais
        $nome = self::removeAcentos($nome);
        
        // Remove palavras comuns (artigos, preposições)
        $palavrasIgnorar = ['de', 'da', 'do', 'das', 'dos', 'e', 'ou', 'a', 'o', 'as', 'os'];
        $palavras = explode(' ', strtolower($nome));
        $palavras = array_filter($palavras, function($palavra) use ($palavrasIgnorar) {
            return !in_array($palavra, $palavrasIgnorar) && strlen($palavra) > 0;
        });
        
        // Se não há palavras válidas, usa as primeiras letras do nome
        if (empty($palavras)) {
            $sigla = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $nome), 0, 4));
            return $sigla ?: 'PROD';
        }
        
        // Pega as primeiras letras de cada palavra (máximo 4 caracteres)
        $sigla = '';
        foreach ($palavras as $palavra) {
            if (strlen($sigla) >= 4) {
                break;
            }
            $sigla .= strtoupper(substr($palavra, 0, 1));
        }
        
        // Se a sigla tem menos de 3 caracteres, completa com letras do nome
        if (strlen($sigla) < 3) {
            $nomeLimpo = preg_replace('/[^a-zA-Z0-9]/', '', $nome);
            $sigla = strtoupper(substr($nomeLimpo, 0, 4));
        }
        
        return $sigla ?: 'PROD';
    }
    
    /**
     * Remove acentos de uma string
     * 
     * @param string $string
     * @return string
     */
    protected static function removeAcentos($string)
    {
        $acentos = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c',
            'Ñ' => 'N', 'ñ' => 'n',
        ];
        
        return strtr($string, $acentos);
    }
}