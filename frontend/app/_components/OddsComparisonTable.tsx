'use client';

import React from 'react';

type ValueBets = {
  home: boolean;
  draw: boolean;
  away: boolean;
};

type ValueProfit = {
  home: number;
  draw: number;
  away: number;
};

type BookmakerRow = {
  bookmaker_id: number;
  bookmaker_name: string;
  bookmaker_logo_url?: string | null;
  bookmaker_affiliate_url: string;
  home_odds: number;
  draw_odds: number | null;
  away_odds: number;
  is_best_home?: boolean;
  is_best_draw?: boolean;
  is_best_away?: boolean;
  value_bets?: ValueBets;
  value_profit?: ValueProfit;
};

type MatchPayload = {
  match_id: number;
  start_time: string;
  sport: { slug: string; name: string };
  league: { slug: string; name: string };
  teams: {
    home: { id: number; name: string };
    away: { id: number; name: string };
  };
  best_odds: { home: number | null; draw: number | null; away: number | null };
  best_bookmaker_ids: { home: number | null; draw: number | null; away: number | null };
  arbitrage: {
    is_arbitrage: boolean;
    implied_probability_sum: number;
    profit_percentage: number;
    stake_ratios: { home: number; draw: number; away: number };
  };
  bookmakers: BookmakerRow[];
};

export default function OddsComparisonTable({ matches }: { matches: MatchPayload[] }) {
  return (
    <div className="overflow-x-auto rounded-lg border border-zinc-800 bg-zinc-900/40">
      <table className="min-w-[980px] w-full text-sm">
        <thead className="sticky top-0 z-10 bg-zinc-950/95">
          <tr className="text-left text-zinc-300">
            <th className="p-3 font-semibold">Match</th>
            <th className="p-3 font-semibold">Bookmaker</th>
            <th className="p-3 font-semibold">Home Win</th>
            <th className="p-3 font-semibold">Draw</th>
            <th className="p-3 font-semibold">Away Win</th>
            <th className="p-3 font-semibold">Bet Now</th>
          </tr>
        </thead>
        <tbody>
          {matches.length === 0 ? (
            <tr>
              <td colSpan={6} className="p-6 text-center text-zinc-400">
                No matches found for the selected filters.
              </td>
            </tr>
          ) : (
            matches.map((m) =>
              m.bookmakers.map((b) => (
                <tr key={`${m.match_id}-${b.bookmaker_id}`} className="border-t border-zinc-800/70">
                  <td className="p-3 align-top">
                    <div className="font-semibold text-zinc-100">
                      {m.teams.home.name} vs {m.teams.away.name}
                    </div>
                    <div className="text-xs text-zinc-400">
                      {m.league.name} • {new Date(m.start_time).toLocaleString()}
                      {m.arbitrage.is_arbitrage ? (
                        <span className="ml-2 inline-flex items-center rounded border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-amber-300">
                          Arb +{m.arbitrage.profit_percentage.toFixed(2)}%
                        </span>
                      ) : null}
                    </div>
                  </td>
                  <td className="p-3 align-top">
                    <div className="flex items-center gap-2">
                      {b.bookmaker_logo_url ? (
                        <div className="relative h-8 w-8 overflow-hidden rounded bg-zinc-800">
                          {/* eslint-disable-next-line @next/next/no-img-element */}
                          <img
                            src={b.bookmaker_logo_url}
                            alt={b.bookmaker_name}
                            className="h-8 w-8 object-contain"
                            loading="lazy"
                          />
                        </div>
                      ) : (
                        <div className="h-8 w-8 rounded bg-zinc-800" />
                      )}
                      <div>
                        <div className="font-medium">{b.bookmaker_name}</div>
                      </div>
                    </div>
                  </td>
                  <OddsCell
                    value={b.home_odds}
                    isBest={!!b.is_best_home}
                    isValue={!!b.value_bets?.home}
                    valueProfit={b.value_profit?.home ?? 0}
                  />
                  <OddsCell
                    value={b.draw_odds}
                    isBest={!!b.is_best_draw}
                    isValue={!!b.value_bets?.draw}
                    valueProfit={b.value_profit?.draw ?? 0}
                  />
                  <OddsCell
                    value={b.away_odds}
                    isBest={!!b.is_best_away}
                    isValue={!!b.value_bets?.away}
                    valueProfit={b.value_profit?.away ?? 0}
                  />
                  <td className="p-3 align-top">
                    <a
                      href={b.bookmaker_affiliate_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center rounded bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500"
                    >
                      Bet Now
                    </a>
                  </td>
                </tr>
              ))
            )
          )}
        </tbody>
      </table>
    </div>
  );
}

function OddsCell({
  value,
  isBest,
  isValue,
  valueProfit,
}: {
  value: number | null;
  isBest: boolean;
  isValue: boolean;
  valueProfit: number;
}) {
  const rounded = 'rounded-md';

  if (value === null) {
    return (
      <td className="p-3 align-top">
        <div className="text-zinc-500">-</div>
      </td>
    );
  }

  const base = 'inline-flex items-center gap-2 px-2 py-1 ' + rounded;
  const bestClass = 'bg-emerald-500/15 border border-emerald-500/60';
  const valueClass = 'bg-teal-500/10 border border-teal-400/30';

  const className = base + (isBest ? ' ' + bestClass : '') + (isValue && !isBest ? ' ' + valueClass : '');

  return (
    <td className="p-3 align-top">
      <div className={className}>
        <span className="font-semibold text-zinc-100">{value.toFixed(2)}</span>
        {isBest ? <span className="text-[11px] font-bold text-emerald-300">Best</span> : null}
        {isValue ? <span className="text-[11px] font-bold text-teal-300">Value</span> : null}
        {isValue ? (
          <span className="text-[11px] text-zinc-300">{valueProfit >= 0 ? `EV +${valueProfit.toFixed(3)}` : ''}</span>
        ) : null}
      </div>
    </td>
  );
}

