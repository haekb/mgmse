<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Game;

/**
 * Message of the day! Or probably month..or year depending on how frequently it gets updated..
 * This class uses the database.
 * Class Motd
 * @property string $content
 * @package App\Models
 * @mixin \Eloquent
 */
class Motd extends Model
{
    protected $table = 'motd';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
    ];

    public function Game(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Game::class);
    }

}
