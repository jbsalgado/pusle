<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\helpers;

use yii\helpers\StringHelper;
use frontend\modules\cadastros\models\TabNomesCurtos;

/**
 * Description of StringHelper
 *
 * @author barbosa
 */
class StrHelper {

    public static function naoAbreviar($nome) {
        //Transforma nomes em tudo maiúsculo
        $nome = strtoupper($nome);

        //pega nomes curtos pré cadastrados
        $pre = TabNomesCurtos::find()->select('*')->all();
        //$pre=['DA','DE','DO', 'E','DAS','DOS','DEL','VON','SÁ'];
        //expressao regular para nomes
        $e = "/(?=^.{2,100}$)^[A-Za-z'áàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ]+(?:[ ](?:das?|dos?|de|e|[A-Za-z'áàâãéèêíïóôõöúçñÁÀÂÃÉÈÍÏÓÔÕÖÚÇÑ]+))*$/";
        //verifica se nome passa pela expressão regular
        $ok = preg_match($e, $nome);
        if ($ok == 0) {
            return ["SUCCESS" => 0, "ERRO" => "ESPAÇOS A MAIS OU PONTOS"];
        } else {
            //separa no em um array de nomes curtos separados por espaço
            $nm = explode(" ", $nome);
            $arrayPreposicoes = new \ArrayObject();
            foreach ($nm as $n) {
                //pega só os nomes curtos com 3 ou menos caracteres                
                if (strlen($n) <= 3) {
                    $arrayPreposicoes->append($n);
                }
            }
            //cria um array simples com todos os nomes curtos pré cadastrados
            $nomes = [];
            for ($i = 0; $i < count($pre); $i++) {
                $nomes[$i] = $pre[$i]->nome;
            }

            //print_r($nomes);
            foreach ($arrayPreposicoes as $i => $ap) {
                //verifica se os nomes curtos do nome informado tem um equivalente pré cadastrado
                $key = array_search($ap, $nomes);
                if ($key === false) {
                    return ["SUCCESS" => 0, "ERRO" => "NOME INVÁLIDO: " . $ap];
                }
            }
        }
        //se passou por todas as validações retorna sucesso=1 e erro="SEM ERRO"
        return ["SUCCESS" => 1, "ERRO" => "SEM ERRO"];
    }

    public static function random_color_part() {
        return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    public static function random_color() {
        $random_color = '#' . StrHelper::random_color_part() . StrHelper::random_color_part() . StrHelper::random_color_part();
        return $random_color;
    }

    public static function removerEspacos($string) {
        $semEspacos = str_replace(' ', '', $string);
        return $semEspacos;
    }

    public static function capitalizarPalavras($string) {
        // Lista de palavras que devem ser ignoradas
        $palavrasIgnoradas = ['a','e','o','de', 'do','da','das','dos','von','com','que',];
    
        // Dividir a string em palavras usando espaço como separador
        $palavras = explode(' ', $string);
    
        // Iterar pelas palavras e aplicar a capitalização apenas se não estiver na lista de palavras ignoradas
        foreach ($palavras as &$palavra) {
            // Converter a palavra para minúsculas para tornar a comparação insensível a maiúsculas e minúsculas
            $palavraMinusculas = mb_strtolower($palavra, 'UTF-8');
    
            // Verificar se a palavra não está na lista de palavras ignoradas
            if (!in_array($palavraMinusculas, $palavrasIgnoradas)) {
                // Capitalizar a primeira letra da palavra
                $palavra = mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
            }
        }
    
        // Reunir as palavras novamente em uma string
        $novaString = implode(' ', $palavras);
    
        return $novaString;
    }

}
