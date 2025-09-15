<?php


namespace Controllers\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CrudService
{
    protected Model $model;

    /**
     * Establece el modelo que el servicio va a gestionar.
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Obtiene todos los registros del modelo.
     */
    public function getAll(): Collection
    {
        return $this->model->latest()->get();
    }

    /**
     * Crea un nuevo registro.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Actualiza un registro existente.
     */
    public function update(Model $entity, array $data): Model
    {
        $entity->update($data);
        return $entity;
    }

    /**
     * Elimina un registro.
     */
    public function delete(Model $entity): void
    {
        $entity->delete();
    }
}
