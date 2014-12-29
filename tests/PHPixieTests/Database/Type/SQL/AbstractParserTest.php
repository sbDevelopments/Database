<?php
namespace PHPixieTests\Database\Type\SQL;

abstract class AbstractParserTest extends \PHPixieTests\AbstractDatabaseTest
{
    protected function assertExpression($expr, $expected)
    {
        $this->assertEquals($expected[0], $expr->sql);
        $this->assertEquals($expected[1], $expr->params);
    }

    protected function queryStub($sql, $params = array())
    {
        $query = $this->quickMock('\PHPixie\Database\Driver\PDO\Query\Type\Select', array('parse'));
        $query
            ->expects($this->any())
            ->method('parse')
            ->will($this->returnValue($this->database->sqlExpression($sql, $params)));

        return $query;
    }

    protected function operator($field, $operator, $values)
    {
        return new \PHPixie\Database\Conditions\Condition\Operator($field, $operator, $values);
    }

}
