<?php

namespace ProcessControl;

use Closure;
use Pcntl\Pcntl;
use Posix\Posix;
use ProcessControl\Exception\ForkFailureException;

/**
 * Class ProcessControlService
 *
 * @package ProcessControl
 */
class ProcessControlService
{
    /**
     * @var Posix
     */
    protected $posix;

    /**
     * @var Pcntl
     */
    protected $pcntl;

    /**
     * @var Process
     */
    protected $master;

    /**
     * Constructor
     *
     * @param Posix $posix
     * @param Pcntl $pcntl
     */
    public function __construct(Posix $posix, Pcntl $pcntl)
    {
        $this->posix = (is_null($posix)) ? new Posix() : $posix;
        $this->pcntl = (is_null($pcntl)) ? new Pcntl() : $pcntl;

        $this->master = new Process($this->posix->getpid());
    }

    /**
     * Get Master Process
     *
     * @return Process
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * Parallel
     *
     * @param callable $closure
     *
     * @return ProcessControlService
     *
     * @throws ForkFailureException
     */
    public function parallel(Closure $closure)
    {
        $processId = $this->pcntl->fork();

        if (-1 === $processId) {
            throw new ForkFailureException('Unable to fork process');
        }

        if ($processId) {
            $process = new Process($processId, $this->master);
            $this->master->addChild($process);
        }

        if (0 === $processId) {
            $process = new Process($this->posix->getpid(), $this->master);
            call_user_func($closure, $process);
            $this->posix->kill($process->getId(), 9);
        }

        return $this;
    }
}