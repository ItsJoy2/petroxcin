<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_message_id', 'file_path'];

    public function message()
    {
        return $this->belongsTo(TicketMessage::class);
    }
}
