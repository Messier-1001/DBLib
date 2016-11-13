<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib
 * @since          2016-10-31
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib;


/**
 * Defines a throwable error, triggered if an class property/field should become an invalid value.
 */
class PropertySetError extends DBLibError
{


   // <editor-fold desc="// – – –   P R O T E C T E D   F I E L D S   – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * The property name.
    *
    * @var string
    */
   protected $propertyName;

   /**
    * The new property value that should be set and is invalid.
    *
    * @type string
    */
   protected $propertyValue;

   /**
    * The (full qualified) class name.
    *
    * @var string
    */
   protected $className;

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * PropertySetError constructor.
    *
    * @param string $propertyName  The property name.
    * @param mixed  $propertyValue The new property value that should be set and is invalid.
    * @param string $message
    * @param int    $code
    * @param \Throwable|null $previous
    */
   public function __construct(
      string $propertyName, $propertyValue, string $message, $code = 256, ?\Throwable $previous = null )
   {

      // Getting the debug backtrace to find out the method/function that is called with an bad argument.
      $trace = \debug_backtrace();

      // Getting the index of the last trace element.
      $lIdx  = \count( $trace ) - 1;

      // Getting the class name.
      $cls = empty( $trace[ $lIdx ][ 'class' ] ) ? 'UNKNOWN' : $trace[ $lIdx ][ 'class' ];

      parent::__construct(
         'Can not set a new value for property "' . \ltrim( $propertyName, '$' )
         . '" of class "' . $cls . '". It uses a value of type '
         . static::GetTypeStr( $propertyValue )
         . static::appendMessage( $message ),
         $code,
         $previous
      );

      $this->rawMessage = $message;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">


   // <editor-fold desc="// ~ ~   G E T T E R S   ~ ~ ~ ~ ~ ~ ~ ~ ~ ~">

   /**
    * Returns the name of the error property.
    *
    * @return string
    */
   public final function getPropertyName() : string
   {

      return $this->propertyName;

   }

   /**
    * Returns the value of the error property.
    *
    * @return mixed
    */
   public final function getPropertyValue()
   {

      return $this->propertyValue;

   }

   /**
    * Returns the name of the error class.
    *
    * @return string
    */
   public final function getClassName() : string
   {

      return $this->className;

   }

   // </editor-fold>


   // </editor-fold>


   // <editor-fold desc="// – – –   P R O T E C T E D   S T A T I C   M E T H O D S   – – – – – – – – – – – – – –">

   /**
    * Returns a string, representing the permitted value.
    *
    * @param  mixed $value
    * @return string
    */
   protected static function GetTypeStr( $value ) : string
   {

      if ( null === $value )
      {
         return 'NULL';
      }

      if ( \is_resource( $value ) )
      {
         return \get_resource_type( $value ) . '-Resource';
      }

      if ( \is_string( $value ) )
      {
         if ( \strlen( $value ) > 128 )
         {
            return 'string with value (' . \substr( $value, 0, 126 ) . '…)';
         }
         return 'string with value (' . $value . ')';
      }

      if ( \is_bool( $value ) )
      {
         return 'boolean with value (' . ( $value ? 'true' : 'false' ) . ')';
      }

      if ( \is_int( $value ) )
      {
         return 'integer with value (' . $value . ')';
      }

      if ( \is_float( $value ) )
      {
         return 'float with value (' . $value . ')';
      }

      if ( \is_array( $value ) )
      {
         return 'Array';
      }

      if ( \is_object( $value ) )
      {
         return \get_class( $value ) . ' object';
      }

      return \gettype( $value );

   }

   // </editor-fold>


}

