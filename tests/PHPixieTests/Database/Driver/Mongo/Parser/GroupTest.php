<?php

namespace PHPixieTests\Database\Driver\Mongo\Parser;

/**
 * @coversDefaultClass \PHPixie\Database\Driver\Mongo\Parser\Group
 */
class GroupTest extends \PHPixieTests\AbstractDatabaseTest
{
    protected $groupParser;

    protected function setUp()
    {
        $this->database = new \PHPixie\Database(null);
        $operatorParser = new \PHPixie\Database\Driver\Mongo\Parser\Operator();
        $this->groupParser = new \PHPixie\Database\Driver\Mongo\Parser\Group($this->database->driver('Mongo'), $operatorParser);
    }

    /**
     * @covers ::parse
     * @covers ::<protected>
     * @covers ::__construct
     */
    public function testParseSimple()
    {
        $builder = $this->getBuilder()
                                    ->_and('name', 1)
                                    ->_and(function ($builder) {
                                        $builder->_and('id', 2)->_or('id', 3);
                                    });
        $this->assertGroup($builder, array(
            '$or'=>array(
                array('name'=>1, 'id'=>2),
                array('name' => 1, 'id' => 3)
            )
        ));

        $builder = $this->getBuilder()
                                    ->_and('a', 1)
                                    ->_and('a', '>', 6)
                                    ->_and('a', '<', 10);

        $this->assertGroup($builder, array(
            '$and'=>array(
                array('a' => 1),
                array('a' => array('$gt' => 6)),
                array('a' => array('$lt'=>10))
            )
        ));

        $builder = $this->getBuilder()
                                    ->_and('a', 1)
                                    ->_and(function ($builder) {
                                        $builder
                                            ->_and('b', '>', 2)
                                            ->_xor('c', '>' , 3);
                                    });

        $this->assertGroup($builder, array(
            '$or' => array(
                        array(
                            'a' => 1,
                            'b' => array('$gt' => 2),
                            'c' => array('$lte' => 3),
                        ),
                        array(
                            'a' => 1,
                            'b' => array('$lte' => 2),
                            'c' => array('$gt' => 3),
                        )
                    )
        ));

        $builder = $this->getBuilder()
                                    ->_and('b', 1)
                                    ->_and('b', '>', 2)
                                    ->_and('a', 2)
                                    ->_and('a', '<', 3)
                                    ->andNot('a', '>', 4);

        $this->assertGroup($builder, array(
            '$and' => array(
                        array(
                            'b' => 1,
                            'a' => 2
                        ),
                        array(
                            'b' => array('$gt' => 2),
                            'a' => array('$lt' => 3),
                        ),
                        array(
                            'a' => array('$lte' => 4),
                        )
                    )
        ));

        $builder = $this->getBuilder()
                                    ->_and('b', 1)
                                    ->xorNot('a', '>', 4);

        $this->assertGroup($builder, array(
            '$or' => array(
                        array(
                            'b' => 1,
                            'a' => array('$gt' => 4)
                        ),
                        array(
                            'b' => array('$ne' => 1),
                            'a' => array('$lte' => 4),
                        )
                    )
        ));

        $builder = $this->getBuilder()->_and(function ($builder) {
            $builder->_and(function () {});
        });
        $this->assertGroup($builder, array());

        $builder = $this->getBuilder()->_and(function ($builder) {
            $builder
                    ->_and('a', 1)
                    ->_and(function ($builder) {
                        $builder->_and(function () {});
                    });
        });
        $this->assertGroup($builder, array('a' => 1));

        $subdocument = $this->database->subdocumentCondition();
        $subdocument
                    ->and('a', 1)
                    ->or('a', '>', 1);
        $builder = $this->getBuilder()->_and('p', 'elemMatch', $subdocument);
        $this->assertGroup($builder, array(
            'p' => array(
                '$elemMatch' => array(
                    '$or' => array(
                        array( 'a' => 1 ),
                        array(
                            'a' => array(
                                '$gt' => 1
                            )
                        )
                    )
                )
            )
        ));
    }

    /**
     * @covers ::parse
     * @covers ::<protected>
     */
    public function testParseNegate()
    {
        $builder = $this->getBuilder()
                                    ->_and('a', 1)
                                    ->andNot('c',2);
        $this->assertGroup($builder, array(
            'a' => 1,
            'c' => array('$ne'=>2)
        ));
    }

    /**
     * @covers ::parse
     * @covers ::<protected>
     */
    public function testParsePrecedance()
    {
        $builder = $this->getBuilder()
                                    ->_and('a', 1)
                                    ->_and('b', 1)
                                    ->_or('c', 1)
                                    ->_and('d', 1);

        $this->assertGroup($builder, array(
            '$or' => array(
                        array(
                            'a' => 1,
                            'b' => 1
                        ),
                        array(
                            'c' => 1,
                            'd' => 1,
                        )
                    )
        ));

        $builder = $this->getBuilder()
                                    ->_and('d', 1)
                                    ->_or('a', 1)
                                    ->_and('b', 1)
                                    ->_xor('c', 1)
                                    ->_or('e',1);

        $this->assertGroup($builder, array(
            '$or' => array(
                        array('d' => 1),
                        array(
                            'a' => 1,
                            'b' => 1,
                            'c' => array('$ne' => 1)
                        ),
                        array(
                            'a' => array('$ne' => 1),
                            'c' => 1
                        ),
                        array(
                            'b' => array('$ne' => 1),
                            'c' => 1
                        ),
                        array('e' => 1),
                    )
        ));

        $builder = $this->getBuilder()
                                    ->_and('a', 1);
        $placeholder = $builder->addPlaceholder('and')->builder();
        $placeholder
                ->_and('b',1)
                ->_or('c', 1);

        $this->assertGroup($builder, array(
            '$or' => array(
                        array('a' => 1, 'b' => 1),
                        array('a' => 1, 'c' => 1)
                    )
        ));
    }

    protected function getBuilder()
    {
        $builder = new \PHPixie\Database\Conditions\Builder($this->database->conditions());

        return $builder;
    }

    protected function assertGroup($builder, $expect)
    {
        $parsed = $this->groupParser->parse($builder->getConditions());
        $this->assertEquals($expect, $parsed);
    }

}
