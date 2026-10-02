@component('mail::message')
# Xin chào {{ $name }},

Sweet Cake đã phản hồi tin nhắn bạn gửi với chủ đề:

**{{ $subject }}**

> {{ $content }}

**Phản hồi từ chúng tôi:**

{{ $reply }}

Cảm ơn bạn đã liên hệ với Sweet Cake!

Trân trọng,<br>
{{ config('app.name') }}
@endcomponent
