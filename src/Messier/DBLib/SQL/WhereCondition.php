<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib\SQL
 * @since          2016-10-31
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib\SQL;


use Messier\DBLib\PropertySetError;


/**
 * Defines a class that represents a single POSTGRES compatible SQL WHERE clause condition part.
 *
 * It defines:
 *
 * - The condition part column name.
 * - The condition part conditional operator between the column name and the value. (eg. < > = IN etc.)
 * - The condition part value. (if the operator is IN, here an array must be defined!)
 * - The condition part state flag if the value should be used as part of a prepared statement.
 * - The operator prefix to combine the condition with an condition before (AND|OR)
 * - The number of opening parenthesises before the condition (for some condition grouping reasons)
 * - The number of closing parenthesises after the condition (for some condition grouping reasons)
 */
class WhereCondition
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * The condition part column name.
    *
    * @type string
    */
   private $_columnName;

   /**
    * The condition part conditional operator between the column name and the value. (eg. < > = IN etc.)
    *
    * @type string
    */
   private $_operator;

   /**
    * The condition part value. (if the operator is IN, here an array must be defined!)
    *
    * @type mixed
    */
   private $_value;

   /**
    * In some cases there must be declared an specific value SQL string that contains an prepared statement bind
    * markup only as a part. (e.g.: '(? + 1)'). For this cases, and only if the condition is prepared use this one in
    * combination with the value.
    *
    * @type string|null
    */
   private $_valueSQL;

   /**
    * The condition part state flag if the value should be used as part of a prepared statement.
    *
    * @type bool
    */
   private $_prepared;

   /**
    * The operator prefix to combine the condition with an condition before (AND|OR)
    *
    * @type string
    */
   private $_prefix;

   /**
    * The number of opening parenthesises before the condition (for some condition grouping reasons)
    *
    * @type int
    */
   private $_parenthesisesBefore;

   /**
    * The number of closing parenthesises after the condition (for some condition grouping reasons)
    *
    * @type int
    */
   private $_parenthesisesAfter;

   private $_rawSQL;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Init a new WhereCondition instance.
    *
    * @param string $columnName The condition part column name.
    * @param string $operator   The condition part conditional operator between the column name and the value. (eg. < > = IN etc.)
    * @param mixed  $value      The condition part value. (if the operator is IN, here an array must be defined!)
    * @param string $prefix     The operator prefix to combine the condition with an condition before (AND|OR)
    */
   public function __construct( string $columnName, string $operator, $value, string $prefix = 'AND' )
   {

      $this->_columnName            = $columnName;
      $this->_operator              = \strtoupper( $operator );
      $this->_value                 = $value;
      $this->_prefix                = $prefix;
      $this->_valueSQL              = null;
      $this->_prepared              = false;
      $this->_parenthesisesBefore   = 0;
      $this->_parenthesisesAfter    = 0;
      $this->_rawSQL                = null;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


   // <editor-fold desc="// ~ ~   G E T T E R S   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Gets the condition part column name.
    *
    * @return string
    */
   public function getColumnName()   : string
   {

      return $this->_columnName;

   }

   /**
    * Gets the condition part conditional operator between the column name and the value. (eg. < > = IN etc.)
    *
    * @return string
    */
   public function getOperator()   : string
   {

      return $this->_operator;

   }

   /**
    * Gets the condition part value. (if the operator is IN, here an array must be defined!)
    *
    * @return mixed
    */
   public function getValue()
   {

      return $this->_value;

   }

   /**
    * In some cases there must be declared an specific value SQL string that contains an prepared statement bind
    * markup only as a part. (e.g.: '(? + 1)'). For this cases, and only if the condition is prepared use this one in
    * combination with the value.
    *
    * @return null|string
    */
   public function getValueSQL() : ?string
   {

      return $this->_valueSQL;

   }

   /**
    * Gets the condition part state flag if the value should be used as part of a prepared statement.
    *
    * @return boolean
    */
   public function isPrepared()   : bool
   {

      return $this->_prepared;

   }

   /**
    * Gets the operator prefix to combine the condition with an condition before (AND|OR)
    *
    * @return string
    */
   public function getPrefix()   : string
   {

      return $this->_prefix;

   }

   /**
    * Gets the number of opening parenthesises before the condition (for some condition grouping reasons)
    *
    * @return int
    */
   public function getParenthesisesBefore()   : int
   {

      return $this->_parenthesisesBefore;

   }

   /**
    * Gets the number of closing parenthesises after the condition (for some condition grouping reasons)
    *
    * @return int
    */
   public function getParenthesisesAfter()   : int
   {

      return $this->_parenthesisesAfter;

   }

   /**
    * Gets the raw condition SQL if defined.
    *
    * @return null|string
    */
   public function getRawSQL() : ?string
   {

      return $this->_rawSQL;

   }

   // </editor-fold>


   // <editor-fold desc="// ~ ~   S E T T E R S   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Sets the condition part column name.
    *
    * @param  string $columnName
    * @return \Messier\DBLib\SQL\WhereCondition
    * @throws \Messier\DBLib\PropertySetError If the new value is invalid
    */
   public function setColumnName( string $columnName ) : WhereCondition
   {

      if ( empty( $columnName ) )
      {
         throw new PropertySetError( 'columnName', $columnName, 'A where column name can not be empty!' );
      }

      if ( ! \preg_match( '~^"?[A-Za-z_][A-Za-z0-9_]*"?(\."?[A-Za-z_][A-Za-z0-9_]*"?)?$~', $columnName ) )
      {
         if ( false !== \strpos( $columnName, '`' ) )
         {
            throw new PropertySetError(
               'columnName',
               $columnName,
               'Invalid where column name format! Can not use ` from MySQL for Postgres!'
            );
         }
         throw new PropertySetError( 'columnName', $columnName, 'Invalid where column name format!' );
      }

      $this->_columnName = $columnName;

      return $this;

   }

   /**
    * Sets the condition part conditional operator between the column name and the value. (eg. < > = IN etc.)
    *
    * @param  string $operator
    * @return \Messier\DBLib\SQL\WhereCondition
    * @throws \Messier\DBLib\PropertySetError If the new value is invalid
    */
   public function setOperator( string $operator ) : WhereCondition
   {

      if ( empty( $operator ) )
      {
         throw new PropertySetError( 'operator', $operator, 'A where operator name can not be empty!' );
      }

      if ( ! \preg_match( '~^(<|>|<>|=|LIKE|IN|IS(\s+NOT)?)$~i', $operator ) )
      {
         throw new PropertySetError( 'operator', $operator, 'Invalid where operator!' );
      }

      $this->_operator = \strtoupper( $operator );

      return $this;

   }

   /**
    * Sets the condition part value. (if the operator is IN, here an array must be defined!)
    *
    * @param  mixed $value
    * @return \Messier\DBLib\SQL\WhereCondition
    */
   public function setValue( $value ) : WhereCondition
   {

      $this->_value = $value;

      return $this;

   }

   /**
    * In some cases there must be declared an specific value SQL string that contains an prepared statement bind
    * markup only as a part. (e.g.: '(? + 1)'). For this cases, and only if the condition is prepared use this one in
    * combination with the value.
    *
    * @param  null|string $valueSQL
    * @return \Messier\DBLib\SQL\WhereCondition
    */
   public function setValueSQL( ?string $valueSQL ) : WhereCondition
   {

      $this->_valueSQL = $valueSQL;

      return $this;

   }

   /**
    * Sets the condition part state flag if the value should be used as part of a prepared statement.
    *
    * @param  boolean $prepared
    * @return \Messier\DBLib\SQL\WhereCondition
    */
   public function setPrepared( bool $prepared ) : WhereCondition
   {

      $this->_prepared = $prepared;

      return $this;

   }

   /**
    * Sets the operator prefix to combine the condition with an condition before (AND|OR)
    *
    * @param  string $prefix
    * @return \Messier\DBLib\SQL\WhereCondition
    * @throws \Messier\DBLib\PropertySetError If the new value is invalid
    */
   public function setPrefix( string $prefix ) : WhereCondition
   {

      if ( ! \preg_match( '~^(AND|OR)$~i', $prefix ) )
      {
         throw new PropertySetError( 'prefix', $prefix, 'Invalid where prefix (AND/OR is valid)!' );
      }

      $this->_prefix = $prefix;

      return $this;

   }

   /**
    * Sets the number of opening parenthesises before the condition (for some condition grouping reasons)
    *
    * @param  int $parenthesisesBefore
    * @return \Messier\DBLib\SQL\WhereCondition
    * @throws \Messier\DBLib\PropertySetError If the new value is invalid
    */
   public function setParenthesisesBefore( int $parenthesisesBefore ) : WhereCondition
   {

      if ( $parenthesisesBefore > 10 )
      {
         throw new PropertySetError( 'parenthesisesBefore', $parenthesisesBefore,
                                     'A where condition can not use more than 10 open parenthesises before!' );
      }

      if ( $parenthesisesBefore < 0 )
      {
         $parenthesisesBefore = 0;
      }

      $this->_parenthesisesBefore = $parenthesisesBefore;

      return $this;

   }

   /**
    * Sets the number of closing parenthesises after the condition (for some condition grouping reasons)
    *
    * @param  int $parenthesisesAfter
    * @return \Messier\DBLib\SQL\WhereCondition
    * @throws \Messier\DBLib\PropertySetError If the new value is invalid
    */
   public function setParenthesisesAfter( int $parenthesisesAfter ) : WhereCondition
   {

      if ( $parenthesisesAfter > 10 )
      {
         throw new PropertySetError( 'parenthesisesAfter', $parenthesisesAfter,
            'A where condition can not use more than 10 closing parenthesises after!' );
      }

      if ( $parenthesisesAfter < 0 )
      {
         $parenthesisesAfter = 0;
      }

      $this->_parenthesisesAfter = $parenthesisesAfter;

      return $this;

   }

   /**
    * Sets the raw condition SQL if defined.
    *
    * @param  null|string $rawSQL
    * @return \Messier\DBLib\SQL\WhereCondition
    */
   public function setRawSQL( ?string $rawSQL ) : WhereCondition
   {

      $this->_rawSQL = ( '' === $rawSQL ) ? null : $rawSQL;

      return $this;

   }

   // </editor-fold>


   /**
    * This method creates the SQL, depending to the current WHERE element definition, and register the required
    * binding param(s) (if used) to global binding params array.
    *
    * @param array   $bindingParamsReference Maybe used binding params are registered inside this array reference, if
    *                                        the method is done.
    * @param string  $engine                 The SQL engine. See constants of {@see \Messier\DBLib\SQL\Engine}
    * @param bool    $includePrefix          Include the prefix (AND/OR) inside the returned SQL?
    * @param bool    $checkForKeywords       If TRUE column names are checked for key words and if a column name is
    *                                        a keyword it is enclosed inside double quotes ". If FALSE, all column
    *                                        names are enclosed by a double quote without a check.
    * @return string Returns the resulting SQL string, including the may required prefix operator ('AND' or 'OR')
    */
   public function toSQL(
      array &$bindingParamsReference, string $engine, bool $includePrefix = true, bool $checkForKeywords = false )
      : string
   {

      if ( null !== $this->_rawSQL )
      {
         $sql = ( $includePrefix ? ' ' . $this->_prefix : '' ) . ' ' . \ltrim( $this->_rawSQL, " \t\r\n" );
         if ( $this->_prepared ) { $bindingParamsReference[] = $this->_value; }
         return $sql;
      }

      // Create SQL for column and operator
      $sql = ' '
         . ( $this->_parenthesisesBefore > 0 ? \str_repeat( '( ', $this->_parenthesisesBefore ) : '' )
         . static::PrepareColumnName( $this->_columnName, $engine, $checkForKeywords )
         . ' '
         . $this->_operator;

      if ( $this->_operator === 'IN' )
      {

         // The IN operator is used so the value must be an numeric indicated array with integer values

         if ( ! \is_array( $this->_value ) )
         {
            // The value must be a int array
            $this->_value = [ (int) $this->_value ];
         }

         if ( $this->_value !== \array_filter( $this->_value, '\\is_int' ) )
         {
            // convert no integers to integers
            for ( $i = 0, $c = \count( $this->_value ); $i < $c; $i++ )
            {
               $this->_value[ $i ] = (int) $this->_value[ $i ];
            }
         }

      }

      if ( $this->_prepared )
      {

         // Handle WHERE elements with bindParam(s) for prepared statements

         if ( $this->_operator === 'IN' )
         {

            // Create the IN(…) part
            $sql .= '( ?' . \str_repeat( ', ?', \count( $this->_value ) - 1 ) . ' )';

            // Remember the bind params
            $bindingParamsReference = \array_merge( $bindingParamsReference, $this->_value );

         }
         else
         {

            // All other operators require a simple value

            if ( ! empty( $this->_valueSQL ) )
            {
               $sql .= ' ' . \ltrim( $this->_valueSQL );
            }
            else
            {
               $sql .= ' ?';
            }

            // Register the binding parameter
            $bindingParamsReference[] = $this->_value;

         }

      }
      else
      {

         // No prepared stuff. Write the value as it.

         if ( $this->_operator === 'IN' )
         {

            // Create the IN(…) part
            $sql .= '( ' .\implode( ', ', $this->_value ) . ' )';

         }
         else
         {

            $sql .= ' ' . $this->_value;

         }

      }

      if ( $this->_parenthesisesAfter > 0 )
      {
         $sql .= \str_repeat( ' ) ', $this->_parenthesisesAfter );
      }

      if ( $includePrefix )
      {

         // Write the prefix operator 'AND' or 'OR' if this is not the first WHERE element.
         return ' ' . $this->_prefix . $sql;

      }

      // Return the resulting SQL WHERE element string
      return $sql;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   S T A T I C   M E T H O D S   – – – – – – – – – – – – – – – – –">

   /**
    * Prepares the defined column name for DBMS specific usage
    *
    * @param  string $columnName       The column name.
    * @param  string $engine           The SQL engine. See constants of {@see \Messier\DBLib\SQL\Engine}
    * @param  bool   $checkForKeywords Only enclose keywords in "" (this requires to check if name (part) is a keyword)
    * @return string
    */
   public static function PrepareColumnName( string $columnName, string $engine, bool $checkForKeywords = true )
   {

      $parts     = \explode( '.', $columnName );
      $quoteChar = Engine::KeywordQuoteChar( $engine );

      for ( $i = 0, $c = \count( $parts ); $i < $c; $i++ )
      {

         if ( $checkForKeywords )
         {

            if ( Engine::IsKeyword( $engine, $parts[ $i ] ) )
            {
               $parts[ $i ] = $quoteChar . \trim( $parts[ $i ], " \t\r\n" . $quoteChar ) . $quoteChar;
               continue;
            }

         }

         $parts[ $i ] = $quoteChar . \trim( $parts[ $i ], " \t\r\n" . $quoteChar ) . $quoteChar;

      }

      return \implode( '.', $parts );

   }

   /**
    * Init a new condition from raw SQL condition string like (foo > 0)
    *
    * @param string $rawSQL   The raw where condition SQL string .
    * @param bool   $prepared Is a $rawSQL part prepared?
    * @param mixed  $value    If prepared, this is the bind value.
    * @param string $prefix   The operator prefix to combine the condition with an condition before (AND|OR)
    * @return \Messier\DBLib\SQL\WhereCondition
    */
   public static function FromRawSQL(
      string $rawSQL, bool $prepared = false, $value = null, string $prefix = 'AND' ) : WhereCondition
   {

      return ( new WhereCondition( '', '', $value, $prefix ) )->setRawSQL( $rawSQL )->setPrepared( $prepared );

   }

   // </editor-fold>


}

