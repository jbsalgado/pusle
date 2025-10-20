<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Sistema de Vendas',
    
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