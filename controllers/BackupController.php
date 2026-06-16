<?php

namespace app\controllers;

// Use app\controllers\AuthController;
use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\web\HttpException;

/**
 * BackupController - Gerencia backups do banco de dados (PostgreSQL)
 */
class BackupController extends Controller
{
    /**
     * behaviors - Apenas administradores podem acessar
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Usuários logados (idealmente admin)
                    ],
                ],
            ],
        ];
    }

    /**
     * Action para realizar o backup
     */
    public function actionCriar()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $db = Yii::$app->db;

            // Extrai dados da DSN
            // Ex: pgsql:host=localhost;port=5432;dbname=pulse
            preg_match('/host=([^;]+)/', $db->dsn, $host);
            preg_match('/port=([^;]+)/', $db->dsn, $port);
            preg_match('/dbname=([^;]+)/', $db->dsn, $dbname);

            $host = $host[1] ?? 'localhost';
            $port = $port[1] ?? '5432';
            $dbname = $dbname[1] ?? 'pulse';
            $user = $db->username;
            $pass = $db->password;

            $backupDir = Yii::getAlias('@app/runtime/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $fileName = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

            // Define a senha via variável de ambiente para o pg_dump
            putenv("PGPASSWORD=$pass");

            // Comando pg_dump
            $command = "pg_dump -h $host -p $port -U $user $dbname > $filePath";

            exec($command, $output, $returnVar);

            // Limpa a senha do ambiente
            putenv("PGPASSWORD=");

            if ($returnVar !== 0) {
                throw new \Exception("Erro ao executar pg_dump. Código: $returnVar");
            }

            return [
                'sucesso' => true,
                'mensagem' => 'Backup criado com sucesso!',
                'arquivo' => $fileName,
                'caminho' => $filePath,
                'data' => date('d/m/Y H:i:s')
            ];
        } catch (\Exception $e) {
            Yii::error('Erro no Backup: ' . $e->getMessage(), __METHOD__);
            return [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
        }
    }
}
