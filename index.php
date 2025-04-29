<?php

use App\Models\EventLogs;
use Stdimitrov\Orm\Tasks\CreateModels;

require_once './vendor/autoload.php';
require_once './models/EventLogs.php';
//
putenv("DB_HOST=127.0.0.1");
putenv("DB_USER=root");
putenv("DB_PASS=root");
putenv("DB_NAME=test");
putenv("DB_PORT_RO=3306");
putenv("DB_PORT_RW=3306");
putenv("MODEL_NAMESPACE=App\Models");

//
//new CreateModels()->run('App\Models', __DIR__ . '/models');


class EventLogsRepository extends EventLogs
{
    /**
     * @throws Exception
     */
    public function findById(int $id): ?EventLogs
    {
        $sql = 'SELECT * FROM event_logs WHERE id = ?';
        return $this->fetchOne($sql, [$id]);
    }

    /**
     * @return EventLogs[]
     * @throws Exception
     */
    public function list(): array
    {
        $sql = 'SELECT * FROM event_logs';
        return $this->fetchAll($sql);
    }
}


$logs = new EventLogsRepository();

$log = $logs->findById(108);
var_dump($log);die;
//var_dump($log->);die;