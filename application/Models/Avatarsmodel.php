<?php namespace App\Models;

class Activationmodel extends \Codeigniter\Model {
    protected $table = "avatars";

    /**
     * Insert new avatar for user
     */
    public function create($filename, $userid){
        # Before insert, we make a soft delete
        $this->softDelete($userid);
        $datas = array(
            'filename'  => $filename,
            'userid'    => $userid
        );
        if( $this->db->table($this->table)->insert($datas) ){
            return true;
        }else{
            $this->db->error();
        }
    }
    /**
     * Soft delete with is_used flag to 0
     */
    public function softDelete($credential){
        $sql = "UPDATE ".$this->table
        ." SET is_used = 0"
        ." WHERE userid = '".$credential."'"
        ." OR filename = '".$credential."'";
        return $this->db->query($sql);
    }
}