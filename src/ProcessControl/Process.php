<?php

namespace ProcessControl;

use PhpCollection\Map;
use ProcessControl\Exception\MissingChildException;

/**
 * Class Process
 *
 * @package ProcessControl
 */
class Process
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Process
     */
    protected $parent;

    /**
     * @var Map
     */
    protected $children;

    /**
     * @param int     $processId Process ID
     * @param Process $parent    Parent Process
     */
    public function __construct($processId, Process $parent = null)
    {
        $this->id = $processId;
        $this->parent = $parent;
        $this->children = new Map();

        return $this;
    }

    /**
     * Get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Is master?
     *
     * @return bool
     */
    public function isMaster()
    {
        return $this->parent ? false : true;
    }

    /**
     * Get Parent
     *
     * @return Process
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add Child Process
     *
     * @param Process $process Child Process
     *
     * @return Process
     */
    public function addChild(Process $process)
    {
        $this->children->set($process->getId(), $process);

        return $this;
    }

    /**
     * Get Child By ID
     *
     * @param int $processId
     *
     * @return Process
     *
     * @throws MissingChildException
     */
    public function getChildById($processId)
    {
        if (false === $this->hasChildById($processId)) {
            throw new MissingChildException('The child process does not exist');
        }

        return $this->children->get($processId)->get();
    }

    /**
     * Has Child By Process Id
     *
     * @param int $processId Process ID
     *
     * @return bool
     */
    public function hasChildById($processId)
    {
        return $this->children->containsKey($processId);
    }

    /**
     * Remove Child Process
     *
     * @param Process $process
     *
     * @return Process
     *
     * @throws MissingChildException
     */
    public function removeChild(Process $process)
    {
        if (false === $this->hasChildById($process->getId())) {
            throw new MissingChildException('The child process does not exist');
        }

        $this->children->remove($process->getId());

        return $this;
    }
}