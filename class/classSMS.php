<?php
/**********************************************************************
 *																	  *
 * ---------------- SMS BULK API klasa ------------------------       *
 * 																	  *
 * 	@Author Boris  													  *
 *  09/2015															  *
 **********************************************************************/
class SMS {

/**********************************************************************
* -------------- Uvecavanje polja za 1 jedinicu ---------------       *
**********************************************************************/
    public function incrementField($table,$field,$kveri){
        $updatekveri = "UPDATE `{$table}` SET `{$field}`= {$field} + 1 WHERE 1 {$kveri}";
        $this->db->query($updatekveri,1);

    }
}
?>