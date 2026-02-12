<?php

return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderName' => 'Example.com mailer',

    // SaaS Pulse Configuration
    'pulse_asaas_wallet_id' => null, // ID da Carteira Asaas dos proprietários do PULSE
    'pulse_platform_fee_percent' => 0.005, // Taxa do PULSE (padrão 0.5%)

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
];
