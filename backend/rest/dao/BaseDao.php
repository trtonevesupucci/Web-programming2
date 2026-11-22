<?php
require_once __DIR__ . '/../config.php';

class BaseDao
{
    protected $connection;
    protected $table_name;

    public function __construct($table_name)
    {
        $this->table_name = $table_name;
        $this->connection = new PDO(
            'mysql:host=' . Config::DB_HOST() . ';dbname=' . Config::DB_NAME() . ';port=' . Config::DB_PORT() . ';charset=utf8mb4',
            Config::DB_USER(),
            Config::DB_PASSWORD(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }

    protected function query($query, $params = [])
    {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function query_unique($query, $params = [])
    {
        $results = $this->query($query, $params);
        return reset($results);
    }

    public function getAll()
    {
        return $this->query('SELECT * FROM ' . $this->table_name . ' ORDER BY id');
    }

    public function getById($id)
    {
        return $this->query_unique('SELECT * FROM ' . $this->table_name . ' WHERE id = :id', ['id' => $id]);
    }

    public function add($entity)
    {
        $columns = array_keys($entity);
        $query = 'INSERT INTO ' . $this->table_name . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')';
        $stmt = $this->connection->prepare($query);
        $stmt->execute($entity);
        return $this->getById($this->connection->lastInsertId());
    }

    public function update($entity, $id, $id_column = 'id')
    {
        if (empty($entity)) {
            return $this->getById($id);
        }
        $set = [];
        foreach (array_keys($entity) as $column) {
            $set[] = $column . ' = :' . $column;
        }
        $query = 'UPDATE ' . $this->table_name . ' SET ' . implode(', ', $set) . ' WHERE ' . $id_column . ' = :id_value';
        $stmt = $this->connection->prepare($query);
        $entity['id_value'] = $id;
        $stmt->execute($entity);
        return $this->getById($id);
    }

    public function delete($id)
    {
        $stmt = $this->connection->prepare('DELETE FROM ' . $this->table_name . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
