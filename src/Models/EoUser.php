<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-06,19:36
 */

namespace Weiwei2012holy\EolinkerDoc\Models;


use Illuminate\Database\Eloquent\Model;

class EoUser extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->connection = config('eolinker.connection');
        parent::__construct($attributes);
    }

    protected $table = 'eo_user';

    protected $primaryKey = 'userID';
}