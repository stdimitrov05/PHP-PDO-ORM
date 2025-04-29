<?php

namespace Stdimitrov\Orm\Tasks;

use PDO;
use Stdimitrov\Orm\Database;
use Stdimitrov\Orm\Tools\Helper;

class CreateModels extends Database
{


    private array $tables = [];

    public function run(string $namespace, string $dir): void
    {

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Count tables in the database
        $total = $this->countTables();

        echo "Found $total tables in the database.\n";
        echo "Creating models.\n";

        foreach ($this->tables as $table) {
            echo "Creating model for table: $table\n";
            $tableColumns = $this->getWriteConnection()->query("SHOW COLUMNS FROM `$table`")->fetchAll();

            $tableColumns = array_map(function ($column) {

                $type = trim(str_replace('unsigned', '', $column['Type']));
                $type = match ($type) {
                    'int', 'tinyint', 'tinyint(1)', 'mediumint' => 'int',
                    'decimal' => 'float',
                    default => 'string',
                };


                $isNullable = $column['Null'] === 'YES';

                if ($isNullable) {
                    $defaultValue =$column['Default'];

                    if ($defaultValue === 'NULL') {
                        $defaultValue = null;
                    } else {
                        $defaultValue =  match ($type) {
                            'int' => (int)$defaultValue,
                            'float' => (float)$defaultValue,
                            default => "''",
                        };
                    }
                }

                $prop = 'public ' . ($isNullable ? '?' . $type : $type) . ' $' . Helper::toCamelCase($column['Field']);
                $prop .= $isNullable ? ' = ' . $defaultValue . ';' : ';';

                return $prop;
            }, $tableColumns);

            $className =  Helper::toPascalCase($table);
            $properties = implode("\n    ", $tableColumns);

            $phpFile = <<<EOT
            <?php
            
            namespace {$namespace};
            
            use Stdimitrov\Orm\AbstractDatabase;
            
            class {$className} extends AbstractDatabase
            {
                {$properties}
                
                protected function getTableName(): string
                {
                    return '{$table}';
                }            
                
            }
            EOT;

            file_put_contents($dir . sprintf('/%s.php', $className), $phpFile);

            echo "Model for table $table created successfully.\n";
        }
    }


    private function countTables(): int
    {
        $sql = 'SHOW TABLES;';
        $this->tables = $this->getWriteConnection()->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        return count($this->tables);
    }


}