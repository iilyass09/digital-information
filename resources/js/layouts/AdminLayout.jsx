import React from 'react';

const groups = [
    {
        label: 'RINGKASAN',
        links: [
            ['Dashboard', '/admin'],
            ['Preview Display', '/admin/display-preview'],
        ],
    },
    {
        label: 'KONTEN BOARD',
        links: [
            ['Info Promosi', '/admin/promotions'],
            ['Achievement', '/admin/achievements'],
            ['Birthday', '/admin/birthdays'],
            ['Weekly Meeting', '/admin/weekly-meetings'],
        ],
    },
    {
        label: 'LIVE STREAMING',
        links: [
            ['Channel Live', '/admin/channels'],
            ['Daftar Host', '/admin/hosts'],
            ['Jadwal Host Live', '/admin/live-hosts'],
        ],
    },
];

export default function AdminLayout({ title, children }) {
    const currentPath = window.location.pathname;
    const isActive = (href) => (href === '/admin' ? currentPath === '/admin' : currentPath.startsWith(href));

    return (
        <div className="cms-shell">
            <aside className="cms-sidebar">
                <a className="cms-brand" href="/admin"><span className="cms-brand-mark">D</span><span>Digital Board<small>ADMIN CMS</small></span></a>
                <nav aria-label="Navigasi admin">
                    {groups.map((group) => (
                        <div className="nav-group" key={group.label}>
                            <p className="nav-label">{group.label}</p>
                            {group.links.map(([label, href]) => (
                                <a key={href} className={`nav-link ${isActive(href) ? 'is-current' : ''}`} href={href}>{label}</a>
                            ))}
                        </div>
                    ))}
                </nav>
                <form className="logout-form" method="post" action="/admin/logout"><input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]').content} /><button className="nav-link logout-button" type="submit">↪ Keluar</button></form>
            </aside>
            <main className="cms-main">
                <header className="cms-topbar">
                    <button className="mobile-menu" type="button" onClick={() => document.body.classList.toggle('nav-open')} aria-label="Buka navigasi">☰</button>
                    <div><p className="eyebrow">ADMINISTRATION</p><h1>{title}</h1></div>
                    <span className="admin-chip">Administrator</span>
                </header>
                <div className="cms-content">{children}</div>
            </main>
        </div>
    );
}
