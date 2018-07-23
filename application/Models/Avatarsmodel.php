<?php namespace App\Models;

class Avatarsmodel extends \CodeIgniter\Model {
    
    /* Define the table name */
    protected $table = "avatars";
    
    public function __construct() {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Generate random filename
     */
    public function generateRawName(){
        helper('text');
        $newRawName = \random_string('alnum', 32);
        $isFind = true;
        while ($isFind) {
            # Search the generate's ID in the table
            $sql = "SELECT raw_name FROM ".$this->table." WHERE raw_name = '".$newRawName."' LIMIT 1";
            $query = $this->db->query( $sql );
            // var_dump($query);
            /** Test de la valeur isFind */
            if( count($query->getResult()) > 0 ) {
                # code existe
                $isFind = true;
            }else {
                # ce code n'existe pas
                $isFind = false;
                break;
            }
        }
        return strtolower( $newRawName );
    }
    /**
     * Insert new avatar for user
     */
    public function create($datas){
        # Before insert, we make a soft delete
        $this->softDelete($datas['userid']);
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