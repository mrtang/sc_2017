<?php namespace ticket;

use Validator;
use Response;
use Input;
use ticketmodel\RatingModel;
use ticketmodel\RequestModel;
use LMongo;
use Excel;

class RatingController extends \BaseController {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getIndex()
	{
		return Response::json(array('name' => 'name1'));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function postCreate($id)
	{
		$UserInfo   = $this->UserInfo();
		/**
        *  Validation params
        * */
        
        Validator::getPresenceVerifier()->setConnection('ticketdb');
        
        $validation = Validator::make(array('id' => $id, 'question' => Input::json()->get('question')), array(
            'id'        => 'sometimes|numeric',
            'question'  => 'required|array'
        ));
        
        //error
        if($validation->fails()) {
            return Response::json(array('error' => true, 'message' => $validation->messages()));
        }

		$validation = Validator::make(Input::json()->all(), array(
			'comment'       => 'sometimes|required'
		));
		if($validation->fails()) {
			return Response::json(array('error' => true, 'message' => $validation->messages()));
		}


		/**
         * Get Data 
         * */
         
        $Question           = Input::json()->get('question');
        $Comment            = Input::json()->get('comment');
        $Source 			= Input::json()->get('source');
        
        $DataInsert         = array();

		$TicketModel    = new RequestModel;
		$Ticket = $TicketModel->where('id',$id)->first();
		if(!isset($Ticket->id)){
			$contents = array(
				'error'     => true,
				'message'   => 'TICKET_NOT_EXISTS'
			);
			return Response::json($contents);
		}

		//CHeck ticket chi được khách đóng khi đã lên trạng thái  đã xử lý
		if($UserInfo['privilege'] == 0 && in_array($Ticket->status, ['NEW_ISSUE','ASSIGNED','PENDING_FOR_CUSTOMER','CUSTOMER_REPLY'])){
			$contents = array(
				'error'     => true,
				'message'   => 'USER_NOT_ALLOW'
			);
			return Response::json($contents);
		}

        foreach($Question as $key => $val){
        	$Source = !empty($Source)  ? $Source  : '';
        	$Model = RatingModel::firstOrNew(['ticket_id'=> $id, 'question_id' => $key, 'rate' => $val, 'source'=> $Source, 'note' => $Comment]);

    		if($Model->time_create == 0){
    			$Model->time_create = time();
    			$Model->save();
    		}
        }
        
        $contents = array(
            'error'     => false,
            'message'   => 'succes'
        );
        
        /*if(!empty($DataInsert)){
            $Request = RequestModel::where('id','=',$id)->first();

            if(!empty($Request)){
                $Insert  = $Request->rating()->saveMany($DataInsert);
            
                if(!empty($Insert)){
                    if(!empty($Comment)){
                        $Request->comment   = $Comment;
                    }
                    if($Request->status != 'CLOSED'){
                    	$Request->status        = 'CLOSED';
                    	$Request->time_update   = time();
                    }

                    $Request->save();
                    
                    $contents = array(
                        'error'     => false,
                        'message'   => 'success'
                    );
                }
            }
        }*/
        
        return Response::json($contents);
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow()
	{
		$UserInfo = $this->UserInfo();
		$id       = (int)$UserInfo['id'];

        if($UserInfo['privilege'] == 0){
            $contents = array(
				'error'   => true,
				'message' => 'Bạn không có quyền!',
				'data'    => ''
            );
            return Response::json($contents);
        }

        
		$page      = Input::has('page')        ? (int)Input::get('page')                    : 1;
		$itemPage  = Input::has('limit')       ? (int)Input::get('limit')                   : 20;
		$Rate      = Input::has('rate')        ? (int)Input::get('rate')                    : "";
		$Keyword   = Input::has('keyword')     ? (int)Input::get('keyword')                 : "";
		$TimeStart = Input::has('time_start')  ? (int)Input::get('time_start')              : "";
		$TimeEnd   = Input::has('time_end')    ? (int)Input::get('time_end')                : "";
		$Cmd       = Input::has('cmd')         ? (int)Input::get('cmd')                     : "";

		$offset    = ($page - 1)*$itemPage;
		$Model     = new RatingModel;

		if(!empty($TimeStart)  && $TimeStart > 0){
			$Model = $Model->where('time_create', '>=', $TimeStart);
		}else {
			$TimeNow   = time();
			$TimeStart = $TimeNow - 30*86400;
			$Model     = $Model->where('time_create', '>=', $TimeStart);
		}

		if(!empty($TimeEnd)  && $TimeEnd > 0){
			$Model = $Model->where('time_create', '<=', $TimeEnd);
		}

		$Total = 0;
		$Data  = [];

		if($Rate){
			$Model = $Model->where('rate', $Rate);
		}

		$Model = $Model->where('question_id', 1);

		$Total = $Model->count();

		if($Total > 0){
			$Data = $Model 
				->with(['ticket' => function ($query){
					$query->with('users');
				}, 'case_ticket' => function($query) {
					$query->where('active',1)->with('case_type');
				}]);

			if($Cmd == 'export'){
            	return $this->Export($Data);
        	}
			$Data = $Data
				->orderBy('time_create', 'DESC')
				->skip($offset)
				->take($itemPage)
				->get();
		}else {
			return Response::json(array(
				'error'         => false,
				'error_message' => 'Thành công',
				'data'          => $Data,
				'total'			=> $Total
			));
		}

		/*$ListTicketId = [];

		foreach ($Data as $key => $value) {
			$ListTicketId[] = $value['id'];
		}

		if(!empty($ListTicketId)){
            $listLog = LMongo::collection('log_change_ticket')->whereIn('id', $ListTicketId)->where('new.status', 'PROCESSED')->get()->toArray();
            foreach ($listLog as $key => $val) {
                $Logs[(int)$val['id']] = $val;
            }
        }
        if(!empty($Logs)){
        	foreach ($Data as $key => $value) {
        		if(!empty($Logs[$value['id']])){
        			$Data[$key]['time_processed'] = $Logs[$value['id']]['time_create'];
        		}
        	}
        }*/


		return Response::json(array(
				'error'         => false,
				'error_message' => 'Thành công',
				'data'          => $Data,
				'total'			=> $Total
		));
	}



	public function Export($Model){
        $Data = $Model->get()->toArray();

        $FileName   = 'Danh_sach_danh_gia_yeu_cau';


        return Excel::create($FileName, function($excel) use($Data){
            $excel->sheet('Sheet1', function($sheet) use($Data){
                $sheet->mergeCells('E1:G1');
                $sheet->row(1, function ($row) {
                    $row->setFontSize(20);
                });
                $sheet->row(1, array('','','','','Danh sách đánh giá yêu cầu'));

                $sheet->setWidth(array(
                    'A'     =>  10, 'B'     =>  10, 'C'     =>  40, 'D'     =>  30, 'E'     =>  30, 'F'     =>  30, 'G'     =>  30,'H'     =>  30,
                    'I'  => 30, 'J'  => 30, 'K' => 30, 'L' => 30
                ));

                $sheet->row(3, array(
                    'STT', '#ID', 'Loại', 'Email',  'Tiêu đề', 'Trường hợp', 'Thời gian xử lý', 'Đánh giá','Thời gian đánh giá', 'Nội dung đánh giá'
                ));

                $sheet->row(3, function($row){
                    $row->setBackground('#989898')
                        ->setFontSize(12)
                        ->setFontWeight('bold')
                        ->setAlignment('center')
                        ->setValignment('top');
                });
                $sheet->setBorder('A3:K3', 'thin');

                $i = 1;
                foreach ($Data as $val) {
					$CaseTicket = '';
					foreach ($val['case_ticket'] as $v){
						if(isset($v['case_type']['type_name'])){
							$CaseTicket .= $v['case_type']['type_name'] .',';
						}
					}

                    $dataExport = array(
                        $i++,
                        isset($val['ticket']) ? $val['ticket']['id'] : '',
                        empty($val['source']) ? 'Xử lý' : 'Tiếp nhận',
                        
                        isset($val['ticket']['users']) ? $val['ticket']['users']['email'] : '',
                        isset($val['ticket']) ? $val['ticket']['title'] : '',
						$CaseTicket,

                        isset($val['ticket']) ? date("d/m/Y H:i:s", $val['ticket']['time_over']) : '',
                        
                        $val['rate'] == 1 ? 'Tốt' : 'Tệ',
                        date("d/m/Y H:i:s", $val['time_create']),
                        $val['note'] 
                    );
                    $sheet->appendRow($dataExport);
                }
            });
        })->export('xls');

    }

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function postUpdate($id)
	{
		$Explain = Input::has('explain') ? Input::get('explain') : "";
		$Model   = new RatingModel;
		$Model 	 = $Model->where('id', $id)->first();
		if(empty($Model)){
			return Response::json([
				'error' 		=> true,
				'error_message'	=> "Lỗi, không tìm thấy",
				'data'	=> ''
			]);
		}

		try {
			$Model->explain = $Explain;
			$Model->save();
		} catch (Exception $e) {
			return Response::json([
				'error' 		=> true,
				'error_message'	=> "Lỗi, truy vấn",
				'data'	=> ''
			]);
		}
		return Response::json([
			'error' 		=> false,
			'error_message'	=> "Thành công",
			'data'	=> ''
		]);


	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}
