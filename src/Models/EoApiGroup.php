<?php

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Database\Eloquent\Model;

class EoApiGroup extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->connection = config('eolinker.connection');
        parent::__construct($attributes);
    }

    protected $table = 'eo_api_group';

    public $timestamps = false;

    protected $primaryKey = 'groupID';

    public $guarded = [];
}
