{{--
    FT-005's email. Every word in it comes from OverdueNotificationMessages, which
    holds the Content & Microcopy table one constant per row.

    The charset is declared because a task title is reproduced exactly as it was
    entered, in whatever script that was (EC-15, A-2).
--}}
@use(App\Constants\Notifications\OverdueNotificationMessages as Copy)

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ Copy::subject($digest->totalCount) }}</title>
</head>
<body style="margin:0;padding:24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1f2933;">
    <p>{{ Copy::greeting($recipientName) }}</p>

    <p>{{ Copy::INTRO }}</p>

    <ul style="padding-left:20px;">
        @foreach ($digest->lines as $line)
            <li style="margin-bottom:6px;">{{ Copy::line($line) }}</li>
        @endforeach
    </ul>

    @if ($digest->hasRemaining())
        <p>{{ Copy::more($digest->remainingCount()) }}</p>
    @endif

    <p style="margin-top:32px;color:#616e7c;font-size:13px;">{{ Copy::FOOTER }}</p>
</body>
</html>
