<?php

class Sender_Model
{
	protected $dirtyAttributes = [];

	protected function getTableName()
	{
		global $wpdb;

		return $wpdb->prefix.$this->tableName;
	}

	public function find($id)
	{
		return $this->findBy('id', $id);
	}

	public function findBy($attribute, $value)
	{
		global $wpdb;

		$sqlQuery = "SELECT * FROM `{$this->getTableName()}` WHERE $attribute = %s";

		$result = $wpdb->get_results( $wpdb->prepare( $sqlQuery, $value ) );

		if (!count($result)) {
			return false;
		}

		$this->parseResult($result[0]);

		return $this;
	}

	protected function setAttribute($attribute, $value)
	{
		if ($this->{$attribute} === $value) {
			return $this;
		}
		$this->dirtyAttributes[] = $attribute;
		$this->{$attribute} = $value;

		return $this;
	}

	public function save()
	{
		if(!$this->id) {
			return $this->createNew();
		}

		return $this->update();
	}

	public function createNew()
	{
		if (!count($this->dirtyAttributes)) {
			return $this;
		}

		global $wpdb;
		$sqlQuery = "INSERT INTO `{$this->getTableName()}` ( ";
		$changes = [];

		foreach ($this->dirtyAttributes as $key => $change) {
			$sqlQuery .= " $change, ";
			$changes[] = $this->{$change};
		}

		$sqlQuery .= " created, ";
		$changes[] = current_time('timestamp');

		$sqlQuery .= " updated ) ";
		$changes[] = current_time('timestamp');

		$wpdb->query( $wpdb->prepare($sqlQuery, ...$changes));
		$this->dirtyAttributes = [];
		return $this;
	}

	public function update()
	{
		if (!count($this->dirtyAttributes)) {
			return $this;
		}

		global $wpdb;
		$sqlQuery = "UPDATE `{$this->getTableName()}`SET ";
		$changes = [];

		foreach ($this->dirtyAttributes as $key => $change) {
			$sqlQuery .= " $change = %s, ";
			$changes[] = $this->{$change};
		}
		$sqlQuery
			.= " updated = %s ";
		$changes[] = current_time('timestamp');

		$sqlQuery .= " WHERE id = {$this->id}";

		$wpdb->query( $wpdb->prepare($sqlQuery, ...$changes));
		$this->dirtyAttributes = [];
		return $this;
	}

	protected function parseResult($result)
	{
		foreach ($result as $attribute => $value) {
			if (property_exists($this, $attribute)) {
				$this->{$attribute} = $value;
			}
		}
	}

	public function isDirty()
	{
		return (bool) count($this->dirtyAttributes);
	}

	public function __set($name, $value)
	{

		if (property_exists($this, $name)) {
			$this->setAttribute($name, $value);

			return $this;
		}
		$this->{$name} = $value;

		return $this;
	}
}