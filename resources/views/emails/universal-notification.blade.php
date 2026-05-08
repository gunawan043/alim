<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: {{ $notification->priority == 'urgent' ? '#dc3545' : ($notification->priority == 'high' ? '#fd7e14' : '#007bff') }};
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            background: #e9ecef;
            border-radius: 3px;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $notification->title }}</h2>
            <p style="margin:0; opacity:0.9;">Module: {{ ucfirst($notification->module) }}</p>
        </div>
        
        <div class="content">
            <p><strong>Halo {{ $user->name }},</strong></p>
            
            <p>{{ $notification->message }}</p>
            
            @if($notification->reference_code)
                <p>
                    <span class="badge">
                        {{ $notification->reference_code }}
                    </span>
                </p>
            @endif
            
            @if($notification->action_url)
                <a href="{{ $notification->action_url }}" class="button">
                    {{ $notification->action_text }}
                </a>
            @endif
            
            <div class="footer">
                <p>Dikirim: {{ $notification->created_at->format('d M Y H:i') }}</p>
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>