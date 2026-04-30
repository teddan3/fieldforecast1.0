'use client';

import OddsComparisonTable from './_components/OddsComparisonTable';
import useSWR from 'swr';
import React, { useMemo, useState } from 'react';

type OddsComparisonResponse = {
  matches: Array<any>;
  meta?: Record<string, unknown>;
};

function buildUrl(params: Record<string, string | undefined>) {
  const base = process.env.NEXT_PUBLIC_API_BASE_URL;
  if (!base) return null;
  const urlBase = base ? base.replace(/\/$/, '') : '';
  const qs = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v && v.trim() !== '') qs.set(k, v);
  }
  return `${urlBase}/api/odds/comparison?${qs.toString()}`;
}

export default function HomePage() {
  const now = useMemo(() => new Date(), []);
  const [sport, setSport] = useState<string>('');
  const [league, setLeague] = useState<string>('');
  const [from, setFrom] = useState<string>(() => {
    const d = new Date(now.getTime() - 24 * 60 * 60 * 1000);
    return d.toISOString();
  });
  const [to, setTo] = useState<string>(() => {
    const d = new Date(now.getTime() + 72 * 60 * 60 * 1000);
    return d.toISOString();
  });

  const url = useMemo(() => {
    return buildUrl({
      sport,
      league,
      from,
      to,
      limit: '30',
    });
  }, [sport, league, from, to]);

  const { data, isLoading, error } = useSWR<OddsComparisonResponse>(url, fetcher, {
    refreshInterval: 45000,
  });

  const matches = data?.matches ?? [];

  return (
    <main className="mx-auto max-w-[1200px] px-4 py-6">
      <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 className="text-2xl font-bold">Odds Comparison</h1>
          <p className="mt-1 text-sm text-zinc-400">Live comparison (polling) with value-bet + arbitrage highlights.</p>
        </div>
        <div className="flex flex-wrap gap-2">
          <label className="flex flex-col gap-1 text-xs text-zinc-300">
            Sport (slug)
            <input
              className="w-44 rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm outline-none focus:border-emerald-500"
              value={sport}
              onChange={(e) => setSport(e.target.value)}
              placeholder="e.g. soccer_epl"
            />
          </label>
          <label className="flex flex-col gap-1 text-xs text-zinc-300">
            League (slug)
            <input
              className="w-44 rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm outline-none focus:border-emerald-500"
              value={league}
              onChange={(e) => setLeague(e.target.value)}
              placeholder="e.g. epl"
            />
          </label>
          <label className="flex flex-col gap-1 text-xs text-zinc-300">
            From (ISO)
            <input
              className="w-48 rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm outline-none focus:border-emerald-500"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
            />
          </label>
          <label className="flex flex-col gap-1 text-xs text-zinc-300">
            To (ISO)
            <input
              className="w-48 rounded border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm outline-none focus:border-emerald-500"
              value={to}
              onChange={(e) => setTo(e.target.value)}
            />
          </label>
        </div>
      </div>

      {error ? (
        <div className="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-red-200">
          Failed to load odds. Check your backend URL and CORS.
        </div>
      ) : null}

      {isLoading ? (
        <div className="rounded-lg border border-zinc-800 bg-zinc-900/40 p-6 text-zinc-400">Loading odds...</div>
      ) : (
        <OddsComparisonTable matches={matches} />
      )}
    </main>
  );
}

async function fetcher(url: string) {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json' }
  });
  if (!res.ok) {
    throw new Error(`Request failed: ${res.status}`);
  }
  return res.json();
}

