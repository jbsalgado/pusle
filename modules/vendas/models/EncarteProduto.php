<?php

namespace app\modules\vendas\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Model: EncarteProduto
 * Tabela: prest_encarte_produtos
 *
 * @property string $id
 * @property string $encarte_id
 * @property string $produto_id
 * @property string|null $variante_id
 * @property string|null $cor
 * @property string|null $tamanho
 * @property float|null $quantidade
 * @property float $preco_oferta
 * @property int $ordem
 * @property bool $destaque
 * @property string $tag_promocional
 * @property string $created_at
 *
 * @property Encarte $encarte
 * @property Produto $produto
 * @property ProdutoVariante|null $variante
 */
class EncarteProduto extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%prest_encarte_produtos}}';
    }

    public function rules()
    {
        return [
            [['encarte_id', 'produto_id'], 'required'],
            [['encarte_id'], 'string', 'max' => 36],
            [['produto_id', 'variante_id'], 'string', 'max' => 36],
            [['cor'], 'string', 'max' => 50],
            [['tamanho'], 'string'],
            [['tag_promocional'], 'string', 'max' => 50],
            [['preco_oferta', 'quantidade'], 'number'],
            [['ordem'], 'integer'],
            [['destaque'], 'boolean'],
            [['ordem'], 'default', 'value' => 0],
            [['destaque'], 'default', 'value' => false],
            [['tag_promocional'], 'default', 'value' => 'AUTO'],
            [['quantidade'], 'default', 'value' => 0],
            [['cor'], 'filter', 'filter' => function ($val) {
                return $val ? mb_strtoupper(trim($val), 'UTF-8') : null;
            }],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'encarte_id' => 'Encarte',
            'produto_id' => 'Produto',
            'variante_id' => 'Variante (Matriz)',
            'cor' => 'Cor',
            'tamanho' => 'Tamanho',
            'quantidade' => 'Quantidade Disponível',
            'preco_oferta' => 'Preço de Oferta',
            'ordem' => 'Ordem',
            'destaque' => 'Destaque Especial',
            'tag_promocional' => 'Tag Promocional',
            'created_at' => 'Data de Inserção',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert && empty($this->id)) {
                try {
                    $uuid = Yii::$app->db->createCommand("SELECT gen_random_uuid()")->queryScalar();
                    if (!empty($uuid)) {
                        $this->id = $uuid;
                    }
                } catch (\Exception $e) {
                    $this->id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
            }
            return true;
        }
        return false;
    }

    public function getEncarte()
    {
        return $this->hasOne(Encarte::class, ['id' => 'encarte_id']);
    }

    public function getProduto()
    {
        return $this->hasOne(Produto::class, ['id' => 'produto_id']);
    }

    public function getVariante()
    {
        return $this->hasOne(ProdutoVariante::class, ['id' => 'variante_id']);
    }

    /**
     * Retorna o objeto de foto mais adequado para este item
     * (Prioriza foto da cor/variante se for matriz)
     * @return ProdutoFoto|null
     */
    public function getFoto()
    {
        if (!$this->produto) return null;

        // 1. Foto vinculada à cor da variante
        if (!empty($this->cor)) {
            $fotosCor = $this->produto->getFotosPorCor($this->cor);
            if (!empty($fotosCor)) {
                return $fotosCor[0];
            }
        }

        // 2. Foto vinculada diretamente à variante_id
        if (!empty($this->variante_id)) {
            $fotoVar = ProdutoFoto::find()
                ->where(['variante_id' => $this->variante_id])
                ->orderBy(['ordem' => SORT_ASC])
                ->one();
            if ($fotoVar) return $fotoVar;
        }

        // 3. Fallback: Foto Principal do produto ou primeira foto
        return $this->produto->fotoPrincipal ?: ($this->produto->fotos[0] ?? null);
    }

    /**
     * Retorna a URL pública da foto
     * @return string|null
     */
    public function getFotoUrl()
    {
        $foto = $this->getFoto();
        if ($foto) {
            return method_exists($foto, 'getUrlCompleta') ? $foto->getUrlCompleta() : $foto->getUrl();
        }

        if ($this->produto && $this->produto->categoria && !empty($this->produto->categoria->foto_path)) {
            $caminhoCatAbs = Yii::getAlias('@app/web/') . ltrim($this->produto->categoria->foto_path, '/');
            if (file_exists($caminhoCatAbs)) {
                return \yii\helpers\Url::to('@web/' . ltrim($this->produto->categoria->foto_path, '/'), true);
            }
        }

        return null;
    }

    /**
     * Retorna o caminho absoluto local da foto para uso em mPDF
     * @return string|null
     */
    public function getFotoCaminhoLocal()
    {
        $foto = $this->getFoto();
        if ($foto && !empty($foto->arquivo_path)) {
            $caminhoLocal = Yii::getAlias('@app/web/') . ltrim($foto->arquivo_path, '/');
            if (file_exists($caminhoLocal)) {
                return $caminhoLocal;
            }
        }

        if ($this->produto && $this->produto->categoria && !empty($this->produto->categoria->foto_path)) {
            $caminhoLocalCat = Yii::getAlias('@app/web/') . ltrim($this->produto->categoria->foto_path, '/');
            if (file_exists($caminhoLocalCat)) {
                return $caminhoLocalCat;
            }
        }

        return null;
    }

    /**
     * Retorna se este item é uma variação de matriz
     * @return bool
     */
    public function getEhMatriz()
    {
        return !empty($this->cor) || !empty($this->tamanho) || !empty($this->variante_id);
    }

    /**
     * Retorna a lista estruturada de tamanhos e seus respectivos estoques
     * @return array Array de [['tamanho' => '34', 'qtd' => 10], ...]
     */
    public function getGradeTamanhos(): array
    {
        if (empty($this->tamanho)) {
            return [];
        }
        $decoded = json_decode($this->tamanho, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Fallback para string simples escalar
        return [
            ['tamanho' => (string)$this->tamanho, 'qtd' => (float)$this->quantidade]
        ];
    }

    /**
     * Retorna o nome de exibição formatado (Produto - Cor para cards de matriz agrupados por cor)
     * @return string
     */
    public function getNomeExibicao()
    {
        $nomeBase = $this->produto ? $this->produto->nome : 'Produto';
        if (!empty($this->cor)) {
            return $nomeBase . ' - ' . $this->cor;
        }
        return $nomeBase;
    }

    public function getPrecoFinal()
    {
        if (!empty($this->preco_oferta) && $this->preco_oferta > 0) {
            return (float)$this->preco_oferta;
        }
        if ($this->variante && (float)$this->variante->preco_venda_sugerido > 0) {
            return (float)$this->variante->preco_venda_sugerido;
        }
        return $this->produto ? (float)$this->produto->preco_venda_sugerido : 0.0;
    }
}
