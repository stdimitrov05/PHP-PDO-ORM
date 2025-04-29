# Custom Basic ORM for PHP (with PDO)

A lightweight and flexible PHP library that simplifies database interactions by providing a basic custom ORM (Object-Relational Mapping) layer on top of PDO.
Designed for developers who want simple, clean, and efficient access to their databases without heavy frameworks.

---
## Requirements

- PHP  8.4 or higher
- PDO extension enabled

---
## Features

- Support InnoDB and MySQL Cluster
- Simple and intuitive CRUD operations
- Read/Write database connection support
- Automatic mapping between `snake_case` (database) and `camelCase` (PHP)
- Secure queries using prepared statements
- Lightweight and dependency-free (except PDO)
- PSR-12 compliant coding standards

---

## Installation
1. [Download a latest package](https://github.com/stdimitrov05/PHP-PDO-ORM/releases) or use [Composer](http://getcomposer.org/):
```bash
composer require stdimitrov/orm
```

2. Create environment file `.env` in the root directory of your project:
```dotenv
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
DB_PORT_RO=3306 # this is port for read connection
DB_PORT_RW=3306 # this is port for write connection
MODEL_NAMESPACE=App\Models
```

## Basic Usage


### 1. Using the ORM on a Model
-- note: Class name should be same as table

```php
namespace App\Models;
use Stdimitrov\Orm\Database;

class Users extends Database
{
    public int $id; // Primary Key
    public string $name;
    public int $age;
    //...
    
    // Define the specific table name
    public function getTableName()
    {
        return 'users'; // Specify the table name
    }
}
```


### 2. Using the ORM on a Repository
-- note: Class name should be same as table + Repository

```php
namespace App\Repositories;
use Stdimitrov\Orm\Database;
use App\Models\Users;

class UsersRepository extends Database
{
    public function findById(int $id): ?Users 
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [$id];
    
        return $this->fetchOne($sql, $params);
    }

    /**
    * @return Users[] | null
    */
    public function getAllUsers(): ?array
    {
        $sql = 'SELECT * FROM users LIMIT 10';
    
        return $this->fetchAll($sql);
    }
    
    // IF use readonly connection but need to write
    public function findById(int $id): ?Users 
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [$id];
    
        return $this->forceRW()->fetchOne($sql, $params);
    }
    
    
    // IF you need debug current query before execute
    public function findById(int $id): ?Users 
    {
        $sql = 'SELECT * FROM users WHERE id = ?';
        $params = [$id];
    
        return $this->debug()->fetchOne($sql, $params);
    }
}

```
---

## Contributing

Contributions are welcome!  
Please fork the repository, create a feature branch, and submit a pull request.  
Follow PSR-12 coding standards and include relevant tests.

---

## License

This project is licensed under the [MIT License](https://github.com/stdimitrov05/PHP-PDO-ORM/blob/main/LICENSE).

---

## Support

For issues, please open a GitHub Issue or contact [stdimitrov05@gmail.com].


