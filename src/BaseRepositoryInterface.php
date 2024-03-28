<?php

namespace TatTran\Repository;

interface BaseRepositoryInterface
{
    /**
     * Get records by query parameters with pagination.
     *
     * @param array $params
     * @param int $size
     * @return mixed
     */
    public function getByQuery(array $params = [], int $size = 25);

    /**
     * Get a record by its ID.
     *
     * @param mixed $id
     * @return mixed
     */
    public function getById($id);

    /**
     * Get a soft-deleted record by its ID.
     *
     * @param mixed $id
     * @return mixed
     */
    public function getByIdInTrash($id);

    /**
     * Store a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function store(array $data);

    /**
     * Store multiple records.
     *
     * @param array $data
     */
    public function storeArray(array $data);

    /**
     * Update a record by its ID.
     *
     * @param mixed $id
     * @param array $data
     * @param array $excepts
     * @param array $only
     * @return mixed
     */
    public function update($id, array $data, array $excepts = [], array $only = []);

    /**
     * Delete a record by its ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id);

    /**
     * Permanently delete a record by its ID.
     *
     * @param mixed $id
     * @return mixed
     */
    public function destroy($id);

    /**
     * Restore a soft-deleted record by its ID.
     *
     * @param mixed $id
     * @return mixed
     */
    public function restore($id);
}

