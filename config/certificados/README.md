# 🔐 Certificado Digital de Teste - Only-code

**Gerado em:** 11/02/2026  
**Validade:** 365 dias (até 11/02/2027)  
**Ambiente:** Homologação/Desenvolvimento

---

## 📋 Informações do Certificado

| Campo         | Valor                 |
| ------------- | --------------------- |
| **Empresa**   | Only-code             |
| **CNPJ**      | 47.037.952/0001-43    |
| **Estado**    | Pernambuco (PE)       |
| **Cidade**    | Recife                |
| **Tipo**      | Auto-assinado (Teste) |
| **Algoritmo** | RSA 2048 bits         |
| **Formato**   | PFX/PKCS#12           |

---

## 📁 Arquivos Gerados

```
/srv/http/pulse/config/certificados/
├── only-code.key     # Chave privada (2048 bits)
├── only-code.crt     # Certificado X.509
└── only-code.pfx     # Certificado PFX (usado pela NFePHP)
```

---

## 🔑 Credenciais

**Senha do certificado PFX:** `onlycode2026`

> ⚠️ **IMPORTANTE:** Esta senha será necessária para usar o certificado no sistema.

---

## 🛠️ Configuração no Sistema

### 1. Adicionar ao `config/params.php`

```php
return [
    // ... outras configurações ...

    'nfe' => [
        'ambiente' => 'homologacao', // 'producao' ou 'homologacao'

        'certificado' => [
            'path' => __DIR__ . '/certificados/only-code.pfx',
            'senha' => 'onlycode2026',
        ],

        'emitente' => [
            'cnpj' => '47037952000143',
            'razao_social' => 'Only-code',
            'nome_fantasia' => 'Only-code',
            'ie' => '0000000000000', // Inscrição Estadual (PE)
            'regime_tributario' => '1', // 1=Simples Nacional, 3=Normal
            'crt' => '1', // Código de Regime Tributário

            'endereco' => [
                'logradouro' => 'Rua Exemplo',
                'numero' => '123',
                'complemento' => '',
                'bairro' => 'Boa Viagem',
                'codigo_municipio' => '2611606', // Código IBGE Recife
                'municipio' => 'Recife',
                'uf' => 'PE',
                'cep' => '51020000',
                'telefone' => '8130000000',
            ],
        ],

        'nfce' => [
            'id_token' => '', // Token ID CSC (obter na SEFAZ)
            'token' => '', // CSC (obter na SEFAZ)
        ],
    ],
];
```

---

## 🧪 Teste de Validação

### Script de Teste

```php
<?php
// test_certificado.php

require __DIR__ . '/vendor/autoload.php';

use NFePHP\Common\Certificate;

$certificadoPath = __DIR__ . '/config/certificados/only-code.pfx';
$senha = 'onlycode2026';

try {
    $content = file_get_contents($certificadoPath);
    $certificado = Certificate::readPfx($content, $senha);

    echo "✅ Certificado carregado com sucesso!\n\n";
    echo "📋 Informações:\n";
    echo "   CNPJ: " . $certificado->getCnpj() . "\n";
    echo "   Razão Social: " . $certificado->getCompanyName() . "\n";
    echo "   Válido de: " . $certificado->getValidFrom()->format('d/m/Y H:i:s') . "\n";
    echo "   Válido até: " . $certificado->getValidTo()->format('d/m/Y H:i:s') . "\n";
    echo "   Dias restantes: " . $certificado->getValidTo()->diff(new DateTime())->days . "\n";

} catch (\Exception $e) {
    echo "❌ Erro ao carregar certificado:\n";
    echo "   " . $e->getMessage() . "\n";
}
```

**Executar:**

```bash
php test_certificado.php
```

---

## ⚠️ Limitações

Este é um certificado **AUTO-ASSINADO** para desenvolvimento:

| Funcionalidade           | Status           |
| ------------------------ | ---------------- |
| ✅ Desenvolvimento local | Funciona         |
| ✅ Testes de integração  | Funciona         |
| ✅ Geração de XML        | Funciona         |
| ❌ Homologação SEFAZ     | **NÃO funciona** |
| ❌ Produção              | **NÃO funciona** |

---

## 🚀 Para Homologação Real

Quando estiver pronto para testar com a SEFAZ de Pernambuco:

### 1. Obter Certificado de Homologação

**Portal SEFAZ PE:**

- https://www.sefaz.pe.gov.br/NFe/

**Passos:**

1. Acessar portal da SEFAZ PE
2. Ir em "Ambiente de Homologação"
3. Solicitar credenciais de teste
4. Baixar certificado de homologação

### 2. Obter CSC (Código de Segurança do Contribuinte)

Para NFCe, é necessário:

1. Acessar portal da SEFAZ PE
2. Gerar Token ID e CSC
3. Adicionar ao `config/params.php`

---

## 📚 Próximos Passos

1. ✅ Certificado gerado
2. ⏳ Configurar `params.php`
3. ⏳ Implementar NFeBuilder
4. ⏳ Testar geração de XML
5. ⏳ Integrar com VendaController

---

## 🔒 Segurança

**Permissões aplicadas:**

```bash
drwx------ (700) /srv/http/pulse/config/certificados/
-rw------- (600) only-code.key
-rw------- (600) only-code.crt
-rw------- (600) only-code.pfx
```

> ⚠️ **NUNCA** commitar certificados no Git!

**Adicionar ao `.gitignore`:**

```
config/certificados/*.pfx
config/certificados/*.key
config/certificados/*.crt
```

---

**Gerado por:** Antigravity AI  
**Data:** 11/02/2026 11:11
