<?php

namespace Coggr\Application;

abstract class Repository
{

	/**
	 * The current entity pointer
	 *
	 * @var object
	 */
	protected $entity;

	/**
	 * Entities container
	 *
	 * @var array
	 */
	protected $entities;

	/**
	 * Repositories container
	 *
	 * @var array
	 */
	protected $repositories;

	/**
	 * Loads entities from an array
	 *
	 * @param array $resources
	 * @return true
	 */
	public function loadResources(array $resources) : bool
	{
		array_walk($resources, [$this, 'register']);

		return true;
	}

	/**
	 * Registers an entity/repository to their appropriate containers
	 *
	 * @param string|object $instance
	 * @throws \Coggr\Exceptions\ResourceNotAnObject
	 * @return true
	 */
	public function register($instance) : bool
	{
		if ( ! is_object($instance) ) {
			$instance = new $instance;
		}

		if ( $instance instanceof \Illuminate\Eloquent\Model ) {
			$this->entities[$instance->getTable()] = $instance;
		}

		if ( $instance instanceof Repository) {
			$repositoryName = strtolower(str_replace('Repository', '', $instance));
			$this->repositories[$repositoryName] = $instance;
		}

		$this->entity = $this->setEntity($this->entity());

		return true;
	}

	/**
	 * Sets the current entity
	 *
	 * @param Object $entity
	 * @return void
	 */
	protected function setEntity($entity)
	{
		$this->entity = $entity;
	}

	/**
	 * Returns the {first configured|inputted} entity
	 *
	 * @param string $entity
	 * @throws \Coggr\Exceptions\EntityNotDefined
	 * @return $entity
	 */
	public function entity($entity = null)
	{
		if ( ! count($this->entities) > 0 && $entity === null ) {
			$entityName = strtolower(str_replace('Repository', '', get_class()));

			return array_key_exists($entityName, $this->entities) ?
				$this->entities[$entityName]:
				$this->entities[0];
		}

		if ( array_key_exists($entity, $this->entities) ) {
			return $this->entity = $this->entities[$entity];
		}

		throw new \Coggr\Exceptions\EntityNotDefined($e);
	}

	/**
	 * Finds an entity by its ID
	 *
	 * @param string $id
	 * @return Object $entity
	 */
	public function find($id)
	{
		return $this->entity->find($id);
	}

	/**
	 * Finds the first entity by the given parameters
	 *
	 * @param integer|array|string $param
	 * @return Object $entity
	 */
	public function first($params, $value = null)
	{
		if ( is_numeric($params) ) {
			$entity = $this->entity->find($id);
		}

		if ( is_array($params) ) {
			$entity = $this->entity->where($params)->first();
		}

		if ( is_string($params) ) {
			$entity = $this->entity->where($params, $value)->first();
		}

		return $entity;
	}

	/**
	 * Gets an entity by parameters
	 *
	 * @param array $params
	 * @return array
	 */
	public function get(array $params)
	{
		return $this->entity->where($params)->get();
	}

	/**
	 * Creates a new model
	 *
	 * @param array $data
	 * @return Object $entity
	 */
	public function create(array $data)
	{
		$entity = $this->map($data, (new $this->entity))->save();

		return $this->entity;
	}

	/**
	 * Updates the entity
	 *
	 * @param array $data
	 * @param integer|array $identifier
	 * @return Object $entity
	 */
	public function update(array $data, $identifier)
	{
		$entity = $this->entity->first($identifier);
		$entity = $this->map($data)->save();

		return $this->entity;
	}

	/**
	 * Deletes the entity
	 *
	 * @param integer|array $identifier
	 * @return boolean
	 */
	public function delete($identifier) : bool
	{
		$entity = $this->entity->first($identifier);

		if ( is_null($entity) ) {
			return false;
		}

		$entity->delete();

		return true;
	}

	/**
	 * Mass assignment based on fillable, hidden, and guarded attributes
	 *
	 * @param \Coggr\Application\Http\Request $request
	 * @param array $inserts
	 * @throws \Coggr\Exceptions\EntityNotDefined
	 * @return object
	 */
	protected function map(\Coggr\Application\Http\Request $request, array $inserts, $entity = null)
	{
		$entity = ! is_null($entity) ? $entity : $this->entity;

		if ( ! is_object($this->entity) ) {
			throw new \Coggr\Exceptions\EntityNotDefined;
		}

		$fields = $request->insertKeys();

		foreach($inserts as $key => $val)
		{
			if ( in_array($key, $fields) ) {
				$entity->{$key} = $val;
			}
		}

		return $entity;
	}

	/**
	 * Mimics the original behavior of the DI
	 *
	 * @return Object
	 */
	public function __get($key)
	{
		try {
			if (array_key_exists($key, $this->entities)) return $this->entities[$key];
			if (array_key_exists($key, $this->repositories)) return $this->repositories[$key];

			throw new \App\Exceptions\ResourceNotFound;
		} catch (\App\Exceptions\ResourceNotFound $e) {

		}
	}

}