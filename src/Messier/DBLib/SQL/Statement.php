<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib\SQL
 * @since          2016-11-11
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib\SQL;


use Messier\DBLib\Connection;


/**
 * Defines a SQL statement caller.
 *
 * If you want to run a SQL query sting (statement) you have to create a instance of this class.
 */
class Statement
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * If no query vars are defined, you can also enable the parsing for using default values if defined.
    *
    * The default values must be defined for it, as a part of the SQL query string.
    *
    * @type array
    */
   private $_parseQueryVars;

   /**
    * The database connection.
    *
    * @type \Messier\DBLib\Connection
    */
   private $_connection;

   /**
    * The SQL query string
    *
    * @type string
    */
   private $_sql;

   /**
    * Binding parameters if the SQL query string uses prepared statements.
    *
    * @type array
    */
   private $_params;

   /**
    * The query vars array if defined.
    *
    * @type array
    */
   private $_queryVars;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Statement constructor.
    *
    * @param \Messier\DBLib\Connection $connection     The required Database connection.
    * @param bool                      $parseQueryVars If no query vars are defined, you can also enable the parsing
    *                                                  for using default values if defined. The default values must be
    *                                                  defined for it, as a part of the SQL query string.
    */
   public function __construct( Connection $connection, bool $parseQueryVars = false )
   {

      $this->_connection     = $connection;
      $this->_parseQueryVars = $parseQueryVars;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * Fetches the found records and returns it as a array. NULL is returned if nothing was found.
    *
    * @param  string $sql        The SQL query string.
    * @param  array  $bindParams Optional binding parameters for prepared statement use (default=[])
    * @param  int    $fetchStyle The fetch style (default=\PDO::FETCH_ASSOC)
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values.
    * @return array
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchAll(
      string $sql, array $bindParams = [], int $fetchStyle = \PDO::FETCH_ASSOC, array $queryVars = [] )
      : array
   {

      $this->_params    = $bindParams;
      $this->_sql       = $sql;
      $this->_queryVars = $queryVars;

      # Parse query vars if required
      $this->parseQueryVars();

      try
      {

         if ( \count( $this->_params ) > 0 )
         {

            $stm     = $this->_connection->prepare( $this->_sql );
            $stm->execute( $this->_params );
            $records = $stm->fetchAll( $fetchStyle );

         }
         else
         {

            $records = $this->_connection->query( $this->_sql )->fetchAll( $fetchStyle );

         }

         if ( ! \is_array( $records ) || \count( $records ) < 1 )
         {
            return [];
         }

         return $records;

      }
      catch ( \Throwable $ex )
      {
         throw new QueryError(
            $this->_connection, $this->_sql, $this->_params, 'Fetching records fail.', 256, $ex );
      }

   }

   /**
    * Fetches all values of the selected single column.
    *
    * If more than one column is returned, always the value of the first selected column is defined.
    *
    * @param  string $sql        The SQL query string.
    * @param  array  $bindParams Optional binding parameters for prepared statement use (default=[])
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values.
    * @return array
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchColumn( string $sql, array $bindParams = [], array $queryVars = [] ) : array
   {

      $result = [];

      foreach ( $this->iterateFetchAll( $sql, $bindParams, \PDO::FETCH_NUM, $queryVars ) as $record )
      {

         if ( \count( $record ) < 1 ) { continue; }

         $result[] = $record[ 0 ];

      }

      return $result;

   }

   /**
    * Fetches all keys and values of the selected columns.
    *
    * The value if the key column is returned as array key and the value column defines the returned values.
    *
    * @param  string $sql             The SQL query string.
    * @param  string $keyColumnName   The name of the column with values, used as returned array keys.
    * @param  string $valueColumnName The name of the column with values, used as returned array values.
    * @param  array  $bindParams      Optional binding parameters for prepared statement use (default=[])
    * @param  array  $queryVars       Pre prepared statement Query-Vars bind values.
    * @return array
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchKeyValuePairs(
      string $sql, string $keyColumnName, string $valueColumnName, array $bindParams = [], array $queryVars = [] )
      : array
   {

      $result = [];

      foreach ( $this->iterateFetchAll( $sql, $bindParams, \PDO::FETCH_ASSOC, $queryVars ) as $record )
      {

         if ( \count( $record ) < 2 || ! isset( $record[ $keyColumnName ] ) || ! isset( $record[ $valueColumnName ] ) )
         {
            continue;
         }

         $result[ $record[ $keyColumnName ] ] = $record[ $valueColumnName ];

      }

      return $result;

   }

   /**
    * Fetches the found records and returns it as a iterable generator for use with foreach.
    *
    * @param  string $sql        The SQL query string.
    * @param  array  $bindParams Optional binding parameters for prepared statement use (default=[])
    * @param  int    $fetchStyle The fetch style (default=\PDO::FETCH_ASSOC)
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values.
    * @return \Generator
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function iterateFetchAll(
      string $sql, array $bindParams = [], int $fetchStyle = \PDO::FETCH_ASSOC, array $queryVars = [] )
      : \Generator
   {

      $this->_params    = $bindParams;
      $this->_sql       = $sql;
      $this->_queryVars = $queryVars;

      # Parse query vars if required
      $this->parseQueryVars();

      if ( \count( $this->_params ) > 0 )
      {

         try
         {

            // TODO: CHECKOUT THE SUPPORTED DBMS TYPES, OF "PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL"
            $stm = $this->_connection->prepare( $this->_sql, [ \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL ] );

            #$stm->setFetchMode( $fetchStyle );
            $stm->execute( $this->_params );

            while ( $record = $stm->fetch( $fetchStyle, \PDO::FETCH_ORI_NEXT ) )
            {
               yield $record;
            }

         }
         catch ( \Throwable $ex )
         {
            throw new QueryError(
               $this->_connection, $this->_sql, $this->_params, 'Fetching records fails.', 256, $ex );
         }

      }
      else
      {

         try
         {
            $stm = $this->_connection->query( $this->_sql );

            while ( $record = $stm->fetch( $fetchStyle, \PDO::FETCH_ORI_NEXT ) )
            {
               yield $record;
            }
         }
         catch ( \Throwable $ex )
         {
            throw new QueryError(
               $this->_connection, $this->_sql, $this->_params, 'Fetching a record fails.', 256, $ex );
         }

      }

   }

   /**
    * Fetches all values of the selected single column and returns it as a iterable generator for use with foreach.
    *
    * If more than one column is returned, always the value of the first selected column is defined.
    *
    * @param  string $sql        The SQL query string.
    * @param  array  $bindParams Optional binding parameters for prepared statement use (default=[])
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values.
    * @return \Generator
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function iterateFetchColumn( string $sql, array $bindParams = [], array $queryVars = [] ) : \Generator
   {

      foreach ( $this->iterateFetchAll( $sql, $bindParams, \PDO::FETCH_NUM, $queryVars ) as $record )
      {

         if ( \count( $record ) < 1 ) { continue; }

         yield $record[ 0 ];

      }

   }

   /**
    * Fetches all keys and values of the selected columns and returns it as a iterable generator for use with foreach.
    *
    * The value if the key column is returned as array key and the value column defines the returned values.
    *
    * @param  string $sql             The SQL query string.
    * @param  string $keyColumnName   The name of the column with values, used as returned array keys.
    * @param  string $valueColumnName The name of the column with values, used as returned array values.
    * @param  array  $bindParams      Optional binding parameters for prepared statement use (default=[])
    * @param  array  $queryVars       Pre prepared statement Query-Vars bind values.
    * @return \Generator
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function iterateFetchKeyValuePairs(
      string $sql, string $keyColumnName, string $valueColumnName, array $bindParams = [], array $queryVars = [] )
      : \Generator
   {

      foreach ( $this->iterateFetchAll( $sql, $bindParams, \PDO::FETCH_ASSOC, $queryVars ) as $record )
      {

         if ( \count( $record ) < 2 || ! isset( $record[ $keyColumnName ] ) || ! isset( $record[ $valueColumnName ] ) )
         {
            continue;
         }

         yield $record[ $keyColumnName ] => $record[ $valueColumnName ];

      }

   }

   /**
    * Fetches the first found record and returns it as a array. FALSE is returned if nothing was found.
    *
    * @param  string  $sql        The SQL statement to use.
    * @param  array   $bindParams Optional binding params for prepared statements (default=array())
    * @param  integer $fetchStyle The fetch style (default=\PDO::FETCH_ASSOC)
    * @param  array   $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return array|FALSE
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchRecord(
      string $sql, array $bindParams = [], int $fetchStyle = \PDO::FETCH_ASSOC, array $queryVars = [] )
      : ?array
   {

      $this->_params    = $bindParams;
      $this->_sql       = $sql;
      $this->_queryVars = $queryVars;

      # Parse query vars if required
      $this->parseQueryVars();

      try
      {

         if ( \count( $this->_params ) > 0 )
         {

            // We have an prepared statement with bind params

            // Prepare the SQL statement
            $stm = $this->_connection->prepare( $this->_sql );

            // Bind the parameters
            $stm->execute( $bindParams );

            // get all rows (finally the first is only required)
            $record = $stm->fetch( $fetchStyle );

         }
         else
         {

            $record = $this->_connection->query( $this->_sql )->fetch( $fetchStyle );

         }

      }
      catch ( \Throwable $ex )
      {

         throw new QueryError( $this->_connection, $sql, $bindParams, 'Fetching a record fails.', 256, $ex );

      }

      if ( ! \is_array( $record ) || \count( $record ) < 1 )
      {
         return [];
      }

      return $record;

   }

   /**
    * Fetches the first column value from the resulting found first record.
    *
    * @param  string $sql          The SQL statement to use.
    * @param  array  $bindParams   Optional binding params for prepared statements (default=[])
    * @param  mixed  $defaultValue This value is returned if no record was found by the query.
    * @param  array  $queryVars    Pre prepared statement Query-Vars bind values.
    * @return mixed
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchScalar( string $sql, array $bindParams = [], $defaultValue = false, array $queryVars = [] )
   {

      // Fetch the record to a numeric indicated array
      $record = $this->fetchRecord( $sql, $bindParams, \PDO::FETCH_NUM, $queryVars );

      if ( null === $record || \count( $record ) < 1 )
      {
         // No arms no cookies :-/
         return $defaultValue;
      }

      // Ensure we can access the required value by a numeric key 0
      $record = \array_values( $record );

      return $record[ 0 ];

   }

   /**
    * Fetches the first column value from the resulting found first record as boolean value.
    *
    * @param  string $sql          The SQL statement to use.
    * @param  array  $bindParams   Optional binding params for prepared statements (default=[])
    * @param  bool   $defaultValue This value is returned if no record was found by the query.
    * @param  array  $queryVars    Pre prepared statement Query-Vars bind values.
    * @return bool
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchBooleanScalar(
      string $sql, array $bindParams = [], bool $defaultValue = false, array $queryVars = [] )
      : bool
   {

      $value = $this->fetchScalar( $sql, $bindParams, $defaultValue, $queryVars );

      if ( \is_bool( $value ) )
      {
         return $value;
      }

      if ( \is_int( $value ) || is_float( $value ) )
      {
         return $value > 0;
      }

      if ( \is_string( $value ) )
      {
         return (bool) \preg_match( '~^(t(rue)|on|yes|enabled|ok|[1-9]\d*)$~i', $value );
      }

      return false;

   }

   /**
    * Fetches the first column value from the resulting found first record as integer value.
    *
    * @param  string $sql          The SQL statement to use.
    * @param  array  $bindParams   Optional binding params for prepared statements (default=[])
    * @param  int    $defaultValue This value is returned if no record was found by the query.
    * @param  array  $queryVars    Pre prepared statement Query-Vars bind values.
    * @return int
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchIntegerScalar(
      string $sql, array $bindParams = [], int $defaultValue = 0, array $queryVars = [] )
      : int
   {

      $value = $this->fetchScalar( $sql, $bindParams, $defaultValue, $queryVars );

      if ( \is_int( $value ) )
      {
         return $value;
      }

      if ( is_float( $value ) )
      {
         return (int) $value;
      }

      if ( is_bool( $value ) )
      {
         return $value ? 1 : 0;
      }

      if ( is_numeric( $value ) )
      {
         return (int) $value;
      }

      return $defaultValue;

   }

   /**
    * Fetches the first column value from the resulting found first record as float value.
    *
    * @param  string $sql          The SQL statement to use.
    * @param  array  $bindParams   Optional binding params for prepared statements (default=[])
    * @param  float  $defaultValue This value is returned if no record was found by the query.
    * @param  array  $queryVars    Pre prepared statement Query-Vars bind values.
    * @return float
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function fetchFloatScalar(
      string $sql, array $bindParams = [], float $defaultValue = 0.0, array $queryVars = [] )
      : float
   {

      $value = $this->fetchScalar( $sql, $bindParams, $defaultValue, $queryVars );

      if ( \is_float( $value ) )
      {
         return $value;
      }

      if ( is_int( $value ) )
      {
         return (float) $value;
      }

      if ( is_bool( $value ) )
      {
         return $value ? 1.0 : 0.0;
      }

      if ( is_numeric( $value ) )
      {
         return (float) $value;
      }

      return $defaultValue;

   }

   /**
    * Returns if the current connection knows an database with the defined name.
    *
    * @param  string $dbName The database name
    * @return bool
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function databaseExists( string $dbName ) : bool
   {

      $engine = $this->_connection->getEngine();

      if ( Engine::SQLITE === $engine || ! \preg_match( '~^[A-Za-z_][A-Za-z_0-9]*$~', $dbName ) )
      {
         return false;
      }

      if ( Engine::PGSQL === $engine )
      {
         // PostGreSQL
         /** @noinspection SqlResolve */
         $sql = "SELECT EXISTS ( SELECT true FROM information_schema.tables WHERE table_catalog = '{$dbName}');";
         return $this->fetchBooleanScalar( $sql );
      }

      // MySQL
      $query = "SHOW DATABASES LIKE '{$dbName}'";
      return ( false !== $this->fetchScalar( $query, [], false ) );

   }

   /**
    * Returns if the defined table exists inside the used database.
    *
    * If $db is defined it is used as database name. Otherwise the db name of current global connection instance
    * is used.
    *
    * @param  string $tableName
    * @param  string $db
    * @return bool
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function tableExists( string $tableName, $db = null ) : bool
   {

      if ( ! \preg_match( '~^[A-Za-z_][A-Za-z_0-9.]*$~', $tableName ) )
      {
         return false;
      }

      if ( empty( $db ) )
      {
         $db = $this->_connection->getDatabaseName();
      }

      switch ( $this->_connection->getEngine() )
      {

         case Engine::PGSQL:

            /** @noinspection SqlResolve */
            $query = "
               SELECT
                  EXISTS
                  (
                     SELECT
                           true
                        FROM
                           information_schema.tables
                        WHERE
                           table_name = ?
                              AND
                           table_catalog = ?
                  )";

            return $this->fetchBooleanScalar( $query, [ $tableName, $db ] );

         case Engine::MYSQL:

            /** @noinspection SqlResolve */
            $query = "
               SELECT
                     COUNT(*)
                  FROM
                     information_schema.TABLES
                  WHERE
                     TABLE_SCHEMA = ?
                        AND
                     TABLE_NAME = ?;";

            $res = $this->fetchIntegerScalar( $query, array( $db, $tableName ) );

            return $res > 0;

         //case Engine::SQLITE:
         default:

            /** @noinspection SqlResolve */
            $query = "
               SELECT
                     COUNT(*)
                  FROM
                     sqlite_master
                  WHERE
                     type = 'table'
                        AND
                     name = :tablename;";

            $res = $this->fetchIntegerScalar( $query, [ ':tablename' => $tableName ] );

            return $res > 0;

      }

   }

   /**
    * Returns how many records was found.
    *
    * @param  string                               $table    The name of the table
    * @param  \Messier\DBLib\SQL\Where|string|null $where    Optional WHERE clause
    * @param  array                                $bindings Binding params for prepared statements of WHERE clause part
    * @return int                                            Returns the count of found records.
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public final function count( string $table, $where = null, array $bindings = [] ) : int
   {

      $sql = "SELECT COUNT(*) AS cnt FROM {$table}";

      if ( null !== $where && '' !== \trim( $where ) )
      {
         if ( $where instanceof Where )
         {
            $sql .= $where->toSQL( $bindings, $this->_connection->getEngine() );
         }
         else
         {
            if ( ! \preg_match( '~^\s*WHERE\s~i', $where ) )
            {
               $sql .= ' WHERE ' . $where;
            }
            else
            {
               $sql .= ' ' . $where;
            }
         }
      }

      return $this->fetchIntegerScalar( $sql, $bindings );

   }

   /**
    * Execute the SQL query with the bind params as prepared statement.
    *
    * @param  string $sql
    * @param  array  $bindParams
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return bool
    * @throws \Messier\DBLib\SQL\QueryError
    */
   public function execute( string $sql, array $bindParams = [], array $queryVars = [] ) : bool
   {

      $this->_params    = $bindParams;
      $this->_sql       = $sql;
      $this->_queryVars = $queryVars;

      # Parse query vars if required
      $this->parseQueryVars();

      try
      {
         if ( 1 > \count( $this->_params ) )
         {
            return ( $this->_connection->exec( $this->_sql ) > -1 );
         }
         $stmt = $this->_connection->prepare( $this->_sql );
         return $stmt->execute( $this->_params );
      }
      catch ( \Exception $ex )
      {
         throw new QueryError( $this->_connection, $this->_sql, $this->_params, 'Query execution fails.', 256, $ex );
      }

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P R O T E C T E D   M E T H O D S   – – – – – – – – – – – – – – – – – – – – –">

   protected function parseQueryVars()
   {

      if ( \count( $this->_queryVars ) < 1 && ! $this->_parseQueryVars )
      {

         // Do nothing…
         return;

      }

      $this->_sql = \preg_replace_callback(

         // {$VarName=DefaultValue} 1=VarName 4=DefaultValue
         '~\\{\\$([A-Za-z0-9_.-]+)((\s*=)([A-Za-z0-9 \t?_:.<=>-]+)?)?\\}~',

         // The callback for doing the replacements
         [ $this, '_replaceQueryVarCallback' ],

         $this->_sql

      );

   }

   /**
    * @ignore
    * @param  array $match
    * @return string
    * @throws QueryError
    */
   protected function _replaceQueryVarCallback( array $match )
   {

      // This method is called as a callback of preg_replace inside the parseQueryVars method.

      // Get the default value if defined
      $defaultValue = null;
      if ( ! empty( $match[ 4 ] ) )
      {
         $defaultValue = \trim( $match[ 4 ] );
      }

      if ( isset( $this->_queryVars[ $match[ 1 ] ] ) )
      {

         // Use the existing query var

         if ( false !== \strpos( $this->_queryVars[ $match[ 1 ] ], '--' ) ||
            ! \preg_match( '~^[A-Za-z0-9 \t?_:.<=>-]+$~', $this->_queryVars[ $match[ 1 ] ] ) )
         {
            // SQL comments and not accepted chars
            throw new QueryError(
               $this->_connection,
               $this->_sql,
               $this->_params,
               'The defined query variable "' . $match[ 1 ] . '" defines an value with invalid format!'
            );
         }

         return $this->_queryVars[ $match[ 1 ] ];

      }

      if ( \is_null( $defaultValue ) )
      {
         throw new QueryError(
            $this->_connection,
            $this->_sql,
            $this->_params,
            'The query declares an query variable placeholder "' . $match[ 1 ] .
            '" without default value and without and assigned replacement value!'
         );
      }

      return $defaultValue;

   }

   // </editor-fold>


}

