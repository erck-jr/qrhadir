<?php

namespace App\Exports;

use App\Models\Event;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $event;
    protected $dates = [];

    public function __construct(Event $event)
    {
        $this->event = $event;

        $start = \Carbon\Carbon::parse($event->start_date);
        $end = \Carbon\Carbon::parse($event->end_date);
        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $this->dates[] = $date->format('Y-m-d');
        }
    }

    public function query()
    {
        return $this->event->eventParticipants()
            ->join('participant_types', 'event_participants.participant_type_id', '=', 'participant_types.id')
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->with([
                'participant' => function($q) { $q->withoutGlobalScope('ownership'); },
                'attendances',
                'participantType' => function($q) { $q->withoutGlobalScope('ownership'); }
            ])
            ->orderBy('participant_types.id', 'asc')
            ->orderBy('participants.name', 'asc')
            ->select('event_participants.*');
    }

    public function headings(): array
    {
        $headers = [
            'Nama Peserta',
            'Tipe',
            'Email',
            'Telepon'
        ];

        if ($this->event->type === 'hybrid') {
            $headers[] = 'Mode Kehadiran';
        }

        foreach ($this->dates as $date) {
            $headers[] = 'Kehadiran ' . $date;
        }

        return [
            ['Laporan Kehadiran: ' . $this->event->name],
            ['Periode: ' . $this->event->start_date->format('d M Y') . ' - ' . $this->event->end_date->format('d M Y')],
            $headers
        ];
    }

    public function map($eventParticipant): array
    {
        $row = [
            $eventParticipant->participant?->name ?? '-',
            $eventParticipant->participantType?->name ?? '-',
            $eventParticipant->participant?->email ?? '-',
            $eventParticipant->participant?->phone ?? '-'
        ];

        if ($this->event->type === 'hybrid') {
            $row[] = $eventParticipant->attendance_mode ? ucfirst($eventParticipant->attendance_mode) : '-';
        }

        foreach ($this->dates as $date) {
            $attendance = $eventParticipant->attendances->first(function ($att) use ($date) {
                return \Carbon\Carbon::parse($att->checkin_time)->format('Y-m-d') === $date;
            });

            $row[] = $attendance ? \Carbon\Carbon::parse($attendance->checkin_time)->format('H:i:s') : 'Tidak Hadir';
        }

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true, 'size' => 14]],
            2    => ['font' => ['italic' => true, 'size' => 11]],
            3    => ['font' => ['bold' => true]],
        ];
    }
}
