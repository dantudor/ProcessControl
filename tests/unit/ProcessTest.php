<?php

use ProcessControl\Process;
/**
 * Class ProcessTest
 */
class ProcessTest extends PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $id = 1234;

        $process = new Process($id);

        $this->assertSame($id, $process->getId());
    }

    public function testIsMasterTrue()
    {
        $process = new Process(1234);

        $this->assertTrue($process->isMaster());
    }

    public function testIsMasterFalse()
    {
        $process = new Process(1234, new Process(5678));

        $this->assertFalse($process->isMaster());
    }

    public function testGetParentOfMasterReturnsNull()
    {
        $process = new Process(1234);

        $this->assertNull($process->getParent());
    }

    public function testGetParentOfMasterReturnsMaster()
    {
        $master = new Process(5678);
        $process = new Process(1234, $master);

        $this->assertSame($master, $process->getParent());
    }
}
