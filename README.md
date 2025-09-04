<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 Basic Project Template</h1>
    <br>
</p>

A custom Yii project model with adminLTE template, RBAC and User components, Menu role based.

## How to use:

After download or clone:


1 - Run composer update

2 - Configure a db

 - In config/db.php:
    ```php
        'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'pgsql:host=localhost;port=5432;dbname=name',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8',
                ],
        ]
    ```

3 - Run migrations:

php yii migrate/up --migrationPath=@vendor/dektrium/yii2-user/migrations

php yii migrate/up --migrationPath=@yii/rbac/migrations





