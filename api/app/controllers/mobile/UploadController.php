<?php namespace mobile;

use ticketmodel\AttachModel;
use omsmodel\ExchangeAttachModel;

class UploadController extends \BaseController {

	/**
	 * Upload file Ticket.
	 *
	 * @return Response
	 */
	public function postTicket($id)
	{
        $UserInfo   = $this->UserInfo();

        if(trim(\Input::get('key')) == 'request'){   //  key  feedback or request
            $Type = 1;
        }else{
            $Type = 2;
        }

        $LinkUpload = $this->Createfolder(\Hash::make($UserInfo['email']));

        $storage    = new \Upload\Storage\FileSystem($LinkUpload['uploadPath']);
        $file       = new \Upload\File('TicketFile', $storage);
        $FileName   =  $file->getNameWithExtension();

        // Optionally you can rename the file on upload
        $new_filename = $this->Encrypt32($UserInfo['email'].$UserInfo['id'].time());
        $file->setName($new_filename);

        // Validate file upload
        $file->addValidations(array(
            new \Upload\Validation\Mimetype(array('image/jpeg','image/png','image/jpg','application/vnd.ms-office','application/vnd.ms-excel','application/excel','application/x-excel','application/x-msexcel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/pdf')),
        
            //You can also add multi mimetype validation
            //new \Upload\Validation\Mimetype(array('image/png', 'image/gif'))
        
            // Ensure file is no larger than 5M (use "B", "K", M", or "G")
            new \Upload\Validation\Size('3M')
        ));
        // Access data about the file that has been uploaded
        /*$data = array(
            'name'       => $file->getNameWithExtension(),
            'extension'  => $file->getExtension(),
            'mime'       => $file->getMimetype(),
            'size'       => $file->getSize(),
            'md5'        => $file->getMd5(),
            //'dimensions' => $file->getDimensions()
        );*/
        $file->upload();


        $AttachModel    = new AttachModel;
        $InsertAttach   = $AttachModel::insert(array('refer_id' => (int)$id,'type' => $Type, 'name' => $FileName,'link_tmp' => $LinkUpload['linkPath'].'/'.$file->getNameWithExtension(), 'extension' => $file->getExtension(),'time_create' => time()));

        if($InsertAttach){
            $contents   = array(
                'error'     => false,
                'messege'   => 'success'
            );
        }else{
            $contents   = array(
                'error'     => true,
                'messege'   => 'insert fail'
            );
        }
        return \Response::json($contents);
	}

    /**
     * @param string $item
     * @return array
     */
    public function postExchange($id)
    {
        $LinkUpload = $this->Createfolder(\Hash::make(time()));

        $storage    = new \Upload\Storage\FileSystem($LinkUpload['uploadPath']);
        $file       = new \Upload\File('ExchangeFile', $storage);
        $FileName   =  $file->getNameWithExtension();
        // Optionally you can rename the file on upload
        $new_filename = $this->Encrypt32($id.time());
        $file->setName($new_filename);

        // Validate file upload
        $file->addValidations(array(
            new \Upload\Validation\Mimetype(array('image/jpeg','image/png','image/jpg')),

            //You can also add multi mimetype validation
            //new \Upload\Validation\Mimetype(array('image/png', 'image/gif'))

            // Ensure file is no larger than 5M (use "B", "K", M", or "G")
            new \Upload\Validation\Size('3M')
        ));
        // Access data about the file that has been uploaded
        /*$data = array(
            'name'       => $file->getNameWithExtension(),
            'extension'  => $file->getExtension(),
            'mime'       => $file->getMimetype(),
            'size'       => $file->getSize(),
            'md5'        => $file->getMd5(),
            //'dimensions' => $file->getDimensions()
        );*/
        $file->upload();


        $ExchangeAttachModel    = new ExchangeAttachModel;
        try{
            $ExchangeAttachModel::insert(['exchange_id' => $id, 'name' => $FileName,'link_tmp' => $LinkUpload['linkPath'].'/'.$file->getNameWithExtension(), 'extension' => $file->getExtension(),'time_create' => time()]);
            $contents   = array(
                'error'     => false,
                'messege'   => 'SUCCESS'
            );
        }catch (Exception $e){
            $contents   = array(
                'error'             => true,
                'messege'           => 'FAIL',
                'error_message'     => $e->getMessage()
            );
        }

        return \Response::json($contents);
    }

    function Createfolder($item = ''){

        $uploadPath = storage_path(). DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'other';
        $linkPath   = $this->link_upload.'/other';

        if(!empty($item)){
            $item   = str_split(preg_replace('/(\W)/','',(string)$item));

            for($i = 0; $i<5; $i++){
                if(isset($item[$i]) && $item[$i] != ''){
                    $uploadPath .= DIRECTORY_SEPARATOR.$item[$i];
                    $linkPath   .= '/'.$item[$i];
                }
            }

            if(!file_exists($uploadPath)){
                \File::makeDirectory($uploadPath,0777, true, true);
            }
        }

        return array(
            'uploadPath'    => $uploadPath,
            'linkPath'      => $linkPath
        );
    }
}
