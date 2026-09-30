import React, { useEffect, useMemo, useState } from 'react';
import { getDisplayData } from '../../services/api.js';
import './DigitalBoard.css';

const SLIDE_INTERVAL = 5_000;
const REFRESH_INTERVAL = 45_000;

function normalizeDisplayData(payload) {
    if (!payload || payload.success !== true || !payload.data) {
        throw new Error('Format data display tidak valid.');
    }

    const liveChannels = Array.isArray(payload.data.live_channels) ? payload.data.live_channels : [];

    return {
        promotions: Array.isArray(payload.data.promotions) ? payload.data.promotions : [],
        achievements: Array.isArray(payload.data.achievements) ? payload.data.achievements : [],
        live_channels: liveChannels
            .filter((channel) => Array.isArray(channel.slots) && channel.slots.length > 0)
            .map((channel) => ({ ...channel, slots: channel.slots.slice(0, 4) })),
        birthdays: Array.isArray(payload.data.birthdays) ? payload.data.birthdays : [],
        weekly_meetings: Array.isArray(payload.data.weekly_meetings) ? payload.data.weekly_meetings : [],
    };
}

function DisplayImage({ src, alt, className = '' }) {
    const [hasError, setHasError] = useState(false);

    useEffect(() => {
        setHasError(false);
    }, [src]);

    if (!src || hasError) {
        return (
            <div className={`display-image-fallback ${className}`} role="img" aria-label={alt}>
                <span aria-hidden="true">DI</span>
                <small>GAMBAR TIDAK TERSEDIA</small>
            </div>
        );
    }

    return <img className={className} src={src} alt={alt} onError={() => setHasError(true)} />;
}

function DisplayTopbar() {
    return <header className="display-topbar" aria-hidden="true" />;
}

function PromotionSlide({ promotion }) {
    const period = [promotion.start_date, promotion.end_date].filter(Boolean).join(' - ');

    return (
        <article className="display-slide promotion-slide">
            <div className="promotion-visual">
                {promotion.image_url && <DisplayImage className="promotion-image" src={promotion.image_url} alt={`Banner ${promotion.title}`} />}

            </div>

        </article>
    );
}

const CONFETTI_COLORS = ['#ffd166', '#ffffff', '#4f7cff', '#a855f7', '#ff9ecb', '#63e6be', '#b388ff'];
const CONFETTI_COUNT = 22;

function buildConfetti() {
    return Array.from({ length: CONFETTI_COUNT }, (_, i) => {
        const leftPct = ((i * 47) % 96) + 2;
        const size = 5 + ((i * 7) % 8);
        const delay = -((i * 173) % 320) / 100;
        const duration = 2.2 + ((i * 13) % 40) / 20;
        const rotate = (i * 61) % 560;
        const sway = ((i * 29) % 120) - 60;
        const shape = i % 3 === 0 ? 'round' : i % 3 === 1 ? 'bar' : 'square';

        return {
            leftPct,
            size,
            delay,
            duration,
            rotate,
            sway,
            shape,
            color: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
        };
    });
}

const CONFETTI = buildConfetti();

const FIREWORK_COLORS = ['#ffd166', '#7fd7ff', '#ff9ecb', '#b388ff', '#ffffff'];
const FIREWORK_COUNT = 3;
const FIREWORK_PARTICLES = 12;
const FIREWORK_DURATION = 3.6;

function buildFireworks() {
    return Array.from({ length: FIREWORK_COUNT }, (_, i) => {
        const particles = Array.from({ length: FIREWORK_PARTICLES }, (_, j) => {
            const angle = (j / FIREWORK_PARTICLES) * Math.PI * 2 + (i * 0.35);
            const dist = 55 + ((j * 41) % 90);

            return {
                tx: Math.round(Math.cos(angle) * dist * 10) / 10,
                ty: Math.round(Math.sin(angle) * dist * 10) / 10,
                rot: (j * 47) % 360,
            };
        });

        return {
            x: 16 + i * 32 + ((i * 13) % 8),
            delay: (i * FIREWORK_DURATION) / FIREWORK_COUNT,
            color: FIREWORK_COLORS[i % FIREWORK_COLORS.length],
            particles,
        };
    });
}

const FIREWORKS = buildFireworks();

function ConfettiRain() {
    return (
        <div className="confetti-rain" aria-hidden="true">
            {CONFETTI.map((piece, i) => (
                <span
                    key={i}
                    className={`confetti confetti-${piece.shape}`}
                    style={{
                        '--x': `${piece.leftPct}%`,
                        '--size': `${piece.size}px`,
                        '--delay': `${piece.delay}s`,
                        '--duration': `${piece.duration}s`,
                        '--rotate': `${piece.rotate}deg`,
                        '--sway': `${piece.sway}px`,
                        '--confetti-color': piece.color,
                    }}
                />
            ))}
        </div>
    );
}

function Fireworks() {
    return (
        <div className="fireworks" aria-hidden="true">
            {FIREWORKS.map((firework, i) => (
                <div
                    key={i}
                    className="firework"
                    style={{
                        '--x': `${firework.x}%`,
                        '--fw-delay': `${firework.delay}s`,
                        '--fw-color': firework.color,
                    }}
                >
                    <span className="firework-rocket" />
                    <span className="firework-flash" />
                    {firework.particles.map((particle, j) => (
                        <span
                            key={j}
                            className="firework-particle"
                            style={{ '--tx': `${particle.tx}px`, '--ty': `${particle.ty}px`, '--rot': `${particle.rot}deg` }}
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}

function AchievementSlide({ achievement }) {
    return (
        <article className="display-slide achievement-slide">
            <ConfettiRain />
            <div className="achievement-paper">
                <span className="achievement-paper-frame" aria-hidden="true" />
                <span className="achievement-corner achievement-corner-tl" aria-hidden="true" />
                <span className="achievement-corner achievement-corner-tr" aria-hidden="true" />
                <span className="achievement-corner achievement-corner-bl" aria-hidden="true" />
                <span className="achievement-corner achievement-corner-br" aria-hidden="true" />

                <header className="achievement-head">
                    <span className="achievement-rule" aria-hidden="true" />
                    <h2 className="achievement-kicker">Piagam Penghargaan</h2>
                    <span className="achievement-rule" aria-hidden="true" />
                </header>

                <div className="achievement-body">
                    {achievement.image_url ? (
                        <DisplayImage className="achievement-emblem" src={achievement.image_url} alt={`Penerima penghargaan ${achievement.employee_name}`} />
                    ) : (
                        <span className="achievement-emblem achievement-emblem-fallback" aria-hidden="true">DI</span>
                    )}
                    <p className="achievement-to">Diberikan kepada</p>
                    <h1 className="achievement-name">{achievement.employee_name}</h1>
                    <p className="achievement-title">{achievement.title}</p>
                    {achievement.division && <p className="achievement-division">Divisi {achievement.division}</p>}
                </div>
            </div>
        </article>
    );
}

function ChannelLogo({ channel }) {
    const [hasError, setHasError] = useState(false);

    useEffect(() => {
        setHasError(false);
    }, [channel.logo_url]);

    if (!channel.logo_url || hasError) {
        return <span className="channel-logo channel-logo-fallback" role="img" aria-label={`Logo ${channel.name}`} aria-hidden="true" />;
    }

    return <img className="channel-logo" src={channel.logo_url} alt={`Logo ${channel.name}`} onError={() => setHasError(true)} />;
}

function HostPhoto({ host }) {
    const [hasError, setHasError] = useState(false);
    const initials = (host.host_name || '').trim().split(/\s+/).map((word) => word[0] || '').slice(0, 2).join('').toUpperCase();

    useEffect(() => {
        setHasError(false);
    }, [host.host_photo]);

    if (!host.host_photo || hasError) {
        return <span className="host-photo host-photo-fallback" role="img" aria-label={`Foto ${host.host_name || 'host'}`}>{initials || '–'}</span>;
    }

    return <img className="host-photo" src={host.host_photo} alt={`Foto ${host.host_name}`} onError={() => setHasError(true)} />;
}

function LiveHostsSlide({ channel }) {
    return (
        <article className="display-slide live-slide">
            <header className="slide-heading live-heading">
                <div className="live-brand">
                    <ChannelLogo channel={channel} />
                    <span className="channel-name">{channel.name}</span>
                </div>
                <span className="slide-kicker">JADWAL HOST LIVE</span>
            </header>
            <div className="host-grid">
                {channel.slots.map((slot) => (
                    <div key={slot.id} className={slot.host_name ? 'host-card' : 'host-card is-unassigned'}>
                        <div className="host-card-photo"><HostPhoto host={slot} /></div>
                        <div className="host-card-meta">
                            <p className="host-card-name">{slot.host_name || 'Belum ditentukan'}</p>
                            <time className="host-card-time">{slot.start_time} – {slot.end_time}</time>
                        </div>
                    </div>
                ))}
            </div>
        </article>
    );
}

function MeetingPhoto({ meeting }) {
    const [hasError, setHasError] = useState(false);
    const initials = (meeting.name || '').trim().split(/\s+/).map((word) => word[0] || '').slice(0, 2).join('').toUpperCase();

    useEffect(() => {
        setHasError(false);
    }, [meeting.photo_url]);

    if (!meeting.photo_url || hasError) {
        return <span className="meeting-photo meeting-photo-fallback" role="img" aria-label={`Foto ${meeting.name || 'anggota'}`}>{initials || '–'}</span>;
    }

    return <img className="meeting-photo" src={meeting.photo_url} alt={`Foto ${meeting.name}`} onError={() => setHasError(true)} />;
}

function WeeklyMeetingSlide({ meetings }) {
    return (
        <article className="display-slide meeting-slide">
            <header className="meeting-head">
                <span className="slide-kicker">WEEKLY MEETING</span>
                <h1>Jadwal Presentasi Weekly Meeting</h1>
                <p className="meeting-day">Selasa Berikutnya</p>
            </header>
            <div className="meeting-row">
                {meetings.map((meeting) => (
                    <div key={meeting.id} className="meeting-card">
                        <MeetingPhoto meeting={meeting} />
                        <p className="meeting-name">{meeting.name}</p>
                    </div>
                ))}
            </div>
        </article>
    );
}

function BirthdaySlide({ birthday }) {
    const dateLabel = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long' }).format(new Date(`${birthday.birth_date}T00:00:00`));

    return (
<article className="display-slide birthday-slide">
            <div className="birthday-orbit birthday-orbit-one" aria-hidden="true" />
            <div className="birthday-orbit birthday-orbit-two" aria-hidden="true" />
            <ConfettiRain />
            <Fireworks />
            <span className="slide-kicker">HARI ULANG TAHUN</span>
            <p className="birthday-heading">Selamat ulang tahun</p>
            <DisplayImage className="birthday-photo" src={birthday.image_url} alt={`Foto ${birthday.employee_name}`} />
            <h1>{birthday.employee_name}</h1>
            <p className="birthday-division">{birthday.division ? `Divisi ${birthday.division}` : ''}</p>
            <time className="birthday-date">{dateLabel}</time>
        </article>
    );
}

function LoadingBoard() {
    return <div className="board-message"><span className="board-message-mark">DI</span><p>Memuat informasi...</p><div className="loading-line" /></div>;
}

function EmptyBoard({ hasError }) {
    return (
        <div className="board-message">
            <span className="board-message-mark">DI</span>
            <h1>Digital Information Board</h1>
            <p>{hasError ? 'Informasi sedang diperbarui. Sistem akan mencoba kembali.' : 'Belum ada informasi untuk ditampilkan.'}</p>
        </div>
    );
}

export default function DigitalBoard() {
    const [data, setData] = useState(null);
    const [isLoading, setIsLoading] = useState(true);
    const [hasError, setHasError] = useState(false);
    const [slideIndex, setSlideIndex] = useState(0);
    const [isFullscreen, setIsFullscreen] = useState(Boolean(document.fullscreenElement));
    const [fullscreenError, setFullscreenError] = useState(false);


    useEffect(() => {
        document.body.classList.add('display-mode');
        let isMounted = true;
        let pollTimeout;
        let activeController;

        async function refreshData() {
            activeController = new AbortController();
            try {
                const payload = await getDisplayData({ signal: activeController.signal });
                if (isMounted) {
                    setData(normalizeDisplayData(payload));
                    setHasError(false);
                }
            } catch (error) {
                if (isMounted && error.name !== 'AbortError') setHasError(true);
            } finally {
                if (isMounted) {
                    setIsLoading(false);
                    pollTimeout = window.setTimeout(refreshData, REFRESH_INTERVAL);
                }
            }
        }

        refreshData();

        return () => {
            isMounted = false;
            activeController?.abort();
            window.clearTimeout(pollTimeout);
            document.body.classList.remove('display-mode');
        };
    }, []);

    const categories = useMemo(() => {
        if (!data) return [];
        return [
            { key: 'promotions', label: 'Promosi', items: data.promotions },
            { key: 'achievements', label: 'Achievement', items: data.achievements },
            { key: 'birthdays', label: 'Birthday', items: data.birthdays },
            { key: 'weekly_meetings', label: 'Weekly Meeting', items: data.weekly_meetings.length ? [data.weekly_meetings] : [] },
            { key: 'live_hosts', label: 'Jadwal Host Live', items: data.live_channels },
        ].filter((category) => category.items.length > 0);
    }, [data]);

    const slides = useMemo(
        () => categories.flatMap((category) => category.items.map((item) => ({ categoryKey: category.key, item }))),
        [categories],
    );

    useEffect(() => {
        if (slides.length <= 1) return undefined;

        const slideTimer = window.setInterval(() => {
            setSlideIndex((previous) => (previous + 1) % slides.length);
        }, SLIDE_INTERVAL);

        return () => window.clearInterval(slideTimer);
    }, [slides]);


    useEffect(() => {
        function syncFullscreen() {
            setIsFullscreen(Boolean(document.fullscreenElement));
        }

        document.addEventListener('fullscreenchange', syncFullscreen);
        return () => document.removeEventListener('fullscreenchange', syncFullscreen);
    }, []);

    async function toggleFullscreen() {
        if (!document.fullscreenEnabled) return;
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await document.querySelector('.display-shell')?.requestFullscreen();
            }
            setFullscreenError(false);
        } catch {
            setFullscreenError(true);
        }
    }
    const fullscreenErrorNotice = fullscreenError ? (<div className="display-error" role="status">Mode layar penuh tidak dapat diaktifkan.</div>) : null;

    if (isLoading && !data) {
        return <main className="display-shell"><DisplayTopbar /><LoadingBoard />{fullscreenErrorNotice}{!isFullscreen && document.fullscreenEnabled && <button className="fullscreen-control" type="button" onClick={toggleFullscreen} aria-label="Tampilkan layar penuh">FULLSCREEN</button>}</main>;
    }

    if (categories.length === 0) {
        return <main className="display-shell"><DisplayTopbar />{hasError && <div className="display-error">Informasi sedang diperbarui</div>}<EmptyBoard hasError={hasError} />{fullscreenErrorNotice}{!isFullscreen && document.fullscreenEnabled && <button className="fullscreen-control" type="button" onClick={toggleFullscreen} aria-label="Tampilkan layar penuh">FULLSCREEN</button>}</main>;
    }

const activeSlide = slides[slideIndex % slides.length];

    return (
        <main className="display-shell">
            <DisplayTopbar />
            {hasError && <div className="display-error" role="status">Informasi sedang diperbarui</div>}
            <div className="display-stage">
                {activeSlide.categoryKey === 'promotions' && <PromotionSlide key={`promotion-${activeSlide.item.id}`} promotion={activeSlide.item} />}
                {activeSlide.categoryKey === 'achievements' && <AchievementSlide key={`achievement-${activeSlide.item.id}`} achievement={activeSlide.item} />}
                {activeSlide.categoryKey === 'live_hosts' && <LiveHostsSlide key={`channel-${activeSlide.item.id}`} channel={activeSlide.item} />}
                {activeSlide.categoryKey === 'birthdays' && <BirthdaySlide key={`birthday-${activeSlide.item.id}`} birthday={activeSlide.item} />}
                {activeSlide.categoryKey === 'weekly_meetings' && <WeeklyMeetingSlide key="weekly-meeting" meetings={activeSlide.item} />}
            </div>
            <footer className="display-footer" aria-hidden="true" />
            {fullscreenErrorNotice}
            {!isFullscreen && document.fullscreenEnabled && <button className="fullscreen-control" type="button" onClick={toggleFullscreen} aria-label="Tampilkan layar penuh">FULLSCREEN</button>}
            
        </main>
    );
}
