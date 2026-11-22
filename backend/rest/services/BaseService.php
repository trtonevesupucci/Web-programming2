<?php
require_once __DIR__ . '/../utils/HttpException.php';

class BaseService
{
    protected $dao;
    protected $fields = [];
    protected $entityName = 'Resource';

    public function __construct($dao)
    {
        $this->dao = $dao;
    }

    public function getAll()
    {
        return $this->dao->getAll();
    }

    public function getById($id)
    {
        $this->validateId($id);
        $entity = $this->dao->getById($id);
        if (!$entity) {
            throw HttpException::notFound($this->entityName . ' not found');
        }
        return $entity;
    }

    public function add($data)
    {
        $data = $this->filterFields($data);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        return $this->dao->add($data);
    }

    public function update($id, $data)
    {
        $this->getById($id);
        $data = $this->filterFields($data);
        if (empty($data)) {
            throw HttpException::badRequest('Request body is empty or contains no valid fields');
        }
        return $this->dao->update($data, $id);
    }

    public function delete($id)
    {
        $this->getById($id);
        return $this->dao->delete($id);
    }

    protected function filterFields($data)
    {
        if (!is_array($data)) {
            throw HttpException::badRequest('Invalid request body');
        }
        return array_intersect_key($data, array_flip($this->fields));
    }

    protected function validateId($id, $label = 'ID')
    {
        if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw HttpException::badRequest('Invalid ' . $label);
        }
    }

    protected function requireFields($data, array $required)
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw HttpException::badRequest(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }
    }

    protected function validateLength($data, $field, $max, $min = 0)
    {
        if (!isset($data[$field]) || $data[$field] === null) {
            return;
        }
        $length = mb_strlen((string) $data[$field]);
        $label = ucfirst(str_replace('_', ' ', $field));
        if ($length < $min) {
            throw HttpException::badRequest($label . ' must be at least ' . $min . ' characters');
        }
        if ($length > $max) {
            throw HttpException::badRequest($label . ' must be at most ' . $max . ' characters');
        }
    }
}
