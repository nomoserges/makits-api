<?php namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;
use App\Models\Avatarsmodel;

class Avatars extends Controller {
    protected $request;
    private $status;
    private $message;
    private $datas;

    /* Constructor */
    public function __constructor() {
        parent::__constructor();
    }
    /* adding avatar */
    public function setAvatar() {
        $data = json_decode(trim(file_get_contents('php://input')), true);
        var_dump( $data );
        $avatarsModel = new Avatarsmodel;
        $theRawName = $avatarsModel->generateRawName();
        $datasModel = array(
            'raw_name'      => $theRawName,
            'filename'      => $theRawName.'.'.explode(".", $data['image_name'])[1],
            'userid'        => $data['userid'],
            'image_type'    => $data['image_type'],
            'image_size'    => $data['image_size']
        );
        # we insert before create the file on filesystem
        if ( $avatarsModel->create($datasModel) === true ) {
            # we create the file
            file_put_contents(
                WRITEPATH.'uploads/'.$datasModel['filename'],
                base64_decode($data['image_binary'])
            );
        } else {
            # code...
        }
        
        
        //$file = $_FILES;
        /*
        $this->validate([
            'useravatar' => 'uploaded[useravatar]|max_size[useravatar,1024]'
        ]);
        echo $this->validator->listErrors();
        */
        /*
        file_put_contents($data['useravatar'], base64_decode($data['image_binary']));
        $this->validate([
            'useravatar' => 'uploaded[useravatar]|max_size[useravatar,1024]'
        ]);
        echo $this->validator->listErrors();
        */
    }
}

