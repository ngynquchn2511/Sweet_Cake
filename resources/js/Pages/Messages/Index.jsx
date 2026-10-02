import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '@/Layouts/MainLayout';
import { formatDateTime } from '@/utils/format';

const statusLabel = {
    unread: { text: 'Chờ phản hồi', cls: 'bg-secondary' },
    new: { text: 'Chờ phản hồi', cls: 'bg-secondary' },
    read: { text: 'Đã xem', cls: 'bg-info' },
    replied: { text: 'Đã phản hồi', cls: 'bg-success' },
};

function Thread({ subject, body, createdAt, status, reply, repliedAt }) {
    const label = statusLabel[status] || statusLabel.unread;
    return (
        <div className="bg-white border rounded-2xl p-4">
            <div className="d-flex justify-content-between align-items-start mb-2">
                <strong>{subject}</strong>
                <span className={`badge ${label.cls}`}>{label.text}</span>
            </div>
            <p className="text-gray-700 mb-1" style={{ whiteSpace: 'pre-line' }}>{body}</p>
            <span className="text-xs text-gray-400">Gửi lúc {formatDateTime(createdAt)}</span>
            {reply && (
                <div className="mt-3 p-3 rounded-xl bg-pink-50 border border-pink-100">
                    <div className="text-xs font-bold text-pink-600 mb-1">
                        Phản hồi từ Sweet Bakery{repliedAt ? ` · ${formatDateTime(repliedAt)}` : ''}
                    </div>
                    <p className="mb-0 text-gray-800" style={{ whiteSpace: 'pre-line' }}>{reply}</p>
                </div>
            )}
        </div>
    );
}

export default function MessagesIndex({ messages, contacts = [] }) {
    const items = messages?.data || [];

    return (
        <MainLayout>
            <Head title="Tin nhắn của tôi" />
            <div className="container py-5">
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <h1 className="text-3xl font-black text-gray-900 mb-0">Tin nhắn của tôi</h1>
                    <Link href={route('contact')} className="btn btn-dark rounded-pill px-4">Gửi câu hỏi mới</Link>
                </div>

                <h2 className="text-lg font-bold mb-3">Hỏi đáp ({items.length})</h2>
                {items.length === 0 ? (
                    <p className="text-gray-500 mb-5">Bạn chưa gửi câu hỏi nào.</p>
                ) : (
                    <div className="d-flex flex-column gap-3 mb-5">
                        {items.map((m) => (
                            <Thread key={`m-${m.id}`} subject={m.subject} body={m.content} createdAt={m.created_at} status={m.status} reply={m.admin_reply} repliedAt={m.status === 'replied' ? m.updated_at : null} />
                        ))}
                    </div>
                )}

                <h2 className="text-lg font-bold mb-3">Lịch sử liên hệ ({contacts.length})</h2>
                {contacts.length === 0 ? (
                    <p className="text-gray-500">Chưa có liên hệ nào gửi từ email của bạn.</p>
                ) : (
                    <div className="d-flex flex-column gap-3">
                        {contacts.map((c) => (
                            <Thread key={`c-${c.id}`} subject={c.subject} body={c.message} createdAt={c.created_at} status={c.status} reply={c.admin_reply} repliedAt={c.replied_at} />
                        ))}
                    </div>
                )}
            </div>
        </MainLayout>
    );
}
