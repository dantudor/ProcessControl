<?php

use ProcessControl\ProcessControlService;

/**
 * Class ProcessControlServiceTest
 */
class ProcessControlServiceTest extends PHPUnit_Framework_TestCase
{
    protected $mockPosix;

    protected $mockPcntl;

    public function setUp()
    {
        $this->mockPosix = Mockery::mock('\Posix\Posix');
        $this->mockPcntl = Mockery::mock('\Pcntl\Pcntl');
    }

    public function testMasterCreatedinConstructor()
    {
        $masterPid = 1234;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);

        $this->assertInstanceOf('\ProcessControl\Process', $processService->getMaster());
    }

    /**
     * @expectedException \ProcessControl\Exception\ForkFailureException
     */
    public function testExceptionWhenParallelFailsToFork()
    {
        $masterPid = 1234;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid);
        $this->mockPcntl->shouldReceive('fork')->andReturn(-1);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $processService->parallel(function(){ });
    }

    public function testParallelReturnsInstanceWhenRespondingToParent()
    {
        $masterPid = 1234;
        $childPid = 5678;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid);
        $this->mockPcntl->shouldReceive('fork')->andReturn($childPid);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $this->assertSame($processService, $processService->parallel(function(){ }));
    }

    public function testParallelAddsChildToMasterWhenRespondingToParent()
    {
        $masterPid = 1234;
        $childPid = 5678;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid);
        $this->mockPcntl->shouldReceive('fork')->andReturn($childPid);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $processService->parallel(function(){ });

        $this->assertTrue($processService->getMaster()->hasChildById($childPid));
    }

    /**
     * @expectedException \RanClosureException
     */
    public function testParallelCallsClosureWhenRespondingToChild()
    {
        $masterPid = 1234;
        $childPid = 0;
        $closure = function() {
            throw new RanClosureException('dan');
        };

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid);
        $this->mockPcntl->shouldReceive('fork')->andReturn($childPid);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $processService->parallel($closure);
    }

    public function testParallelKillsChildProcessOnCompletion()
    {
        $masterPid = 1234;
        $childPid = 5678;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid, $childPid);
        $this->mockPosix->shouldReceive('kill')->with($childPid, 9);
        $this->mockPcntl->shouldReceive('fork')->andReturn(0);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $processService->parallel(function(){ });
    }

    public function testTerminateProcess()
    {
        $masterPid = 1234;
        $childPid = 5678;

        $this->mockPosix->shouldReceive('getpid')->andReturn($masterPid, $childPid);
        $this->mockPosix->shouldReceive('kill')->with($childPid, 9);
        $this->mockPcntl->shouldReceive('fork')->andReturn($childPid);

        $processService = new ProcessControlService($this->mockPosix, $this->mockPcntl);
        $processService->parallel(function(){sleep(10);});

        $childProcess = $processService->getMaster()->getChildById($childPid);

        $this->assertSame($processService, $processService->terminateProcess($childProcess));
    }
}

class RanClosureException extends \Exception
{

}