<?php

namespace Weiwei2012holy\EolinkerDoc\Models;

use Illuminate\Database\Eloquent\Model;

class EoProject extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('eolinker.connection');
    }

    protected $table = 'eo_project';

    public $timestamps = false;

    protected $primaryKey = 'projectAD';
}
