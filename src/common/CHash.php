<?php
/**
 * @Author shaowei
 * @Date   2015-07-20
 * A simple consistent hashing implementation.
 */

class CHash
{
    // the number of virtual nodes
    private $virtNodesNum = 64;

    // current target counter
    private $curTargetCount = 0;

    // map of positions (hash outputs) to targets
    // array { position => target, ... }
    private $posToTarget = array();

    // map of targets to lists of positions that target is hashed to.
    // array { target => [ position, position, ... ], ... }
    private $targetToPos = array();
    private $posToTargetSorted = false;

    public function __construct($targets = array())
    {
        if (!empty($targets)) {
            $this->addTargets($targets);
        }
    }

    public function addTarget($target)
    {
        if (isset($this->targetToPos[$target])) {
            return false;
        }
        $this->targetToPos[$target] = array();
        for ($i = 0; $i < $this->virtNodesNum; $i++) {
            $position = crc32($target . $i);
            $this->posToTarget[$position] = $target; // for lookup
            $this->targetToPos[$target][] = $position; // target remove
        }
        $this->posToTargetSorted = false;
        $this->curTargetCount++;
        return true;
    }

    public function addTargets($targets)
    {
        foreach ($targets as $target) {
            $this->addTarget($target);
        }
        return true;
    }

    public function delTarget($target)
    {
        if (!isset($this->targetToPos[$target])) {
            return false;
        }
        foreach ($this->targetToPos[$target] as $position) {
            unset($this->posToTarget[$position]);
        }
        unset($this->targetToPos[$target]);
        $this->curTargetCount--;
        return true;
    }

    public function get($resource)
    {
        if (empty($this->posToTarget)) {
            return false;
        }
        if ($this->curTargetCount == 1) {
            $ret = array_values($this->posToTarget);
            return empty($ret) ? false : $ret[0];
        }
        $resourcePos = crc32($resource);
        $this->sortPosTargets();
        // search values above the resourcePos
        foreach ($this->posToTarget as $key => $value) {
            if ($key > $resourcePos) {
                return $value;
            }
        }
        // get the first
        $ret = array_slice($this->posToTarget, 0, 1, false);
        return empty($ret) ? false : $ret[0];
    }

    //= private method
    private function sortPosTargets()
    {
        if (!$this->posToTargetSorted) {
            ksort($this->posToTarget, SORT_REGULAR);
            $this->posToTargetSorted = true;
        }
    }
}

