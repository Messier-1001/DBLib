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


/**
 * Defines a class that …
 */
class ConnectionError extends DBLibError
{


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * ConnectionError constructor.
    *
    * @param \Messier\DBLib\Connection $connection The connection
    * @param string                    $message    The error message
    * @param int                       $code
    * @param \Throwable|null           $previous
    */
   public function __construct( Connection $connection, string $message, $code = 256, ?\Throwable $previous = null )
   {

      parent::__construct(
         \sprintf(
            "%s connection error (host=%s%s; dbname=%s; user=%s; %s)",
            \ucfirst( $connection->getEngine() ),
            $connection->getHost() ?? '[undefined]',
            '; port=' . $connection->getPort(),
            $connection->getDatabaseName(),
            $connection->getUserName() ?? '[undefined]',
            '; charset=' . $connection->getCharset()
         ) . $this->appendMessage( $message ),
         $code,
         $previous
      );

   }

   // </editor-fold>


}

