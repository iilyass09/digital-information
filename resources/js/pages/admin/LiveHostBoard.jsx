import React, { useCallback, useEffect, useRef, useState } from 'react';
import AdminLayout from '../../layouts/AdminLayout.jsx';
import { apiRequest } from '../../services/api.js';

const LOGO_ACCEPT = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

export default function LiveHostBoard({ title }) {
    const formRef = useRef(null);
    const [board, setBoard] = useState(null);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (notice) window.sessionStorage.removeItem('cms-success');
    }, [notice]);

    const load = useCallback(async () => {
        setError('');
        try {
            setBoard(await apiRequest('/api/admin/live-hosts/board'));
        } catch (loadError) {
            setError(loadError.message);
        }
    }, []);

    useEffect(() => { load(); }, [load]);

    function pick(slotId, hostId) {
        setBoard((current) => ({
            ...current,
            channels: current.channels.map((channel) => ({
                ...channel,
                slots: channel.slots.map((slot) => (slot.id === slotId
                    ? { ...slot, host_id: hostId ? Number(hostId) : null }
                    : slot)),
            })),
        }));
    }

    function pickStreamUrl(channelId, streamUrl) {
        setBoard((current) => ({
            ...current,
            channels: current.channels.map((channel) => (
                channel.id === channelId ? { ...channel, stream_url: streamUrl } : channel
            )),
        }));
    }

    async function save() {
        setBusy(true);
        setError('');
        setNotice('');
        try {
            const logos = formRef.current?.querySelectorAll('input[type="file"]') ?? [];
            const body = new FormData();
            let assignmentIndex = 0;

            board.channels.forEach((channel, channelIndex) => {
                body.append(`channels[${channelIndex}][live_channel_id]`, channel.id);
                body.append(`channels[${channelIndex}][stream_url]`, channel.stream_url ?? '');
                if (logos[channelIndex]?.files?.[0]) {
                    body.append(`channels[${channelIndex}][stream_logo]`, logos[channelIndex].files[0]);
                }

                channel.slots.forEach((slot) => {
                    body.append(`assignments[${assignmentIndex}][live_schedule_id]`, slot.id);
                    if (slot.host_id) {
                        body.append(`assignments[${assignmentIndex}][host_id]`, slot.host_id);
                    }
                    assignmentIndex += 1;
                });
            });

            setBoard(await apiRequest('/api/admin/live-hosts/board', { method: 'PUT', body }));
            if (formRef.current) formRef.current.reset();
            setNotice('Jadwal host live tersimpan.');
        } catch (saveError) {
            setError(saveError.message);
        } finally {
            setBusy(false);
        }
    }

    return (
        <AdminLayout title={title}>
            <form ref={formRef} onSubmit={(event) => { event.preventDefault(); save(); }}>
                <div className="page-toolbar">
                    <p className="muted">Jadwal berlaku setiap hari. Tentukan host untuk setiap slot, lalu simpan.</p>
                    <a className="button button-light" href="/admin/hosts">Kelola daftar host</a>
                </div>

                {error && <p className="notice notice-error" role="alert">{error}</p>}
                {notice && <p className="notice notice-success" role="status">{notice}</p>}

                {!board && !error && <section className="empty-state"><span>⌛</span><h2>Memuat jadwal</h2><p>Membaca slot jadwal per channel.</p></section>}

                {board && board.channels.length === 0 && (
                    <section className="empty-state">
                        <span>CMS</span>
                        <h2>Belum ada jadwal</h2>
                        <p>Jadwal slot per channel belum tersedia.</p>
                    </section>
                )}

                {board && board.channels.length > 0 && (
                    <div className="schedule-board">
                        {board.channels.map((channel, channelIndex) => (
                            <section className="schedule-channel" key={channel.id}>
                                <h2>{channel.name}</h2>

                                <div className="schedule-stream">
                                    <label>
                                        Link streaming (dipakai semua slot channel ini)
                                        <input
                                            type="url"
                                            value={channel.stream_url ?? ''}
                                            onChange={(event) => pickStreamUrl(channel.id, event.target.value)}
                                        />
                                    </label>
                                    <label>
                                        Logo link streaming
                                        <input type="file" accept={LOGO_ACCEPT} />
                                    </label>
                                    {channel.stream_logo_url && (
                                        <img className="form-preview" src={channel.stream_logo_url} alt={`Logo streaming ${channel.name}`} />
                                    )}
                                </div>

                                <div className="schedule-slots">
                                    {channel.slots.map((slot) => (
                                        <label className="schedule-slot" key={slot.id}>
                                            <span className="schedule-time">{slot.start_time} - {slot.end_time}</span>
                                            <select value={slot.host_id ?? ''} onChange={(event) => pick(slot.id, event.target.value)}>
                                                <option value="">Belum diisi</option>
                                                {board.hosts.map((host) => (
                                                    <option key={host.id} value={host.id}>{host.name}</option>
                                                ))}
                                            </select>
                                        </label>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                )}

                {board && board.channels.length > 0 && (
                    <div className="form-actions">
                        <button className="button" type="submit" disabled={busy}>
                            {busy ? 'Menyimpan' : 'Simpan jadwal'}
                        </button>
                        <button className="button button-light" type="button" onClick={load} disabled={busy}>Muat ulang</button>
                    </div>
                )}
            </form>
        </AdminLayout>
    );
}
