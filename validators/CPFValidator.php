<?php

namespace app\validators;

use yii\validators\Validator;

/**
 * CPFValidator class file.
 *
 * @author Thiago F Macedo (#Panurge) <thiago@internetbudi.com.br>
 * @link http://twitter.com/thiagofmacedo/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CPFValidator valida um CPF brasileiro conforme algoritimo de geração.
 * @author Thiago F Macedo <thiago@internetbudi.com.br>
 * @version 0.1
 */
class CPFValidator extends Validator
{
	/**
	 * Validates the attribute of the object.
	 * If there is any error, the error message is added to the object.
	 * @param CModel the data object being validated
	 * @param string the name of the attribute to be validated.
	 */
	public function validateAttribute( $object, $attribute ){
		if ( !$this->validaCPF( $object->$attribute ) )
            $this->addError($object, $attribute,' Não é um CPF válido.');
	}
    
    public function clientValidateAttribute($object,$attribute,$view) {
    
    }

    
    /*
     * @autor: Moacir Selínger Fernandes
     * @email: hassed@hassed.com
     * Qualquer dúvida é só mandar um email
     * http://codigofonte.uol.com.br/codigo/php/validacao/validacao-de-cpf-com-php
     * 
     * Modificada conforme indicações nos comentários de habner e calex
    */
    
    // Função que valida o CPF
    public function validaCPF($cpf)
    {	// Verifiva se o número digitado contém todos os digitos
        $cpf = str_pad(preg_replace('/[^0-9_]/', '', $cpf), 11, '0', STR_PAD_LEFT);

        // valida número sequencial 1111... 22222 ......
        for ($x=0; $x<10; $x++)
            if ( $cpf == str_repeat($x, 11) )
                return false;

        // Verifica se nenhuma das sequências abaixo foi digitada, caso seja, retorna falso
        if ( strlen($cpf) != 11 )
        {
            return false;
        }
        else
        {   // Calcula os números para verificar se o CPF é verdadeiro
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c = 0; $c < $t; $c++) {
                    $d += $cpf{$c} * (($t + 1) - $c);
                }

                $d = ((10 * $d) % 11) % 10;

                if ($cpf{$c} != $d) {
                    return false;
                }
            }

            return true;
        }
    }
    
}