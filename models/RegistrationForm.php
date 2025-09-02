<?php

namespace app\models;

use dektrium\user\models\RegistrationForm as BaseRegistration;

class RegistrationForm extends BaseRegistration{
    
    public function rules() {
        $rules = parent::rules();
        
        $rules['usernameLength'] = ['username', 'string', 'min' => 3, 'max' => 255];
        
        return $rules;
    }
}