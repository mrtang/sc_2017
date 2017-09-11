<?php namespace sellermodel;

use Eloquent;
class UserInventoryModel extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table            = 'user_inventory' ;
    protected $connection       = 'metadb';
    protected $guarded          = array();
    public    $timestamps       = false;

    public function City(){
        return $this->belongsTo('CityGlobalModel','city_id');
    }
    
    public function district(){
        return $this->belongsTo('DistrictModel','province_id');
    }
    
    public function country(){
        return $this->belongsTo('CountryModel','country_id');
    }

    public function ward(){
        return $this->belongsTo('WardModel','ward_id');
    }

    public function pipe_journey(){
        return $this->hasMany('omsmodel\PipeJourneyModel','tracking_code')->where('type',3);
    }

    public function user(){
        return $this->belongsTo('User','user_id')->select(['id','fullname','email','phone']);
    }

    public function get_by_id($id, $active, $delete){
        if(isset($active)){
            self::where('active',$active);
        }

        if(isset($delete)){
            self::where('delete',$delete);
        }

        return self::where('id',$id)->remember(60)->first(['id','user_name','phone','city_id','province_id','ward_id','address']);
    }
}
