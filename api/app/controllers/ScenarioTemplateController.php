<?php
class ScenarioTemplateController extends \BaseController {
    private $domain = '*';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
     
    public function __construct(){
        
    }
    //
    public function getIndex()
	{  
        $page       = Input::has('page') ? (int)Input::get('page') : 1;
        $itemPage   = Input::has('item_page') ? (int)Input::get('item_page') : 20;
        $offset     = ($page - 1)*$itemPage;
       
        $Model  = new ScenarioTemplateModel;
        $Data   = $Model::skip($offset)->take($itemPage)->orderBy('id','DESC')->get();
        if($Data){
            $Scenario = ScenarioModel::all(array('id','name'));
            if($Scenario){
                foreach($Scenario as $val){
                    $LScenario[$val['id']] = $val['name'];
                }
                foreach($Data as $key => $val){
                    if (isset($LScenario[$val['scenario_id']])){
                        $val->scenario_name = $LScenario[$val['scenario_id']];
                    }
                }
            }
            $Template = TemplateModel::all(array('id','title'));
            if($Template){
                foreach($Template as $value){
                    $LTemplate[$value['id']] = $value['title'];
                }
                foreach($Data as $key => $v){
                    if (isset($LTemplate[$v['template_id']])){
                        $v->template_name = $LTemplate[$v['template_id']];
                    }
                }
            }
        }
      	
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'total'         => $Model::count(),
            'item_page'     => $itemPage,
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getShow($Id)
	{
        $Model      = new ScenarioTemplateModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        if($Model){
            $contents = array(
                'error'     => false,
                'message'   => 'success',
                'data'      => $Model
            );
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
    //
    public function getTemplatebyscenario($id){
        $Model  = new ScenarioTemplateModel;
        $output = array();
        $listTemplate = TemplateModel::get(array('id','title','transport_id'))->toArray();
        $arrTemp = array();
        foreach($listTemplate AS $v){
            $arrTemp[] = $v['id'];
        }

        $Data = $Model::where('scenario_id','=',(int)$id)->get(array('id','template_id'))->toArray();
        if($Data){
            $arr_return = array();
            foreach($listTemplate AS $one){
                $output[$one['id']] = $one;
            }
            foreach($Data AS $val){
                if(in_array($val['template_id'], $arrTemp)){
                    $output[$val['template_id']]['checked'] = 1;
                }else{
                    $output[$val['template_id']]['checked'] = 0;
                }
            }

            foreach($output AS $v){
                $arr_return[] = $v;
            }
            $statusCode = 200;
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'data'          => $arr_return
            );
        }else{
            $statusCode = 200;
            $contents = array(
                'error'         => false,
                'message'       => 'success',
                'data'          => $listTemplate
            );
        }        
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
	/**
	 * Remove.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function getDestroy($Id)
	{
        $Model      = new ScenarioTemplateModel;
        $Model      = $Model::find($Id);
        $statusCode = 200;
        
        if($Model){
            $Delete = $Model->delete();
            if($Delete){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'delete error'
                );
            }
        }else{
            $contents = array(
                'error'     => true,
                'message'   => 'not exits'
            );
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
	}
    //
    public function getScenariotemplatebyidscenario($id)
    {  
        $Model  = new ScenarioTemplateModel;
        $Data       = $Model::where('scenario_id','=',(int)$id)->get(array('id','template_id'));
        // if($Data){
        //     $Template       = TemplateModel::all(array('id','title'));
        //     if($Template){
        //         foreach($Template as $val){
        //             $LTemplate[$val['id']] = $val['title'];
        //         }
        //         foreach($Data as $key => $val){
        //             if (isset($LTemplate[$val['template_id']])){
        //                 $val->template_name = $LTemplate[$val['template_id']];
        //             }
        //         }
        //     }
        // }
        $statusCode = 200;
        $contents = array(
            'error'         => false,
            'message'       => 'success',
            'data'          => $Data
        );
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
    //
    public function postAction()
    { 
        $Data               = Input::json()->all();
        $Scenario           = isset($Data['scenario_id'])           ? $Data['scenario_id']          : null;
        $Template           = isset($Data['template_id'])         ? $Data['template_id']            : null;
        
        $Model      = new ScenarioTemplateModel;
        $statusCode = 200;
        
        //
        $info = $Model::where('scenario_id','=',$Scenario)->where('template_id','=',$Template)->get()->toArray();
        if(!empty($info)){
            $Delete = $Model::where('scenario_id','=',$Scenario)->where('template_id','=',$Template)->delete();
            if($Delete){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success'
                );
            }else{
                $contents = array(
                    'error'     => true,
                    'message'   => 'delete error'
                );
            }
        }else{
            $Id = $Model::insertGetId(
                    array(
                        'scenario_id'      => $Scenario, 
                        'template_id'          => $Template 
                    ));
        
            if($Id){
                $contents = array(
                    'error'     => false,
                    'message'   => 'success',
                    'id'        => $Id
                );
            }else{
                $contents = array(
                    'error' => true,
                    'message' => 'insert false'
                );
            }
        }
        
        return Response::json($contents, $statusCode, array('Access-Control-Allow-Origin' => $this->domain));
    }
}
?>