<?php

return [
    'adminEmail' => 'only.code.cru@gmail.com',
    'supportEmail' => 'support@example.com',
    'senderName' => 'Example.com mailer',

    // SaaS Pulse Configuration
    'pulse_asaas_wallet_id' => null, // ID da Carteira Asaas dos proprietários do PULSE
    'pulse_platform_fee_percent' => 0.005, // Taxa do PULSE (padrão 0.5%)

    // Telegram Alerts Configuration
    'telegram_bot_token' => '', // TODO: Inserir token do Bot (ex: 123456:ABC-DEF)
    'telegram_chat_id' => '',   // TODO: Inserir ID do Chat/Grupo (ex: -100123456)

    // Marketplace Integration Configuration
    'marketplace' => [
        'enabled' => false, // Desabilitado por padrão - habilitar quando estiver pronto para uso
        'mercado_livre' => false,
        'shopee' => false,
        'magazine_luiza' => false,
        'amazon' => false,
    ],

    // NFe/NFCe Configuration
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
            'ie' => '0000000000000', // Inscrição Estadual (PE) - ATUALIZAR COM IE REAL
            'iest' => '', // IE do Substituto Tributário (se houver)
            'im' => '', // Inscrição Municipal (se houver)
            'cnae' => '6201500', // CNAE principal - ATUALIZAR
            'regime_tributario' => '1', // 1=Simples Nacional, 2=Simples Excesso, 3=Normal
            'crt' => '1', // Código de Regime Tributário

            'endereco' => [
                'logradouro' => 'Rua Exemplo', // ATUALIZAR
                'numero' => '123', // ATUALIZAR
                'complemento' => '',
                'bairro' => 'Boa Viagem', // ATUALIZAR
                'codigo_municipio' => '2611606', // Código IBGE Recife
                'municipio' => 'Recife',
                'uf' => 'PE',
                'cep' => '51020000', // ATUALIZAR
                'telefone' => '8130000000', // ATUALIZAR
            ],
        ],

        // Configuração NFCe (Nota Fiscal de Consumidor Eletrônica)
        'nfce' => [
            'id_token' => '', // Token ID CSC (obter na SEFAZ PE)
            'token' => '', // CSC - Código de Segurança do Contribuinte (obter na SEFAZ PE)
            'serie' => '1', // Série da NFCe
        ],

        // Configuração NFe (Nota Fiscal Eletrônica)
        'nfe' => [
            'serie' => '1', // Série da NFe
        ],

        // URLs dos Webservices SEFAZ PE
        'webservices' => [
            'homologacao' => [
                'autorizacao' => 'https://nfehomolog.sefaz.pe.gov.br/nfe-service/services/NFeAutorizacao4',
                'retorno_autorizacao' => 'https://nfehomolog.sefaz.pe.gov.br/nfe-service/services/NFeRetAutorizacao4',
                'consulta' => 'https://nfehomolog.sefaz.pe.gov.br/nfe-service/services/NFeConsultaProtocolo4',
                'inutilizacao' => 'https://nfehomolog.sefaz.pe.gov.br/nfe-service/services/NFeInutilizacao4',
                'status_servico' => 'https://nfehomolog.sefaz.pe.gov.br/nfe-service/services/NFeStatusServico4',
            ],
            'producao' => [
                'autorizacao' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeAutorizacao4',
                'retorno_autorizacao' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeRetAutorizacao4',
                'consulta' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeConsultaProtocolo4',
                'inutilizacao' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeInutilizacao4',
                'status_servico' => 'https://nfe.sefaz.pe.gov.br/nfe-service/services/NFeStatusServico4',
            ],
        ],
    ],

    // -------------------------------------------------------------------------
    // Integração WhatsApp — Evolution API Go (Engine v0.7.1)
    // -------------------------------------------------------------------------
    // baseUrl:      URL base do serviço Go (sem barra final)
    // globalApiKey: Chave global definida no motor Go (usada em ações administrativas)
    //
    // ATENÇÃO: Em produção, atualize os valores abaixo com as credenciais reais.
    // -------------------------------------------------------------------------
    'evolution' => [
        'baseUrl'      => $_ENV['EVOLUTION_BASE_URL'] ?? getenv('EVOLUTION_BASE_URL') ?: (
            (defined('YII_ENV') && YII_ENV === 'dev') ? 'http://localhost:4000' : 'http://localhost:8080'
        ), // URL do motor Evolution API Go (autodetectada por ambiente)
        'globalApiKey' => $_ENV['EVOLUTION_API_KEY'] ?? getenv('EVOLUTION_API_KEY') ?: '429683C4C977415CAAFCCE10F7D57E11',  // Global API Key do motor Go
    ],

    // -------------------------------------------------------------------------
    // Integração Meta Graph API (Instagram Business & Facebook Pages)
    // -------------------------------------------------------------------------
    'meta_app_id' => $_ENV['META_APP_ID'] ?? getenv('META_APP_ID') ?: '',
    'meta_app_secret' => $_ENV['META_APP_SECRET'] ?? getenv('META_APP_SECRET') ?: '',
    'meta_api_version' => 'v19.0',
    'meta_token_encryption_key' => $_ENV['META_TOKEN_ENCRYPTION_KEY'] ?? getenv('META_TOKEN_ENCRYPTION_KEY') ?: 'pulse-meta-social-token-secret-key-2026',
];

