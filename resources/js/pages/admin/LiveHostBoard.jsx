import React, { useCallback, useEffect, useState } from 'react';
import AdminLayout from '../../layouts/AdminLayout.jsx';
import { apiRequest } from '../../services/api.js';

function today() {
    return new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

export default function LiveHostBoard({ title }) {
    const [date, setDate] = useState(today);
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
            setBoard(await apiRequest(`/api/admin/live-hosts/board?date=${date}`));
        } catch (loadError) {
            setError(loadError.message);
        }
    }, [date]);

    useEffect(() => { load(); }, [load]);

    function pick(slotId, hostId) {
        setBoard((current) => ({
            ...current,
            channels: current.channels.map((channel) => ({
                ...channel,
                slots: channel.slots.map((slot) => (slot.id === slotId ? { ...slot, host_id: hostId ? Number(hostId) : null } : slot)),
            })),
        }));
    }

    async function save() {
        setBusy(true);
        setError('');
        setNotice('');
        try {
            setBoard(await apiRequest('/api/admin/live-hosts/board', {
                method: 'PUT',
                body: {
                    date,
                    assignments: board.channels.flatMap((channel) => channel.slots.map((slot) => ({
                        live_schedule_id: slot.id,
                        host_id: slot.host_id ?? null,
                    }))),
                },
            }));
            setNotice('Jadwal host live tersimpan.');
        } catch (saveError) {
            setError(saveError.message);
        } finally {
            setBusy(false);
        }
    }

    const assigned = board ? board.channels.reduce(
        (total, channel) => total + channel.slots.filter((slot) => slot.host_id).length,
        0,
    ) : 0;
    const totalSlots = board ? board.channels.reduce((total, channel) => total + channel.slots.length, 0) : 0;

    return (
        <AdminLayout title={title}>
            <div className="page-toolbar">
                <p className="muted">Pilih tanggal, lalu tentukan host untuk setiap slot jadwal yang sudah tersedia.</p>
                <a className="button button-light" href="/admin/hosts">Kelola daftar host</a>
            </div>

            <div className="filter-bar">
                <label>
                    Tanggal
                    <input type="date" value={date} onChange={(event) => setDate(event.target.value)} />
                </label>
                <label>
                    Terisi
                    <input type="text" value={`${assigned} / ${totalSlots} slot`} readOnly />
                </label>
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
                <>
                    <div className="schedule-board">
                        {board.channels.map((channel) => (
                            <section className="schedule-channel" key={channel.id}>
                                <h2>{channel.name}</h2>
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

                    <div className="form-actions">
                        <button className="button" type="button" onClick={save} disabled={busy}>
                            {busy ? 'Menyimpan' : 'Simpan jadwal'}
                        </button>
                        <button className="button button-light" type="button" onClick={load} disabled={busy}>Muat ulang</button>
                    </div>
                </>
            )}
        </AdminLayout>
    );
}
