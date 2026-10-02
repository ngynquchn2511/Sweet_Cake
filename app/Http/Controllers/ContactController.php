<?php
// app/Http/Controllers/ContactController.php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Store contact message (public)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:15',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        $contact = ContactMessage::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'new',
        ]);

        // TODO: Send notification email to admin
        // Mail::to(config('mail.admin_email'))->send(new ContactMessageNotification($contact));

        return response()->json([
            'success' => true,
            'message' => 'Tin nhắn của bạn đã được gửi thành công. Chúng tôi sẽ phản hồi sớm nhất.',
            'data' => $contact
        ], 201);
    }
}