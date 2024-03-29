<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IvrMenus extends Model
{
    use HasFactory, \App\Models\Traits\TraitUuid;

    protected $table = "v_ivr_menus";

    public $timestamps = false;

    protected $primaryKey = 'ivr_menu_uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     *
    protected $fillable = [

    ];*/

    /*
    public function __construct(array $attributes = [])
    {
        parent::__construct();
        $this->attributes['domain_uuid'] = Session::get('domain_uuid');
        $this->attributes['insert_date'] = date('Y-m-d H:i:s');
        $this->attributes['insert_user'] = Session::get('user_uuid');
        $this->fill($attributes);
    }*/

    public function getId()
    {
        return $this->ivr_menu_extension;
    }

    public function getName()
    {
        return $this->ivr_menu_extension.' - '.$this->ivr_menu_name;
    }
}
