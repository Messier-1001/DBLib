<?php
/**
 * @author         Messier 1001 <messier.1001+code@gmail.com>
 * @copyright  (c) 2016, Messier 1001
 * @package        Messier\DBLib\SQL
 * @since          2016-11-20
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace Messier\DBLib\SQL;


/**
 * Defines a SQL join.
 */
class Join
{


   // <editor-fold desc="// – – –   P R I V A T E   F I E L D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * The join type (see TYPE_* class constants)
    *
    * @type string
    */
   private $_type;

   /**
    * The name of the foreign (other) table.
    *
    * @type string
    */
   private $_foreignTableName;

   /**
    * The name of the foreign (other) table column.
    *
    * @type string
    */
   private $_foreignColumnName;

   /**
    * The name of this table column.
    *
    * @type string
    */
   private $_thisColumnName;

   // </editor-fold>


   // <editor-fold desc="// – – –   C L A S S   C O N S T A N T S   – – – – – – – – – – – – – – – – – – – – – – –">

   /**
    * A LEFT JOIN
    */
   public const TYPE_LEFT = 'LEFT';

   /**
    * A RIGHT JOIN
    */
   public const TYPE_RIGHT = 'RIGHT';

   /**
    * A INNER JOIN
    */
   public const TYPE_INNER = 'INNER';

   /**
    * A    public const TYPE_OUTER = 'OUTER';
   JOIN
    */
   public const TYPE_OUTER = 'OUTER';

   /**
    * A simple JOIN
    */
   public const TYPE_NONE = '';

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   C O N S T R U C T O R   – – – – – – – – – – – – – – – – – – – –">

   /**
    * Join constructor.
    *
    * @param string $type           The join type (see TYPE_* class constants)
    * @param string $foreignTable   The name of the foreign (other) table.
    * @param string $foreignColumn  The name of the foreign (other) table column.
    * @param string $thisColumn     The name of this table column.
    */
   public function __construct( string $type, string $foreignTable, string $foreignColumn, string $thisColumn )
   {

      $this->_type              = $type;
      $this->_foreignTableName  = $foreignTable;
      $this->_foreignColumnName = $foreignColumn;
      $this->_thisColumnName    = $thisColumn;

   }

   // </editor-fold>


   // <editor-fold desc="// – – –   P U B L I C   M E T H O D S   – – – – – – – – – – – – – – – – – – – – – – – –">

   public function __toString()
   {

      return   "\n   " .
               $this->_type .
               " JOIN\n         " .
               $this->_foreignTableName .
               "\n      ON\n        " .
               $this->_foreignColumnName .
               ' = ' .
               $this->_thisColumnName;

   }

   // </editor-fold>


}

