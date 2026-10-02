import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import axios from 'axios';
import MainLayout from '@/Layouts/MainLayout';

const emptyContact = { name: '', email: '', phone: '', subject: '', message: '' };
const emptyQuestion = { guest_name: '', guest_email: '', guest_phone: '', subject: '', content: '' };

function Field({ label, name, error, textarea, ...props }) {
    const Input = textarea ? 'textarea' : 'input';
    return (
        <div className="mb-3">
            <label htmlFor={name} className="form-label text-sm font-semibold">{label}</label>
            <Input id={name} name={name} className={`form-control ${error ? 'is-invalid' : ''}`} rows={textarea ? 4 : undefined} {...props} />
            {error && <div className="invalid-feedback">{error}</div>}
        </div>
    );
}

export default function Contact() {
    const { auth } = usePage().props;
    const [contact, setContact] = useState(emptyContact);
    const [contactErrors, setContactErrors] = useState({});
    const [contactStatus, setContactStatus] = useState(null);
    const [question, setQuestion] = useState(emptyQuestion);
    const [questionErrors, setQuestionErrors] = useState({});
    const [questionStatus, setQuestionStatus] = useState(null);

    const firstErrors = (errors = {}) =>
        Object.fromEntries(Object.entries(errors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));

    const submitContact = async (event) => {
        event.preventDefault();
        setContactErrors({});
        setContactStatus(null);
        try {
            const res = await axios.post('/api/customer/contact', contact);
            setContactStatus({ type: 'success', text: res.data.message });
            setContact(emptyContact);
        } catch (err) {
            setContactErrors(firstErrors(err.response?.data?.errors));
            setContactStatus({ type: 'danger', text: err.response?.status === 429 ? 'Bạn gửi quá nhiều lần, vui lòng thử lại sau ít phút.' : 'Vui lòng kiểm tra lại thông tin.' });
        }
    };

    const submitQuestion = async (event) => {
        event.preventDefault();
        setQuestionErrors({});
        setQuestionStatus(null);
        try {
            await axios.post('/api/messages', question);
            setQuestionStatus({ type: 'success', text: 'Tin nhắn của bạn đã được gửi thành công' });
            setQuestion(emptyQuestion);
        } catch (err) {
            setQuestionErrors(firstErrors(err.response?.data?.errors));
            setQuestionStatus({ type: 'danger', text: err.response?.status === 429 ? 'Bạn gửi quá nhiều lần, vui lòng thử lại sau ít phút.' : 'Vui lòng kiểm tra lại thông tin.' });
        }
    };

    const bindContact = (name) => ({ value: contact[name], onChange: (e) => setContact({ ...contact, [name]: e.target.value }), error: contactErrors[name] });
    const bindQuestion = (name) => ({ value: question[name], onChange: (e) => setQuestion({ ...question, [name]: e.target.value }), error: questionErrors[name] });

    return (
        <MainLayout>
            <Head title="Liên hệ" />
            <div className="container py-5">
                <h1 className="text-3xl font-black text-gray-900 mb-2">Liên hệ với chúng tôi</h1>
                <p className="text-gray-500 mb-5">
                    Sweet Bakery luôn sẵn sàng lắng nghe. Phản hồi của cửa hàng sẽ được gửi qua email
                    {auth.user && <> và hiển thị tại <Link href={route('messages.mine')}>Tin nhắn của tôi</Link></>}.
                </p>

                <div className="row g-4 g-lg-5">
                    <div className="col-lg-6">
                        <form onSubmit={submitContact} className="bg-white border rounded-2xl p-4" noValidate>
                            <h2 className="text-lg font-bold mb-3">Gửi liên hệ</h2>
                            {contactStatus && <div className={`alert alert-${contactStatus.type}`} role="alert">{contactStatus.text}</div>}
                            <Field label="Họ tên *" name="name" {...bindContact('name')} />
                            <Field label="Email *" name="email" type="email" {...bindContact('email')} />
                            <Field label="Số điện thoại *" name="phone" {...bindContact('phone')} />
                            <Field label="Chủ đề *" name="subject" {...bindContact('subject')} />
                            <Field label="Nội dung *" name="message" textarea {...bindContact('message')} />
                            <button type="submit" className="btn btn-dark rounded-pill px-4">Gửi liên hệ</button>
                        </form>
                    </div>

                    <div className="col-lg-6">
                        <form onSubmit={submitQuestion} className="bg-white border rounded-2xl p-4" noValidate>
                            <h2 className="text-lg font-bold mb-3">Hỏi đáp nhanh</h2>
                            {questionStatus && <div className={`alert alert-${questionStatus.type}`} role="alert">{questionStatus.text}</div>}
                            {!auth.user && (
                                <>
                                    <Field label="Họ tên *" name="guest_name" {...bindQuestion('guest_name')} />
                                    <Field label="Email *" name="guest_email" type="email" {...bindQuestion('guest_email')} />
                                    <Field label="Số điện thoại" name="guest_phone" {...bindQuestion('guest_phone')} />
                                </>
                            )}
                            <Field label="Chủ đề *" name="q_subject" {...bindQuestion('subject')} />
                            <Field label="Nội dung *" name="content" textarea {...bindQuestion('content')} />
                            <button type="submit" className="btn btn-pink rounded-pill px-4" style={{ background: '#db2777', color: '#fff' }}>Gửi câu hỏi</button>
                        </form>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}
