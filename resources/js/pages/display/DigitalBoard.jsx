import React, { useEffect, useMemo, useState } from 'react';
import QRCode from 'qrcode';
import { getDisplayData } from '../../services/api.js';
import './DigitalBoard.css';

const SLIDE_INTERVAL = 5_000;
const REFRESH_INTERVAL = 45_000;
const CLOCK_INTERVAL = 15_000;
const NOTICE_DISMISS = 8_000;
const WIB_OFFSET_MS = 7 * 60 * 60 * 1000;

const WIB_CLOCK = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

function timeToMinutes(value) {
    const match = /^(\d{1,2}):(\d{2})/.exec(String(value ?? ''));

    if (!match) return null;

    return Number(match[1]) * 60 + Number(match[2]);
}

function wibMinutes(date) {
    const parts = WIB_CLOCK.formatToParts(date);
    const hour = Number(parts.find((part) => part.type === 'hour')?.value ?? 0);
    const minute = Number(parts.find((part) => part.type === 'minute')?.value ?? 0);

    return hour * 60 + minute;
}

function wibClockLabel(date) {
    return WIB_CLOCK.format(date);
}

function msUntilNextTuesday(nowMs) {
    const wib = new Date(nowMs + WIB_OFFSET_MS);
    const day = wib.getUTCDay();
    const minutesNow = wib.getUTCHours() * 60 + wib.getUTCMinutes();
    let add = (2 - day + 7) % 7;

    if (add === 0 && minutesNow >= 540) {
        add = 7;
    }

    const target = Date.UTC(wib.getUTCFullYear(), wib.getUTCMonth(), wib.getUTCDate() + add, 9, 0, 0) - WIB_OFFSET_MS;

    return Math.max(0, target - nowMs);
}

function countdownParts(ms) {
    const totalMinutes = Math.floor(ms / 60000);

    return {
        days: Math.floor(totalMinutes / 1440),
        hours: Math.floor((totalMinutes % 1440) / 60),
        minutes: totalMinutes % 60,
    };
}

function slotPhase(slot, date = new Date()) {
    const start = timeToMinutes(slot.start_time);
    const end = timeToMinutes(slot.end_time);

    if (start === null || end === null) return 'unknown';

    const minutes = wibMinutes(date);

    if (minutes < start) return 'upcoming';
    if (minutes >= end) return 'ended';

    return 'live';
}

function useNowTick(interval = CLOCK_INTERVAL) {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), interval);

        return () => window.clearInterval(timer);
    }, [interval]);

    return now;
}

function LockIcon() {
    return (
        <svg className="lock-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <path d="M7.5 10V7.5a4.5 4.5 0 0 1 9 0V10" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
            <rect x="4.5" y="10" width="15" height="10" rx="2.5" fill="none" stroke="currentColor" strokeWidth="2" />
        </svg>
    );
}

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

const ACHIEVEMENT_SPARKLES = Array.from({ length: 18 }, (_, i) => ({
    left: ((i * 53) % 92) + 4,
    top: ((i * 37) % 84) + 8,
    size: 0.35 + ((i * 13) % 5) / 10,
    delay: -((i * 210) % 260) / 100,
    duration: 2.2 + ((i * 17) % 26) / 10,
}));

const BALLOON_COLORS = ['#ff6b9d', '#ffd166', '#6bd7ff', '#b388ff', '#63e6be', '#ff9f6b', '#f78fb3', '#8ce99a'];

const BALLOONS = Array.from({ length: 9 }, (_, i) => ({
    left: ((i * 23) % 88) + 5,
    color: BALLOON_COLORS[i % BALLOON_COLORS.length],
    delay: -((i * 320) % 900) / 100,
    duration: 7.5 + ((i * 19) % 45) / 10,
    scale: 0.7 + ((i * 11) % 7) / 10,
}));

const LIVE_DUST = Array.from({ length: 16 }, (_, i) => ({
    left: ((i * 29) % 94) + 3,
    size: 0.12 + ((i * 7) % 5) / 20,
    delay: -((i * 260) % 1400) / 100,
    duration: 9 + ((i * 23) % 70) / 10,
}));

const LIVE_EQ = [55, 85, 40, 100, 62, 78, 46, 92];

const AUDIENCE = Array.from({ length: 11 }, (_, i) => ({
    left: 3 + i * 8.6 + (i % 2) * 2.2,
    w: 0.9 + ((i * 13) % 6) / 10,
    delay: -((i * 170) % 320) / 100,
    duration: 2.8 + ((i * 19) % 24) / 10,
}));

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

function AchievementSlide({ achievement, brandLogoUrl }) {
    return (
        <article className="display-slide achievement-slide">
            <BoardBrand logoUrl={brandLogoUrl} />
            <ConfettiRain />
            <div className="achievement-gold" aria-hidden="true">
                <span className="achievement-rays" />
                <span className="achievement-beam" />
                {ACHIEVEMENT_SPARKLES.map((sparkle, i) => (
                    <span
                        key={i}
                        className="achievement-sparkle"
                        style={{
                            left: `${sparkle.left}%`,
                            top: `${sparkle.top}%`,
                            width: `${sparkle.size}vmax`,
                            height: `${sparkle.size}vmax`,
                            animationDelay: `${sparkle.delay}s`,
                            animationDuration: `${sparkle.duration}s`,
                        }}
                    />
                ))}
            </div>
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
            <span className="achievement-flash" aria-hidden="true" />
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

function BoardBrand({ logoUrl }) {
    return (
        <div className="board-brand">
            <ChannelLogo channel={{ name: 'Johen PUBG', logo_url: logoUrl }} />
            <span className="channel-name">Johen Gaming</span>
        </div>
    );
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

function StreamLinkLabel({ slot, phase, onBlocked }) {
    const [logoFailed, setLogoFailed] = useState(false);
    const url = slot.stream_link_url;
    const logo = slot.stream_link_logo_url;

    useEffect(() => {
        setLogoFailed(false);
    }, [logo]);

    if (!url) return null;

    const logoNode = logo && !logoFailed
        ? <img className="stream-link-logo" src={logo} alt="" onError={() => setLogoFailed(true)} />
        : null;

    if (phase === 'live') {
        return (
            <a className="stream-link" href={url} target="_blank" rel="noopener noreferrer" onClick={(event) => onBlocked(slot, event)}>
                {logoNode}
                <span className="stream-link-text">Link Live Tiktok</span>
            </a>
        );
    }

    return (
        <button type="button" className="stream-link is-locked" onClick={(event) => onBlocked(slot, event)}>
            {logoNode}
            <span className="stream-link-badge">{phase === 'ended' ? 'Sudah berakhir' : 'Belum mulai'}</span>
        </button>
    );
}

function StreamQr({ slot, phase, onBlocked }) {
    const [qrCode, setQrCode] = useState('');
    const url = slot.stream_link_url;

    useEffect(() => {
        let cancelled = false;
        setQrCode('');

        if (!url || phase !== 'live') return undefined;

        QRCode.toDataURL(url, { width: 512, margin: 1, errorCorrectionLevel: 'M' })
            .then((dataUrl) => {
                if (!cancelled) setQrCode(dataUrl);
            })
            .catch(() => {
                if (!cancelled) setQrCode('');
            });

        return () => { cancelled = true; };
    }, [url, phase]);

    if (!url) return null;

    if (phase !== 'live') {
        return (
            <button type="button" className="stream-qr stream-qr-locked" onClick={(event) => onBlocked(slot, event)}>
                <span className="stream-qr-lock"><LockIcon /></span>
                <span className="stream-qr-locked-text">{phase === 'ended' ? 'Sudah berakhir' : 'Belum mulai'}</span>
            </button>
        );
    }

    if (!qrCode) return null;

    return <img className="stream-qr" src={qrCode} alt={`QR Code ${slot.stream_link_name || 'link streaming'}`} />;
}

function ScheduleNotice({ notice, now, onClose }) {
    if (!notice) return null;

    const { slot, phase } = notice;
    const title = phase === 'ended' ? 'Jadwal sudah berakhir' : 'Jadwal belum mulai';
    const time = wibClockLabel(now);

    return (
        <div className="schedule-notice" role="alertdialog" aria-modal="true" aria-labelledby="schedule-notice-title" onClick={onClose}>
            <div className="schedule-notice-card" onClick={(event) => event.stopPropagation()}>
                <span className="schedule-notice-mark"><LockIcon /></span>
                <h2 id="schedule-notice-title">{title}</h2>
                <p>
                    Link &amp; QR untuk <strong>{slot.host_name || 'host ini'}</strong> hanya aktif pada sesi{' '}
                    <strong>{slot.start_time} – {slot.end_time}</strong>.
                </p>
                <p className="schedule-notice-now">Sekarang pukul {time} WIB.</p>
                <button type="button" className="schedule-notice-close" onClick={onClose}>Mengerti</button>
            </div>
        </div>
    );
}

function LiveHostsSlide({ channel, now, onBlocked }) {
    return (
        <article className="display-slide live-slide">
            <div className="live-stage" aria-hidden="true">
                <span className="live-lamp live-lamp-left" />
                <span className="live-lamp live-lamp-right" />
                <span className="live-beam live-beam-left" />
                <span className="live-beam live-beam-right" />
            </div>
            <div className="live-dust" aria-hidden="true">
                {LIVE_DUST.map((mote, i) => (
                    <span
                        key={i}
                        className="live-dust-mote"
                        style={{
                            left: `${mote.left}%`,
                            '--s': `${mote.size}rem`,
                            '--dur': `${mote.duration}s`,
                            '--delay': `${mote.delay}s`,
                        }}
                    />
                ))}
            </div>
            <header className="slide-heading live-heading">
                <div className="live-brand">
                    <ChannelLogo channel={channel} />
                    <span className="channel-name">{channel.name}</span>
                </div>
                <div className="live-heading-side">
                    <span className="slide-kicker">JADWAL HOST LIVE</span>
                    <span className="live-eq" aria-hidden="true">
                        {LIVE_EQ.map((h, i) => (
                            <span
                                key={i}
                                className="live-eq-bar"
                                style={{
                                    height: `${h}%`,
                                    animationDelay: `${(i * 0.13).toFixed(2)}s`,
                                    animationDuration: `${(0.62 + (i % 4) * 0.14).toFixed(2)}s`,
                                }}
                            />
                        ))}
                    </span>
                </div>
            </header>
            <div className="host-grid">
                {channel.slots.map((slot) => {
                    const phase = slotPhase(slot, now);
                    const isLive = phase === 'live';
                    const cardClass = ['host-card', slot.host_name ? '' : 'is-unassigned', isLive ? 'is-live' : ''].filter(Boolean).join(' ');

                    return (
                        <div key={slot.id} className={cardClass}>
                            {isLive && <span className="host-live-signal" aria-hidden="true" />}
                            <div className="host-card-photo"><HostPhoto host={slot} /></div>
                            <div className="host-card-meta">
                                <p className="host-card-name">
                                    <span className="host-card-name-text">{slot.host_name || 'Belum ditentukan'}</span>
                                    {isLive && <span className="host-card-live">Live</span>}
                                </p>
                                <time className="host-card-time">{slot.start_time} – {slot.end_time}</time>
                                <StreamLinkLabel slot={slot} phase={phase} onBlocked={onBlocked} />
                            </div>
                            <StreamQr slot={slot} phase={phase} onBlocked={onBlocked} />
                        </div>
                    );
                })}
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

function WeeklyMeetingSlide({ meetings, now, brandLogoUrl }) {
    const remaining = countdownParts(msUntilNextTuesday(now ? now.getTime() : Date.now()));
    const countdownText = remaining.days > 0
        ? `${remaining.days} hari ${remaining.hours} jam ${remaining.minutes} menit`
        : remaining.hours > 0
            ? `${remaining.hours} jam ${remaining.minutes} menit`
            : `${remaining.minutes} menit`;

    return (
        <article className="display-slide meeting-slide">
            <BoardBrand logoUrl={brandLogoUrl} />
            <div className="meeting-spotlights" aria-hidden="true">
                <span className="meeting-lamp meeting-lamp-left" />
                <span className="meeting-lamp meeting-lamp-right" />
                <span className="meeting-beam meeting-beam-left" />
                <span className="meeting-beam meeting-beam-right" />
            </div>
            <div className="meeting-projection" aria-hidden="true">
                <span className="meeting-screen" />
                <span className="meeting-projector" />
                <span className="meeting-projector-beam" />
            </div>
            <div className="meeting-audience" aria-hidden="true">
                {AUDIENCE.map((figure, i) => (
                    <span
                        key={i}
                        className="meeting-audience-figure"
                        style={{
                            left: `${figure.left}%`,
                            '--w': figure.w,
                            '--dur': `${figure.duration}s`,
                            '--delay': `${figure.delay}s`,
                        }}
                    />
                ))}
            </div>
            <div className="meeting-podium" aria-hidden="true"><span className="meeting-mic" /></div>
            <div className="meeting-curtains" aria-hidden="true">
                <span className="meeting-curtain meeting-curtain-left" />
                <span className="meeting-curtain meeting-curtain-right" />
            </div>
            <header className="meeting-head">
                <span className="slide-kicker meeting-kicker">WEEKLY MEETING</span>
                <h1>Jadwal Presentasi Weekly Meeting</h1>
                <p className="meeting-day">Selasa Berikutnya</p>
                <p className="meeting-countdown">
                    <span className="meeting-countdown-dot" aria-hidden="true" />
                    <span className="meeting-countdown-label">Mulai dalam</span>
                    <strong className="meeting-countdown-time">{countdownText}</strong>
                </p>
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

function BirthdaySlide({ birthday, brandLogoUrl }) {
    const dateLabel = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long' }).format(new Date(`${birthday.birth_date}T00:00:00`));

    return (
<article className="display-slide birthday-slide">
            <BoardBrand logoUrl={brandLogoUrl} />
            <div className="birthday-orbit birthday-orbit-one" aria-hidden="true" />
            <div className="birthday-orbit birthday-orbit-two" aria-hidden="true" />
            <ConfettiRain />
            <Fireworks />
            <div className="birthday-party" aria-hidden="true">
                <span className="birthday-rays" />
                {BALLOONS.map((balloon, i) => (
                    <span
                        key={i}
                        className="birthday-balloon"
                        style={{
                            left: `${balloon.left}%`,
                            '--balloon': balloon.color,
                            '--dur': `${balloon.duration}s`,
                            '--delay': `${balloon.delay}s`,
                            '--scale': balloon.scale,
                        }}
                    />
                ))}
            </div>
            <span className="slide-kicker birthday-kicker">HARI ULANG TAHUN</span>
            <p className="birthday-heading">Selamat ulang tahun</p>
            <DisplayImage className="birthday-photo" src={birthday.image_url} alt={`Foto ${birthday.employee_name}`} />
            <h1>{birthday.employee_name}</h1>
            <p className="birthday-division">{birthday.division ? `Divisi ${birthday.division}` : ''}</p>
            <time className="birthday-date">{dateLabel}</time>
            <span className="birthday-burst" aria-hidden="true" />
            <span className="birthday-burst birthday-burst-two" aria-hidden="true" />
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
    const [scheduleNotice, setScheduleNotice] = useState(null);
    const nowMs = useNowTick();
    const brandLogoUrl = useMemo(
        () => data?.live_channels?.find((channel) => (channel.name || '').toLowerCase().includes('johen pubg'))?.logo_url ?? null,
        [data],
    );

    useEffect(() => {
        if (!scheduleNotice) return undefined;

        const timer = window.setTimeout(() => setScheduleNotice(null), NOTICE_DISMISS);

        return () => window.clearTimeout(timer);
    }, [scheduleNotice]);

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
    function handleSlotAccess(slot, event) {
        const phase = slotPhase(slot, new Date());

        if (phase === 'live') {
            if (event.currentTarget?.tagName !== 'A' && slot.stream_link_url) {
                window.open(slot.stream_link_url, '_blank', 'noopener,noreferrer');
            }

            return;
        }

        event.preventDefault();
        setScheduleNotice({ slot, phase, at: Date.now() });
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
                {activeSlide.categoryKey === 'achievements' && <AchievementSlide key={`achievement-${activeSlide.item.id}`} achievement={activeSlide.item} brandLogoUrl={brandLogoUrl} />}
                {activeSlide.categoryKey === 'live_hosts' && <LiveHostsSlide key={`channel-${activeSlide.item.id}`} channel={activeSlide.item} now={new Date(nowMs)} onBlocked={handleSlotAccess} />}
                {activeSlide.categoryKey === 'birthdays' && <BirthdaySlide key={`birthday-${activeSlide.item.id}`} birthday={activeSlide.item} brandLogoUrl={brandLogoUrl} />}
                {activeSlide.categoryKey === 'weekly_meetings' && <WeeklyMeetingSlide key="weekly-meeting" meetings={activeSlide.item} now={new Date(nowMs)} brandLogoUrl={brandLogoUrl} />}
            </div>
            <footer className="display-footer" aria-hidden="true" />
            <ScheduleNotice notice={scheduleNotice} now={new Date(nowMs)} onClose={() => setScheduleNotice(null)} />
            {fullscreenErrorNotice}
            {!isFullscreen && document.fullscreenEnabled && <button className="fullscreen-control" type="button" onClick={toggleFullscreen} aria-label="Tampilkan layar penuh">FULLSCREEN</button>}
            
        </main>
    );
}
