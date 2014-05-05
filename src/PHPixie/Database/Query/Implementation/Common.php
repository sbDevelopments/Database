<?php

namespace PHPixie\Database\Query;

class Common
{
	protected $conditions;
    protected $fields;
    protected $limit;
    protected $offset;
    protected $orderBy = array();
    protected $conditionBuilders = array();
    protected $defaultBuilder;

	
	public function __construct($conditions)
	{
		$this->conditions = $conditions;
	}
	
    public function fields($fields)
    {
        $this->assert($fields !== null && !is_array($fields),"Field list must either be an array or NULL");
        $this->fields = $fields;
        return $this;
    }
    
    public function getFields()
    {
        return $this->fields;
    }
    
    public function limit($limit)
    {
        $this->assert(is_numeric($$limit), "Limit must be a number");
        $this->limit = $limit;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function offset($offset)
    {
        $this->assert(is_numeric($offset), "Offset must be a number");
        $this->offset = $offset;
        return $this;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function orderAscendingBy($field)
    {
        $this->orderBy[] = array($field, 'asc');
        return $this;
    }
    
    public function orderDescendingBy($field)
    {
        $this->orderBy[] = array($field, 'desc');
        return $this;
    }
    
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    public function conditionBuilder($name = null)
    {
        if ($name === null) {
            $this->assert($this->defaultBuilder !== null, "None of the condition builders were used");
            return $this->defaultBuilder;
        }

        if (!isset($this->conditionBuilders[$name]))
            $this->conditionBuilders[$name] = $this->conditions->builder();

        $this->defaultBuilder = $this->conditionBuilders[$name];

        return $this->defaultBuilder;
    }

    public function getConditions($name)
    {
        if (!isset($this->conditionBuilders[$name]))
            return array();

        return $this->conditionBuilders[$name]->getConditions();
    }

    public function addCondition($args, $logic = 'and', $negate = false, $builderName = null)
    {
        $this->conditionBuilder($builderName)->addCondition($logic, $negate, $args);
    }

    public function startConditionGroup($logic = 'and', $negate = false, $builderName = null)
    {
        $this->conditionBuilder($builderName)->startConditionGroup($logic, $negate);
    }

    public function endConditionGroup($builderName = null)
    {
        $this->conditionBuilder($builderName)->endGroup();
    }
    
    public function defaultBuilder()
    {
		return $this->defultBuilder;
	}
	
	public function setDefaultBuilder($builder)
	{
		$this->defaultBuilder = $builder;
	}
    
    public function assert($condition, $exceptionMessage)
    {
        if(!$condition)
            throw new \PHPixie\Database\Exception\Builder($exceptionMessage);
    }

}
