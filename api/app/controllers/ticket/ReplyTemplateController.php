<?php namespace ticket;
use Illuminate\Support\Facades\Response;
use ticketmodel\ReplyTemplateModel;
use ticketmodel\CaseTypeModel;
use Illuminate\Support\Facades\Input;

class ReplyTemplateController extends \BaseController {

    public function getIndex() {
        $itemsPerPage = Input::has("itemsPerPage") ? (int)Input::get('itemsPerPage') : 0;
        $currentPage  = Input::has("currentPage")   ?   (int)Input::get("currentPage") : 0;

        $listReplyTemplate = ReplyTemplateModel::take($itemsPerPage)->skip($itemsPerPage*($currentPage-1))->get();
        if(!$listReplyTemplate->isEmpty()) {
            $listTypeID = [];
            foreach($listReplyTemplate as $oneReplyTemplate) {
                $listTypeID[] = $oneReplyTemplate->type_id;
            }
            $listType = [];
            if(!empty($listTypeID)) {
                $type = CaseTypeModel::whereIn('id',$listTypeID)->get();
                if(!$type->isEmpty()) {
                    foreach($type as $oneType) {
                        $listType[$oneType->id] = $oneType;
                    }
                }
            }


            foreach($listReplyTemplate as $k => $oneReplyTemplate) {
                if(isset($listType[$oneReplyTemplate->type_id])) {
                    $listReplyTemplate[$k]->type = $listType[$oneReplyTemplate->type_id];
                }
            }
            $total = ReplyTemplateModel::count();
            $response = [
                'error'    =>  false,
                'data'      =>  $listReplyTemplate,
                'total'     =>  $total
            ];
        } else {
            $response = [
                'error'    =>  true,
                'message'   =>  'Không có dữ liệu'
            ];
        }
        return Response::json($response);
    }

    public function postSave($id = false) {
        $Active     = Input::has('active') ? (int)Input::get('active') : null;

        $template = ReplyTemplateModel::firstOrNew(['id'=>$id]);
        $template->message  =   Input::get('message');

        if(isset($Active)){
            $template->active   =   $Active;
        }

        $template->type_id  =   Input::get('type_id');
        $template->save();
        return Response::json([
            'error'    =>  false,
            'id'        =>  $template->id,
            'type'      =>  CaseTypeModel::find($template->type_id)
        ]);
    }

    public function getDetail($id) {
        $template = ReplyTemplateModel::find($id);
        return Response::json([
            'error' =>  false,
            'data'  =>  $template
        ]);
    }
}