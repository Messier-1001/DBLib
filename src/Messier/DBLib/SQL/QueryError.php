<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib
 * @since          2016-11-07
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib\SQL;


use Messier\DBLib\Connection;
use Messier\DBLib\ConnectionError;


/**
 * …
 */
class QueryError extends ConnectionError
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * The error SQL query string.
    *
    * @type string
    */
   private $_sql;

   /**
    * Bind params for prepared statements
    *
    * @type array
    */
   private $_params;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   public function __construct(
      Connection $connection, string $sql, array $params, ?string $message = null,
      $code = 256, ?\Throwable $previous = null )
   {

      parent::__construct(
         $connection,
         ( empty( $message ) ? '' : ( $message . ' ' ) )
            . "\nSQL:\n   "
            . \wordwrap( $sql, 120, "\n      " )
            ."\nPARAMS:\n   "
            . \preg_replace( '~\R~', "\n   ", \print_r( $params, true ) ),
         $code,
         $previous
      );

      $this->_sql    = $sql;
      $this->_params = $params;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * Gets the SQL string
    *
    * @return string
    */
   public function getSQL() : string
   {

      return $this->_sql;

   }

   /**
    * Gets the declared parameters for prepared statements.
    *
    * @return array
    */
   public function getParams() : array
   {

      return $this->_params;

   }

   // </editor-fold>


}

