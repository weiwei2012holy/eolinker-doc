<?php

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Database\Eloquent\Model;

class EoProject extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->connection = config('eolinker.connection');
        parent::__construct($attributes);
    }

    protected $table = 'eo_project';

    public $timestamps = false;

    protected $primaryKey = 'projectAD';
}
