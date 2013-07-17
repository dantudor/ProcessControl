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
    public function __construct(Posix $posix = null, Pcntl $pcntl = null)
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
        $processId = $this->fork();

        if ($processId) {
            $process = new Process($processId, $this->master);
            $this->master->addChild($process);

            return $process;
        }

        $process = new Process($this->posix->getpid(), $this->master);
        if (false === is_null($closure)) {
            call_user_func($closure, $process);
        }
        $this->posix->kill($process->getId(), SIGKILL);

        return $this;
    }

    /**
     * Daemonize
     *
     * @param callable $closure
     *
     * @return $this
     */
    public function daemonize(Closure $closure = null)
    {
        $processId = $this->fork();

        if ($processId) {
            if (false === is_null($closure)) {
                $process = new Process($processId, $this->master);
                call_user_func($closure, $process);
            }
            $this->posix->kill($this->master->getId(), SIGKILL);
            return;
        }

        $this->posix->setsid(); // Child leads the session

        return new Process($this->posix->getpid());
    }

    /**
     * Fork the Process
     *
     * @return int
     * @throws ForkFailureException
     */
    protected function fork()
    {
        $processId = $this->pcntl->fork();

        if (-1 === $processId) {
            throw new ForkFailureException('Unable to fork process');
        }

        return $processId;
    }

    /**
     * Terminate Process
     *
     * @param Process $process Child Process
     * @param int     $signal  Signal
     *
     * @return ProcessControlService
     *
     * @throws TerminationFailureException
     */
    public function terminateProcess(Process $process, $signal = SIGKILL)
    {
        $this->posix->kill($process->getId(), $signal);
        $this->getMaster()->removeChild($process);

        return $this;
    }
}