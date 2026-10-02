@component('mail::message')
# Xin chào {{ $name }},

Sweet Cake đã phản hồi liên hệ bạn gửi với chủ đề:

**{{ $subject }}**

> {{ $message }}

**Phản hồi từ chúng tôi:**

{{ $reply }}

Cảm ơn bạn đã liên hệ với Sweet Cake!

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
