<?php
// app/Http/Controllers/Admin/ContactController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Danh sách liên hệ gửi qua form "Liên hệ với chúng tôi"
     */
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        if ($request->status && in_array($request->status, ['new', 'read', 'replied'], true)) {
            $query->where('status', $request->status);
        }

        $contacts = $query->orderByDesc('created_at')->paginate($request->per_page ?? 15);

        return Inertia::render('Admin/Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Chi tiết 1 liên hệ (tự đánh dấu đã đọc)
     */
    public function show($id)
    {
        $contact = ContactMessage::findOrFail($id);

        if ($contact->status === 'new') {
            $contact->update(['status' => 'read']);
        }

        return Inertia::render('Admin/Contacts/Detail', [
            'contact' => $contact,
        ]);
    }

    /**
     * Admin phản hồi liên hệ
     */
    public function reply(Request $request, $id)
    {
        $contact = ContactMessage::findOrFail($id);

        $request->validate([
            'admin_reply' => 'required|string',
        ]);

        $contact->update([
            'admin_reply' => $request->admin_reply,
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        \Illuminate\Support\Facades\Mail::to($contact->email)
            ->send(new \App\Mail\ContactRepliedMail($contact->fresh()));

        return back()->with('success', 'Đã phản hồi liên hệ!');
    }

    /**
     * Xoá 1 liên hệ
     */
    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'Đã xóa liên hệ!');
    }
}
