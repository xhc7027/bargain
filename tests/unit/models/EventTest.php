<?php

namespace unit\models;

use app\models\Event;
use Codeception\Test\Unit;

class EventTest extends Unit
{
    public function testFind()
    {
        $count = Event::find()->count();
        $this->assertTrue($count > 0, '查询出来的行数不正确');
    }
}