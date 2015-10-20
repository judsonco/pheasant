<?php

namespace Pheasant\Database\Mysqli;

/**
 * A Transaction Stack that keeps track of open savepoints
 */
class SavePointStack
{
    private
        $_savePointStack = array()
        ;

    /**
     * Get the top element without removing
     * @return string
     */
    public function peek()
    {
      $current = array_slice($this->_savePointStack, -1);

      return !$current ? null : $current[0];
    }

    /**
     * Get the size of the stack
     * @return integer
     */
    public function size()
    {
        return count($this->_savePointStack);
    }

    /**
     * Decend deeper into the transaction stack and return a unique
     * transaction savepoint name
     * @return string
     */
    public function descend()
    {
        $this->_savePointStack[] = current($this->_savePointStack) === false
            ? null
            : 'savepoint_'.$this->size();

        return end($this->_savePointStack);
    }

    /**
     * Pop off the last savepoint
     * @return string
     */
    public function pop()
    {
      return array_pop($this->_savePointStack);
    }
}
