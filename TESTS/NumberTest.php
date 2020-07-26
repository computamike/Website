<?php
use PHPUnit\Framework\TestCase;

final class NUmberlTest extends TestCase
{
    public function testThatBarAndBarEqualsFour(){
        $this->assertEquals('bar', 'bar');
    }
}