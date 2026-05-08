<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header {
            background: {{ $isOverdue ? '#dc3545' : '#0ab39c' }};
            color: white; padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 5px 5px; }
        .badge { display: inline-block; padding: 3px 7px; border-radius: 3px; font-size: 12px; }
        .badge-priority { background: {{ $todo->priority === 'mendesak' ? '#dc3545' : ($todo->priority === 'tinggi' ? '#fd7e14' : '#17a2b8') }}; color: white; }
        .badge-status { background: #6c757d; color: white; }
        .badge-overdue { background: #dc3545; color: white; }
        .todo-item { background: white; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .todo-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .todo-meta { font-size: 13px; color: #6c757d; margin: 5px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #0ab39c; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .footer { margin-top: 20px; font-size: 12px; color: #6c757d; text-align: center; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">{{ $isOverdue ? '⚠️ Tugas Terlambat' : '⏰ Reminder Todo' }}</h2>
            <p style="margin:5px 0 0; opacity:0.9;">{{ config('app.name') }} — Todo List</p>
        </div>

        <div class="content">
            <p><strong>Halo {{ $owner->name }},</strong></p>

            @if($isOverdue)
                <div class="warning">
                    <strong>⚠️ Perhatian:</strong> Tugas ini sudah melewati tenggat waktu yang ditentukan.
                </div>
            @endif

            <div class="todo-item">
                <div class="todo-title">{{ $todo->title }}</div>
                <div class="todo-meta">
                    <span class="badge badge-priority">Prioritas: {{ $priority }}</span>
                    <span class="badge badge-status">Status: {{ $todo->statusLabel }}</span>
                    @if($isOverdue)
                        <span class="badge badge-overdue">TERLAMBAT</span>
                    @endif
                </div>
                <div class="todo-meta" style="margin-top:8px;">
                    📅 Tenggat: <strong>{{ $dueText }}</strong>
                </div>
                @if($todo->description)
                    <div class="todo-meta" style="margin-top:8px;">
                        📝 {{ Str::limit(strip_tags($todo->description), 150) }}
                    </div>
                @endif
                @if($todo->delegatedByUser)
                    <div class="todo-meta">
                        👤 Didelegasikan oleh: {{ $todo->delegatedByUser->name }}
                    </div>
                @endif
                @if($todo->subtasks->count() > 0)
                    <div class="todo-meta">
                        📋 Subtask: {{ $todo->subtasks->where('is_completed', 1)->count() }}/{{ $todo->subtasks->count() }} selesai
                        ({{ $todo->progress_percent }}%)
                    </div>
                @endif
            </div>

            <p>Segera buka aplikasi untuk melihat detail dan mengambil tindakan yang diperlukan.</p>

            <a href="{{ $url }}" class="button">Buka Todo</a>

            <div class="footer">
                <p>Dikirim: {{ now()->format('d M Y H:i') }} WIB</p>
                <p>Email ini dikirim otomatis oleh sistem {{ config('app.name') }}. Jangan balas email ini.</p>
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
