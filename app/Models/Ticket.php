<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = ['user_id', 'subject', 'status', 'ticket_id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            do {
                $ticketId = 'pt-' . mt_rand(10000000, 99999999);
            } while (self::where('ticket_id', $ticketId)->exists());

            $ticket->ticket_id = $ticketId;
        });
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
