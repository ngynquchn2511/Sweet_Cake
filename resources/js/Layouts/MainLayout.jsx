import React from 'react';
import Navbar from '../Components/Navbar';
import Footer from '../Components/Footer'; // Import Footer vào đây

export default function MainLayout({ children }) {
    return (
        <div className="d-flex flex-column min-vh-100">
            <Navbar />
            
            {/* Nội dung trang */}
            <main className="flex-grow-1">
                {children}
            </main>

            <Footer /> {/* Gọi Footer ở cuối */}
        </div>
    );
}