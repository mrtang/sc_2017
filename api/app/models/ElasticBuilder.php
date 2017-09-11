<?php
use Elasticsearch\Client;

class ElasticBuilder {

	protected $must 	= [];
	protected $should   = [];
	protected $must_not = [];

	protected $_sort    = [];
	protected $_groupBy = [];

	protected $fields 	= [];

	protected $from		 = 0;
	protected $size 	 = 10;

	protected $DSLQuery  = [];

	protected $index 	 = "";
	protected $type  	 = "";

	protected $RawResult   = [];

	protected $TotalRecord = 0;

	private $list_operand = ['=', '!=', 'gt', 'gte', 'lt', 'lte'];


	
	static $Client 		= null;


	public function __construct($index, $type){
		$this->index = $index;
		$this->type  = $type;

		if (empty($this->index) || empty($this->type)) {
			throw new Exception("Please provide index and type");
		}

		if (empty($this->Client)) {
			try {
                static::$Client = new Client(["hosts"=>["10.0.20.164:9202"]]);
            } catch (Exception $e) {
                throw new Exception("Could not connect to elasticsearch server reason : ". $e->getMessage());
            }
		}

		return $this;

	}

	public function where($field, $operand, $value = ""){
		if (func_num_args() == 2) {

			$this->must[]['term'][$field] = $operand;
			return $this;
		}

		if(!in_array($operand, $this->list_operand)){
			throw new Exception("Operand is'nt right !");
		}

		switch ($operand) {
			case '=':
				$this->must[]['term'][$field] = $value;
				break;
			case 'lte':
			case 'lt':
			case 'gte':
			case 'gt':
				$this->range($field, $operand, $value);
				break;
			case '!=':
				$this->must_not[]['term'][$field] = $value;
				break;
			default:
				$this->must[]['term'][$field] = $value;
				break;
		}
		return $this;
	}

	public function range($field, $operand, $value){
		$this->must[]['range'][$field][$operand] = $value;
		return $this;
	}


	public function whereNotIn($field, $values){
		if (!is_array($values)) {
			throw new Exception("Value must be array", 1);
		}

		$this->must_not[]['terms'][$field] = $values;
		return $this;

	}

	public function whereIn($field, $values){
		if (!is_array($values)) {
			throw new Exception("Value must be array", 1);
		}

		$this->must[]['terms'][$field] = $values;
		return $this;
	}


	public function whereNot($field, $value){
		$this->must_not[]['term'][$field] = $value;
		return $this;
	}
	public function orderBy($field, $direction = 'DESC'){
		$this->_sort[$field] = $direction;
		return $this;
	}
	public function groupBy($field){
		$this->_groupBy[$field]['terms']['field'] = $field;
		$this->_groupBy[$field]['terms']['size']  = 100;
		return $this;
	}


	private function QueryBuilder(){
		$this->DSLQuery = [
			"index"	=> $this->index,
			"type"	=> $this->type,
			"size" 	=> $this->size,
			"from"	=> $this->from,
			"body"	=> [

			]
		];

		if (!empty($this->must)) {
			$this->DSLQuery['body']['query']['filtered']['filter']['bool']['must'] = $this->must;
		}

		if (!empty($this->must_not)) {
			$this->DSLQuery['body']['query']['filtered']['filter']['bool']['must_not'] = $this->must_not;
		}

		if (!empty($this->should)) {
			$this->DSLQuery['body']['query']['filtered']['filter']['bool']['should'] = $this->should;
		}

		if(!empty($this->_sort)){
			$this->DSLQuery['body']['sort'] = $this->_sort;
		}

		if(!empty($this->_groupBy)){
			$this->DSLQuery['size'] = 0;
			$this->DSLQuery['body']['aggs'] = $this->_groupBy;
		}

		if (!empty($fields)) {
			$this->DSLQuery['body']['fields'] = $this->fields;
		}
		return $this->DSLQuery;
	}

	private function paserResult(){
		$Data = [];

		$this->TotalRecord =  $this->RawResult['hits']['total'];

		if (!empty($this->_groupBy)) {
			foreach ($this->RawResult['aggregations'] as $key => $value) {
				$Data[$key] = $value['buckets'];
			}

			return $Data;
		}


		if ($this->TotalRecord == 0) {
			return [];
		}

        $hits 	= $this->RawResult['hits']['hits'];

        foreach ($hits as $key => $value) {
            $Data[] = $value['_source'];
        }
        return $Data;
	}

	public function skip($number){
		$this->from = $number;
		return $this;
	}
	public function take($number){
		$this->size = $number;
		return $this;
	}

	public function get($fields = []){
		$this->fields = $fields;
		try {
			$this->RawResult = static::$Client->search($this->QueryBuilder());
		} catch (Exception $e) {
			throw new Exception("Wrong DSL Query : ". $e->getMessage());
		}
		return $this->paserResult();
	}
	//$size : Custome size 
	public function lists($field, $size = 5001){
		$this->size = $size;
		$Data  = $this->get();
		$Lists = [];

		foreach ($Data as $key => $value) {
			if (!isset($value[$field])) {
				throw new Exception("Can't not find field ". $field." in result");
			}
			$Lists[] = $value[$field];
		}
		return $Lists;
	}
	public function count(){
		$this->get();
		return $this->TotalRecord;
	}



}
