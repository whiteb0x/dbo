<?php
class Page
{
    const SELECTION_BASE = 'SELECT * FROM pages WHERE TRUE';
    
    private static function _fromSql($sql, $params)
    {
        // the dbo::query syntax isn't standard.  It uses one of my database classes I have that *always* passes 
        // everything through mysqli_real_escape_string.  You have too use some sort of scheme like that.  
        // It has a number of other advantages (connection caching, etc) that should be employed.
        $res = dbo::query($sql, $params);
        if ($res)
        {
            if ($row = mysqli_fetch_assoc($res))
            {
                 return self::fromRow($row);
            }
        }
        // Handle error.  Either bad SQL or more likely, bad parameters.
        // You can, for instance, store it statically in a message queue.
    }
    
    // This function is nice because it basically casts an array into an object… and the $row doesn't have to be from SQL.
    public static function fromRow($row)
    {
        // we could actually use renaming maps here to do the heavy lifting, but that is more advanced.
        if (empty($row)) return NULL;
        $retVal = new Page();
        $retVal->field1 = $row['id'];
        $retVal->field2 = $row['page_title'];
        $retVal->field3 = $row['page_content'];
        $retVal->field4 = $row['time_created'];
        return $retVal;
    }

    public static function byId($id)
    {
         return self::_fromSql(self::SELECTION_BASE.' AND ID=%d', array($id)); 
    }

    // If you have additional unique keys (say, Model/Manufacturer) you can build additional static retrieval methods, which 
    // can re-use all of the code in fromSql and fromRow.
}
