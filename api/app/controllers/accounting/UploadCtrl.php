<?php namespace accounting;

class UploadCtrl extends BaseCtrl{
    public $link_stogare;
    public $link_download;
    public $type = '';
    public $table;

    private $filename;
    private $extension;

    function __construct(){

    }

    public function Upload(){
        if (!file_exists($this->link_stogare)) {
            File::makeDirectory($this->link_stogare, 0777, true, true);
        }

        $storage            = new \Upload\Storage\FileSystem($this->link_stogare);
        $file               = new \Upload\File('AccFile', $storage);
        $this->filename     = time().'_'.$file->getName();
        $this->extension    = $file->getExtension();

        $file->setName($this->filename);

        $file->addValidations(array(
            new \Upload\Validation\Mimetype(array('text/plain', 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')),
            new \Upload\Validation\Size('5M')
        ));

        try{
            $file->upload();
        }catch (Exception $e){
            return [
                'error'         => true,
                'code'          => 'UPLOAD_FAIL',
                'error_message' => $e->getMessage()
            ];
        }

        if(!empty($this->table)){
            return $this->InsertLog();
        }

        return [
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành công'
        ];
    }

    public function UploadHttps(){
        if (!file_exists($this->link_stogare)) {
            File::makeDirectory($this->link_stogare, 0777, true, true);
        }

        $storage            = new \Upload\Storage\FileSystem($this->link_stogare);
        $file               = new \Upload\File('AccFile', $storage);
        $this->filename     = time().'_'.$file->getName();
        $this->extension    = $file->getExtension();

        $file->setName($this->filename);

        $file->addValidations(array(
            new \Upload\Validation\Mimetype(array('text/plain', 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')),
            new \Upload\Validation\Size('5M')
        ));

        try{
            $file->upload();
        }catch (Exception $e){
            return [
                'error'         => true,
                'code'          => 'UPLOAD_FAIL',
                'error_message' => $e->getMessage()
            ];
        }

        if(!empty($this->table)){
            return $this->InsertLog();
        }

        return [
            'error'         => false,
            'code'          => 'SUCCESS',
            'error_message' => 'Thành công'
        ];
    }

    private function InsertLog(){
        $UserInfo   = $this->UserInfo();
        $LMongo     = new \LMongo;

        try {
            $IdLog = (string)$LMongo::collection($this->table)->insert(
                array(
                    'link_tmp'      => $this->link_stogare . DIRECTORY_SEPARATOR . $this->filename.'.'.$this->extension,
                    'link_download' => $this->link_download . $this->filename.'.'.$this->extension,
                    'name'          => $this->filename,
                    'user_id'       => (int)$UserInfo['id'],
                    'courier_id'    => Input::has('courier_id')         ? (int)(Input::get('courier_id'))       : 0,
                    'type'          => $this->type,
                    'action'        => array('del' => 0, 'insert' => 0),
                    'time_create'   => time()
                )
            );
            return [
                'error'         => false,
                'message'       => 'SUCCESS',
                'error_message' => 'Thành công',
                'id'            => $IdLog
            ];
        } catch (Exception $e) {
            return [
                'error'             => true,
                'message'           => 'INSERT_FAIL',
                'error_message'     => $e->getMessage()
            ];
        }
    }
}
