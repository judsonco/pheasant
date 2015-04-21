<?php

namespace Pheasant\Query;
use \Pheasant\Pheasant;

/**
 * An iterator that lazily executes a query, hydrating as it goes
 */
class RestrictedQueryIterator extends QueryIterator implements \SeekableIterator, \Countable
{

  // Constants to keep track of param indices
  const _LOCAL_INDEX = 0;
  const _FOREIGN_INDEX = 1;

  private
    $_iterator,
    $_restrictTo,
    $_restrictBy,
    $_offset,
    $_length
    ;

    /**
     * Constructor
     * @param object An instance of Query
     * @param closure A closure that takes a row and returns an object
     */
    public function __construct($query, $hydrator=null)
    {
        $this->_iterator = new QueryIterator($query, $hydrator);
    }

    private function _restricted(){
        return !!$this->_restrictTo;
    }

    /**
     * Set which properties will be used for restricting
     * this iterator.
     *
     * @param String|Array properties An array of arrays in the form
     * of [local, foreign], or a string to be used as both local and foreign.
     */
    public function restrictBy($properties)
    {
        $restrictions = is_array($properties)
            ? $properties
            : array(array($properties, $properties));
        $this->_restrictBy = $restrictions;
    }

    /**
     * Set which values the restricted properties will be restricted to
     *
     * @param String|Array values An array of values or a single value
     * to use for restriction. Must be the same length as number of restricted
     * properties.
     */
    public function restrictTo($values)
    {
        $restrictions = is_array($values)
            ? $values
            : array($values);

        $this->_restrictTo = $restrictions;
    }

    /**
     * Get the offset for the restricted set
     * @return int
     */
    private function _restrictedBeginning(){
      // For a given RestrictedIterator, the offset will only
      // be set once per Iterator instance.
      if ($this->_restricted() && !isset($this->_offset)) {
          $oldHydrator = $this->_iterator->hydrator();
          // Rewind the iterator to make sure we're at 0
          $this->_iterator->rewind();
          for ($i=0; $this->_iterator->valid(); $i++,$this->_iterator->next()) {
              // If the offset is already set, then we're only
              // looking for length, so skip this section
              if (!(!isset($this->_offset) || !isset($this->_length))) break;

              $currentIsExpected = true;
              // Because an iterator may be restricted by multiple
              // properties, we must iterate over all the restricted
              // properties and make sure they all match
              for ($j=0,$cj=count($this->_restrictBy); $j<$cj; $j++) {
                  // The current restriction
                  $current = $this->_restrictBy[$j];
                  // The foreign field name for this restriction
                  $foreignField = $current[static::_FOREIGN_INDEX];
                  // Because it is possible that the iterator may be
                  // restricted to properties which are not on the objects
                  // aka, properties that are used for joins, we need to reset
                  // the hydrator to simply return the row's data to us
                  $this->_iterator->setHydrator(function($row){return $row;});
                  // The current value of the foreign property
                  $row = $this->_iterator->current();
                  $value = $row[$foreignField];
                  // The expected value for the restriction
                  $expected = $this->_restrictTo[$j];
                  // If the current and expected aren't equal,
                  // this is not the beginning of the offset
                  if ($value != $expected) {
                      $currentIsExpected = false;
                      break;
                  }
              }

              // If all of the current property values matched the expected
              // property values, then the offset should get set.
              if (!isset($this->_offset) && $currentIsExpected) $this->_offset = $i;

              // If the offset is not set, no need to worry about
              // the length.
              if (!isset($this->_offset)) continue;

              // If the offset is set (e.g. we are/were in the restricted
              // range) and the current doesn't equal the expected, we have
              // hit the end of the restricted range and should set the length.
              if(!$currentIsExpected) $this->_length = $i - $this->_offset;
          }

          // If the restricted offset wasn't ever found, then, for
          // this restriction, the data doesn't exist, and the offset
          // and length should be zero.
          if (!isset($this->_offset)) {
            $this->_offset = 0;
            $this->_length = 0;
          }

          // If the length isn't set, then the restricted range goes to the
          // end of the iterator, and we should set the length now
          if (!isset($this->_length)) $this->_length = $i - $this->_offset;

          $this->_iterator->setHydrator($oldHydrator);
      }

      // If this is iterator is currently restricted we should return
      // the offset, which marks the beginning of the restricted set.
      // Otherwise, return the absolute beginning of the set.
      return $this->_restricted() ? $this->_offset : 0;
    }

    /**
    * Rewinds the internal pointer
    */
    public function rewind()
    {
        return $this->_iterator->seek($this->_restrictedBeginning());
    }

    /**
    * Moves the internal pointer one step forward
    */
    public function next()
    {
        return $this->_iterator->next();
    }

    /**
    * Returns true if the current position is valid, false otherwise.
    * @return bool
    */
    public function valid()
    {
        if (!$this->_restricted()) return $this->_iterator->valid();

        // If this iterator is restricted, we must do some
        // extra checks to see if this position is valid.
        $offset = $this->_iterator->key();
        return ($this->_restrictedBeginning() <= $offset) &&
               ($offset < $this->_offset+$this->_length);
    }

    /**
    * Returns the row that matches the current position
    * @return array
    */
    public function current()
    {
        return $this->_iterator->current();
    }

    /**
    * Returns the current position
    * @return int
    */
    public function key()
    {
        return $this->_iterator->key() - (!$this->_restricted() ? 0 : $this->_restrictedBeginning());
    }

    /**
     * Seeks to a particular position in the result. Offset is from 0.
     */
    public function seek($position)
    {
        return $this->_iterator->seek($position+$this->_restrictedBeginning());
    }

    /**
     * Counts the number or results in the query
     */
    public function count()
    {
        return !$this->_restricted()
            ? $this->_iterator->count()
            : $this->_length;
    }
}
