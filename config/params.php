<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Sistema de Vendas',

    // Adicione a configuração da NFe aqui
    'nfe' => [
        'ambiente' => 2, // 1=Produção, 2=Homologação
        'tpEmis' => 1,   // 1=Normal
        // O Alias @app pega o caminho absoluto da raiz do projeto
        'path_certs' => '@app/dados_nfe/certs/', 
        'path_xmls'  => '@app/dados_nfe/xmls/',
        'certificado_nome' => 'meucertificado.pfx',
        'certificado_senha' => '123456', // Idealmente, use variáveis de ambiente (.env)
        'cnpj_emitente' => '00000000000191',
        'uf_emitente' => 'SP',
    ],
    
    // Configurações de Upload de Fotos
    'upload' => [
        'maxFileSize' => 5 * 1024 * 1024, // 5MB
        'allowedExtensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'maxFiles' => 10, // Máximo de fotos por produto
        'imagePath' => 'uploads/produtos',
        'thumbnailSize' => [
            'width' => 300,
            'height' => 300
        ]
    ],
    
    // Configurações de Produtos
    'produto' => [
        'defaultEstoque' => 0,
        'estoqueMinimoAlerta' => 5,
        'permiteEstoqueNegativo' => false,
    ],
    
    // Configurações de Paginação
    'pagination' => [
        'produtos' => 12,
        'categorias' => 20,
    ],
];