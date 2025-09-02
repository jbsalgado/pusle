<?php

return [ 
     
          [
                        'label' => 'Usuários',
                        'icon' => 'fa fa-users',
                        'url' => '#',
                        'items' => [
                                      
                                        ['label' => 'Administrar', 'url' => ['/user/admin/index']],
                                        ['label' => 'Conta', 'url' => ['/user/settings/account']],
                                        ['label' => 'Redes', 'url' => ['/user/settings/networks']],
                                        
                                   
                                   ]
          ]
];
