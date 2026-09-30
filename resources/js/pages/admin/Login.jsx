import React, { useState } from 'react';

const iconProps = {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.6,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': 'true',
    focusable: 'false',
};

const UserIcon = () => (
    <svg {...iconProps}>
        <path d="M20 21v-1.8a4.2 4.2 0 0 0-4.2-4.2H8.2A4.2 4.2 0 0 0 4 19.2V21" />
        <circle cx="12" cy="7.2" r="3.8" />
    </svg>
);

const LockIcon = () => (
    <svg {...iconProps}>
        <rect x="4" y="10.5" width="16" height="10.5" rx="2.6" />
        <path d="M8 10.5V7.6a4 4 0 0 1 8 0v2.9" />
    </svg>
);

const EyeIcon = () => (
    <svg {...iconProps}>
        <path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12Z" />
        <circle cx="12" cy="12" r="3" />
    </svg>
);

const EyeOffIcon = () => (
    <svg {...iconProps}>
        <path d="M3 3.2 21 20.8" />
        <path d="M10.6 6.1A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a17.4 17.4 0 0 1-3.3 4" />
        <path d="M6.5 7.9A16.9 16.9 0 0 0 2.5 12S6 18 12 18a9.5 9.5 0 0 0 3.6-.7" />
        <path d="M9.9 10.2a3 3 0 0 0 4 4.1" />
    </svg>
);

const LoginIcon = () => (
    <svg {...iconProps}>
        <path d="M15.5 3.5H18a2.5 2.5 0 0 1 2.5 2.5v12a2.5 2.5 0 0 1-2.5 2.5h-2.5" />
        <path d="M10.5 16.5 15 12l-4.5-4.5" />
        <path d="M15 12H3.5" />
    </svg>
);

export default function AdminLogin({ errors = {}, oldUsername = '' }) {
    const messages = Object.values(errors).flat();
    const [showPassword, setShowPassword] = useState(false);
    const describedBy = messages.length > 0 ? 'login-errors' : undefined;

    return (
        <main className="auth-shell">
            <section className="auth-card">
                <span className="auth-logo" aria-hidden="true">D</span>
                <h1 className="auth-title">Admin Panel</h1>
                <p className="auth-subtitle">PT. Johen Sukses Abadi — Masuk untuk melanjutkan</p>
                {messages.length > 0 && (
                    <div className="form-errors" id="login-errors" role="alert">
                        <p className="form-errors-head"><span aria-hidden="true">⚠</span>Gagal masuk</p>
                        {messages.map((message) => <p key={message}>{message}</p>)}
                    </div>
                )}
                <form method="post" action="/admin/login" className="auth-form">
                    <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]').content} />
                    <div className="auth-field">
                        <label className="auth-label" htmlFor="username">Username</label>
                        <div className="auth-control">
                            <UserIcon />
                            <input
                                id="username"
                                type="text"
                                name="username"
                                autoComplete="username"
                                defaultValue={oldUsername}
                                placeholder="Masukkan username admin"
                                aria-invalid={Boolean(errors.username)}
                                aria-describedby={describedBy}
                                required
                            />
                        </div>
                    </div>
                    <div className="auth-field">
                        <label className="auth-label" htmlFor="password">Password</label>
                        <div className="auth-control">
                            <LockIcon />
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                autoComplete="current-password"
                                placeholder="Masukkan password"
                                aria-invalid={Boolean(errors.password)}
                                aria-describedby={describedBy}
                                required
                            />
                            <button
                                className="auth-toggle"
                                type="button"
                                onClick={() => setShowPassword((visible) => !visible)}
                                aria-pressed={showPassword}
                                aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                            >
                                {showPassword ? <EyeOffIcon /> : <EyeIcon />}
                            </button>
                        </div>
                    </div>
                    <button className="auth-submit" type="submit">
                        <LoginIcon />
                        Masuk
                    </button>
                </form>
                <a className="auth-back" href="/">← Kembali ke Website</a>
            </section>
        </main>
    );
}