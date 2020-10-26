<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-06,19:36
 */

namespace Weiwei2012holy\EolinkerDoc\Models;


use Illuminate\Database\Eloquent\Model;

class EoProjectStatusCodeGroup extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = config('eolinker.connection');
    }
    public $timestamps = false;

    protected $table = 'eo_project_status_code_group';

    protected $primaryKey = 'groupID';

    public $guarded = [];
}