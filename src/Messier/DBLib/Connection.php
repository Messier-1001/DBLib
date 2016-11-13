<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib
 * @since          2016-11-06
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib;


use Messier\DBLib\SQL\Engine;


/**
 * The database connection class.
 */
class Connection extends \PDO
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * The SQL engine. Known engines are defined as constants by class {@see \Messier\DBLib\SQL\Engine}
    *
    * @type string
    */
   private $_engine;

   /**
    * The DB-Host name or IP address or NULL if no host is required.
    *
    * @type string|null
    */
   private $_host;

   /**
    * The name of the initial open database. SQLite here requires the DB file path.
    *
    * @type string
    */
   private $_database;

   /**
    * The DB login user name, or NULL if not required.
    *
    * @type string|null
    */
   private $_userName;

   /**
    * The DB login password, or NULL if not required.
    *
    * @type string|null
    */
   private $_password;

   /**
    * The client + connection charset (default='UTF8')
    *
    * @type string
    */
   private $_charset;

   /**
    * Optional port if different from default.
    *
    * @type int|null
    */
   private $_port;

   /**
    * The ready to use connection DSN (must be generated!)
    *
    * @type string
    */
   private $_dsn;

   /**
    * @type  bool
    */
   private $_parseQueryVarsAlways = false;

   /**
    * @type \Messier\DBLib\Connection|null
    */
   private static $instance;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Connection constructor.
    *
    * @param  string        $engine   The SQL engine. Known engines are defined as constants by class
    *                                 {@see \Messier\DBLib\SQL\Engine}
    * @param  null|string   $host     The DB-Host name or IP address or NULL if no host is required.
    * @param  string        $database The name of the initial open database. SQLite here requires the DB file path.
    * @param  null|string   $username The DB login user name, or NULL if not required.
    * @param  null|string   $password The DB login password, or NULL if not required.
    * @param  string        $charset  The client + connection charset (default='UTF8')
    * @param  int|null|null $port     Optional port if different from default.
    * @throws \Messier\DBLib\DBLibError
    * @throws \Messier\DBLib\PropertySetError
    * @throws \Messier\DBLib\ConnectionError
    */
   public function __construct(
      string $engine, ?string $host, string $database, ?string $username, ?string $password,
      string $charset = 'UTF8', ?int $port = null )
   {

      if ( ! Engine::IsKnown( $engine ) )
      {
         throw new PropertySetError(
            'engine', $engine, 'Can not create a DB connection with an unknown engine.'
         );
      }

      $this->_engine   = $engine;
      $this->_host     = ( '' === $host )     ? null : $host;
      $this->_database = ( '' === $database ) ? null : $database;
      $this->_userName = ( '' === $username ) ? null : $username;
      $this->_password = ( '' === $password ) ? null : $password;
      $this->_charset  = $charset;
      $this->_port     = $port;

      $this->createDSN();

      $options = [
         \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
      ];

      switch ( $engine )
      {

         case Engine::PGSQL:

            try { parent::__construct( $this->_dsn, $this->_userName, $this->_password, $options ); }
            catch ( \Throwable $ex )
            {
               throw new ConnectionError( $this, 'Connection init fails!', 256, $ex );
            }

            if ( ! empty( $this->_charset ) )
            {
               try { $this->query( 'set client_encoding to ' . $charset ); }
               catch ( \Throwable $ex )
               {
                  throw new ConnectionError( $this, 'Setting the connection charset fails!', 256, $ex );
               }
            }

            break;

         case Engine::MYSQL:

            if ( ! empty( $this->_charset ) ) { $options[ \PDO::MYSQL_ATTR_INIT_COMMAND ] = 'SET NAMES ' . $charset; }

            try { parent::__construct( $this->_dsn, $this->_userName, $this->_password, $options ); }
            catch ( \Exception $ex )
            {
               throw new ConnectionError( $this, 'Connection init fails!', 256, $ex );
            }

            break;

         case Engine::SQLITE:

            try { parent::__construct( $this->_dsn, null, null, $options ); }
            catch ( \Exception $ex )
            {
               throw new ConnectionError( $this, 'Connection init fails!', 256, $ex );
            }

            break;

         default:

            throw new ConnectionError( $this, 'Connection init fails! Unknown engine…' );

      }

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P R O T E C T E D   M E T H O D S   – – – – – – – – – – – – – – – – – – – – –">

   /**
    * Builds the DSN from current settings and saves it internally.
    *
    * @throws \Messier\DBLib\DBLibError
    */
   protected function createDSN()
   {

      switch ( $this->_engine )
      {

         case Engine::PGSQL:

            if ( null === $this->_host )
            {
               throw new DBLibError( 'Can not create a "PostGreSQL" connection DSN if no host is defined!' );
            }

            if ( null === $this->_password )
            {
               $this->_password = '';
            }

            $dsn = \sprintf( 'pgsql:host=%s', $this->_host );

            if ( null !== $this->_database )
            {
               $dsn .= \sprintf( ';dbname=%s', $this->_database );
            }

            if ( null !== $this->_port && 5432 !== $this->_port )
            {
               $dsn .= ';port=' . $this->_port;
            }

            $this->_dsn = $dsn;

            break;

         case Engine::MYSQL:

            if ( null === $this->_host )
            {
               throw new DBLibError( 'Can not create a "MySQL" connection DSN if no host is defined!' );
            }

            if ( null === $this->_password )
            {
               $this->_password = '';
            }

            $dsn = \sprintf( 'mysql:host=%s', $this->_host );

            if ( null !== $this->_database )
            {
               $dsn .= \sprintf( ';dbname=%s', $this->_database );
            }

            if ( null !== $this->_port && 3306 !== $this->_port )
            {
               $dsn .= ';port=' . $this->_port;
            }

            $this->_dsn = $dsn;

            break;

         case Engine::SQLITE:

            $dsn = 'sqlite:';

            if ( null !== $this->_database )
            {
               $dsn .= $this->_database;
            }
            else
            {
               $dsn .= 'memory:';
            }

            $this->_dsn = $dsn;

            break;

      }

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – – ">

   /**
    * Returns the type of the based DBMS engine ({@see \Messier\DBLib\SQL\Engine::PGSQL},
    * {@see \Messier\DBLib\SQL\Engine::MYSQL} or {@see \Messier\DBLib\SQL\Engine::SQLITE})
    *
    * @return string
    */
   public function getEngine() : string
   {

      return $this->_engine;

   }

   /**
    * Returns the current used DB server host name or IP address, or NULL if not required.
    *
    * @return string|null
    */
   public function getHost() : ?string
   {

      return $this->_host;

   }

   /**
    * Returns the name of the currently connected database, or NULL if not required.
    *
    * @return string|null
    */
   public function getDatabaseName() : ?string
   {

      return $this->_database;

   }

   /**
    * Returns the username to login at DBMS server, or NULL if not required.
    *
    * @return string|null
    */
   public function getUserName() : ?string
   {

      return $this->_userName;

   }

   /**
    * Returns the connection/client charset.
    *
    * @return string
    */
   public function getCharset() : string
   {

      return $this->_charset;

   }

   /**
    * Returns the currently used server port. If no special is defined the default port of current engine is returned
    *
    * @return integer
    */
   public function getPort() : int
   {

      switch ( $this->_engine )
      {

         case Engine::PGSQL:
            $port = 5432;
            break;

         case Engine::MYSQL:
            $port = 3306;
            break;

         default:
            $port = 0;
            break;

      }

      return ( null === $this->_port || 1 < $this->_port )
         ? $port
         : $this->_port;

   }

   /**
    * Gets if each executed SQL statement should be parsed for the usage of Query-Params. Otherwise
    * it will only be parsed if some query param replacements are defined while running the executing methods.
    *
    * For performance issues set it to true if required and reset it to false if not required!
    *
    * @return bool
    */
   public final function getParseQueryVarsAlways() : bool
   {

      return $this->_parseQueryVarsAlways;

   }

   /**
    * sets if each executed SQL statement should be parsed for the usage of Query-Params. Otherwise
    * it will only be parsed if some query param replacements are defined while running the executing methods.
    *
    * For performance issues set it to true if required and reset it to false if not required!
    *
    * @param  bool $value
    * @return \Messier\DBLib\Connection
    * @since  v0.1.2
    */
   public final function setParseQueryVarsAlways( bool $value ) : Connection
   {

      $this->_parseQueryVarsAlways = $value;

      return $this;

   }

   /**
    * Sets an connection charset.
    *
    * @param  string $charset
    * @return bool
    */
   public final function setConnectionCharset( string $charset = 'utf8' ) : bool
   {

      if ( $this->_engine === Engine::SQLITE || \strtolower( $this->_charset ) === \strtolower( $charset ) )
      {
         return true;
      }

      try
      {

         if ( $this->_engine === Engine::PGSQL )
         {
            $this->exec( 'set client_encoding to ' . $charset );
         }
         else
         {
            $this->exec( 'SET NAMES ' . $charset );
         }

      }
      catch ( \Throwable $ex )
      {
         return false;
      }

      $this->_charset = $charset;

      return true;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   S T A T I C   M E T H O D S   – – – – – – – – – – – – – – – – –">

   /**
    * Returns, if a global Connection instance is defined.
    *
    * @return bool
    */
   public static function HasInstance() : bool
   {

      return ( null !== self::$instance );

   }

   /**
    * Gets the global instance if defined
    *
    * @return \Messier\DBLib\Connection|null
    */
   public static function GetInstance() : ?Connection
   {
      return self::$instance;
   }

   /**
    * Sets the globally usable connection.
    *
    * @param  \Messier\DBLib\Connection|null $instance
    * @return \Messier\DBLib\Connection|null
    */
   public static function SetInstance( ?Connection $instance = null ) : ?Connection
   {

      self::$instance = $instance;

      return self::$instance;

   }

   /**
    * Opens a new PGSQL connection and returns it.
    *
    * @param  string   $host           The DBMS host name or IP address
    * @param  string   $dbName         The name of the database that should be selected.
    * @param  string   $username       The login username.
    * @param  string   $password       The login password.
    * @param  string   $charset        Optional connection charset (Default is 'UTF8')
    * @param  int|null $port           Optional DB Port (Default is 5432)
    * @param  bool     $registerGlobal Register the created instance globally?
    * @return \Messier\DBLib\Connection
    * @throws \Messier\DBLib\DBLibError
    * @throws \Messier\DBLib\PropertySetError
    * @throws \Messier\DBLib\ConnectionError
    */
   public static function OpenPgSQL(
      string $host, string $dbName, string $username, string $password, string $charset = 'UTF8',
      int $port = 5432, bool $registerGlobal = true )
      : Connection
   {

      if ( ! $registerGlobal )
      {
         $conn = new Connection( Engine::PGSQL, $host, $dbName, $username, $password, $charset, $port );
         return $conn;
      }

      self::$instance = new Connection( Engine::PGSQL, $host, $dbName, $username, $password, $charset, $port );

      return self::$instance;

   }

   /**
    * Opens a new SQLITE connection and returns it.
    *
    * @param  string   $dbFile         The SQLITE db file. Empty means create in memory
    * @param  bool     $registerGlobal Register the created instance globally?
    * @return \Messier\DBLib\Connection
    * @throws \Messier\DBLib\DBLibError
    * @throws \Messier\DBLib\PropertySetError
    * @throws \Messier\DBLib\ConnectionError
    */
   public static function OpenSQLite( string $dbFile = null, bool $registerGlobal = true ) : Connection
   {

      if ( ! $registerGlobal )
      {
         return new Connection( Engine::SQLITE, '', $dbFile, '', '' );
      }

      self::$instance = new Connection( Engine::SQLITE, '', $dbFile, '', '' );

      return self::$instance;

   }

   // </editor-fold>


}

