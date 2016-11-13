<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib\SQL
 * @since          2016-11-02
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib\SQL;


use Traversable;


/**
 * Defines a whole WHERE SQL clause with all conditions.
 */
class Where implements \Iterator, \IteratorAggregate , \ArrayAccess, \Countable
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * @type \Messier\DBLib\SQL\WhereCondition[] Array
    */
   private $_conditions;

   /**
    * @type int
    */
   private $_index;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Where constructor.
    */
   public function __construct()
   {

      $this->_conditions = [];
      $this->_index = 0;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


   // <editor-fold desc="// ~ ~   I T E R A T O R   I N T E R F A C E   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Return the current condition.
    *
    * @return \Messier\DBLib\SQL\WhereCondition|null
    */
   public function current()
   {

      if ( ! $this->valid() ) { return null; }

      return $this->_conditions[ $this->_index ];

   }

   /**
    * Move forward to next condition.
    */
   public function next()
   {

      $this->_index++;

   }

   /**
    * Return the key of the current element
    *
    * @link  http://php.net/manual/en/iterator.key.php
    * @return mixed scalar on success, or null on failure.
    * @since 5.0.0
    */
   public function key()
   {

      return $this->_index;

   }

   /**
    * Checks if current position is valid.
    *
    * @return bool
    */
   public function valid()
   {

      return $this->_index < $this->count();

   }

   /**
    * Rewind the Iterator to the first element.
    */
   public function rewind()
   {

      $this->_index = 0;

   }

   // </editor-fold>


   // <editor-fold desc="// ~ ~   A R R A Y A C C E S S   I N T E R F A C E   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Whether a offset exists
    *
    * @param  int $offset An offset to check for.
    * @return bool
    */
   public function offsetExists( $offset )
   {

      return $offset > -1 && $offset < $this->count();

   }

   /**
    * Gets the condition for defined offset.
    *
    * @param  int $offset The offset to retrieve.
    * @return \Messier\DBLib\SQL\WhereCondition|null
    */
   public function offsetGet( $offset )
   {

      if ( ! $this->offsetExists( $offset ) ) { return null; }

      return $this->_conditions[ $offset ];

   }

   /**
    * Sets the condition for defined offset.
    *
    * @param int|null $offset The offset to assign the value to.
    * @param \Messier\DBLib\SQL\WhereCondition $value The condition to set.
    */
   public function offsetSet( $offset, $value )
   {

      if ( null === $offset )
      {
         $this->_conditions[] = $value;
      }
      else
      {
         $this->_conditions[ $offset ] = $value;
      }

   }

   /**
    * Offset to unset.
    *
    * @param mixed $offset The offset to unset.
    */
   public function offsetUnset( $offset )
   {

      unset( $this->_conditions[ $offset ] );

   }

   // </editor-fold>


   // <editor-fold desc="// ~ ~   C O U N T A B L E   I N T E R F A C E   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Count all current registered conditions.
    *
    * @return int
    */
   public function count()
   {

      return \count( $this->_conditions );

   }

   // </editor-fold>


   // <editor-fold desc="// ~ ~   I T E R A T O R A G G R E G A T E   I N T E R F A C E   ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Retrieve an external iterator
    *
    * @return Traversable An instance of an object implementing <b>Iterator</b> or <b>Traversable</b>
    */
   public function getIterator()
   {

      return new \ArrayIterator( $this->_conditions );

   }

   // </editor-fold>


   // <editor-fold desc="// ~ ~   O T H E R   M E T H O D S   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Gets the condition with defined Index
    *
    * @param int $index
    * @return \Messier\DBLib\SQL\WhereCondition|null
    */
   public function get( int $index ) : ?WhereCondition
   {

      return $this->offsetGet( $index );

   }

   /**
    * Gets the index (0-n) of the last defined where condition, or -1 of no condition is defined.
    *
    * @return int
    */
   public function indexOfLast() : int
   {

      return $this->count() - 1;

   }

   /**
    * Gets the WHERE clause SQL string (' WHERE conditions…')
    *
    * @param array  $bindParamsReference  Maybe used binding params are registered inside this array reference, if
    *                                     the method is done.
    * @param string $engine               The SQL engine. See constants of {@see \Messier\DBLib\SQL\Engine}
    * @param bool   $checkForKeywords     If TRUE column names are checked for key words and if a column name is
    *                                     a keyword it is enclosed inside double quotes ". If FALSE, all column
    *                                     names are enclosed by a double quote without a check.
    * @return string
    */
   public function toSQL( array &$bindParamsReference, string $engine, bool $checkForKeywords = false ) : string
   {

      $sql = '';

      // No conditions => No SQL
      if ( $this->count() < 1 ) { return $sql; }

      $sql = ' WHERE';
      $i   = 0;

      foreach ( $this as $condition )
      {

         $sql .= $condition->toSQL( $bindParamsReference, $engine, $i > 0, $checkForKeywords );
         $i++;

      }

      return $sql;

   }

   /**
    * Adds a new condition
    *
    * @param  string $columnName          The condition part column name.
    * @param  string $operator            The condition part conditional operator between the column name and the value.
    *                                     (eg. < > = IN etc.)
    * @param  mixed  $value               The condition part value. (if the operator is IN, here an array must be
    *                                     defined!)
    * @param  bool   $prepared            The condition part state flag if the value should be used as part of a
    *                                     prepared statement.
    * @param  string $prefix              The operator prefix to combine the condition with an condition before (AND|OR)
    * @param  int    $parenthesisesBefore The number of opening parenthesises before the condition (for some condition
    *                                     grouping reasons)
    * @param  int    $parenthesisesAfter  The number of closing parenthesises after the condition (for some condition
    *                                     grouping reasons)
    * @param  string|null $valueSQL       In some cases there must be declared an specific value SQL string that
    *                                     contains an prepared statement bind markup only as a part. (e.g.: '(? + 1)').
    *                                     For this cases, and only if the condition is prepared use this one in
    *                                     combination with the value.
    * @return \Messier\DBLib\SQL\Where
    * @throws \Messier\DBLib\PropertySetError
    */
   public function addCondition(
      string $columnName, string $operator, $value, bool $prepared = true, string $prefix = 'AND',
      int $parenthesisesBefore = 0, int $parenthesisesAfter = 0, ?string $valueSQL = null ) : Where
   {

      $this->_conditions[] = ( new WhereCondition( $columnName, $operator, $value, $prefix ) )
                              ->setPrepared( $prepared )
                              ->setParenthesisesAfter( $parenthesisesAfter )
                              ->setParenthesisesBefore( $parenthesisesBefore )
                              ->setValueSQL( $valueSQL );

      return $this;

   }

   /**
    * Adds a new raw/plain SQL condition.
    *
    * @param string $rawSQL   The raw where condition SQL string .
    * @param bool   $prepared Is a $rawSQL part prepared?
    * @param mixed  $value    If prepared, this is the bind value.
    * @param string $prefix   The operator prefix to combine the condition with an condition before (AND|OR)
    * @return \Messier\DBLib\SQL\Where
    */
   public function addRawCondition( string $rawSQL, bool $prepared = false, $value = null, string $prefix = 'AND' )
      : Where
   {

      $this->_conditions[] = WhereCondition::FromRawSQL( $rawSQL, $prepared, $value, $prefix );

      return $this;

   }

   /**
    * Removes all currently defined WHERE conditions.
    *
    * @return \Messier\DBLib\SQL\Where
    */
   public function clear() : Where
   {

      $this->_conditions = [];

      return $this;

   }

   // </editor-fold>


   // </editor-fold>


}

