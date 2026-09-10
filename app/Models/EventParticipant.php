<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasRelatedOwnership;

class EventParticipant extends Model
{
    use HasFactory, HasRelatedOwnership;

    protected $fillable = [
        'event_id',
        'participant_id',
        'participant_type_id',
        'registered_via',
        'attendance_mode',
        'registered_at',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function qrToken()
    {
        return $this->hasOne(AttendanceQrToken::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function participantType()
    {
        return $this->belongsTo(ParticipantType::class);
    }

    public function certificateReports()
    {
        return $this->hasMany(CertificateReport::class);
    }
}

